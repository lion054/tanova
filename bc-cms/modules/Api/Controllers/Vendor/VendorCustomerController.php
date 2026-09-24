<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Events\SendMailUserRegistered;
use Modules\User\Models\Role;

/**
 * Customer accounts for a vendor's own app or site: POST /api/v/customers/...
 *
 * The vendor's API key says which app is calling; the customer's own token
 * (X-Customer-Token) says who is signed in. Accounts made here are ALWAYS the
 * `customer` role. Registration does not use the portal's default-role setting,
 * which production points at `vendor` because portal.tsokatravel.com is where
 * vendors sign up. A customer token can only reach /customers/* and
 * /customer/* endpoints, never the vendor dashboard or the wider API.
 */
class VendorCustomerController extends Controller
{
    use ApiResponse;

    private const TOKEN_NAME = 'vendor-app';

    public function register(Request $request): JsonResponse
    {
        if (!is_enable_registration()) {
            return $this->error('registration_closed', 'New accounts cannot be created right now.', 403);
        }

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email:rfc|max:255',
            'password'   => 'required|string|min:8|max:100',
            'phone'      => 'nullable|string|max:30',
            'terms'      => 'accepted',
        ]);

        $email = Str::lower(trim($data['email']));
        if (User::where('email', $email)->exists()) {
            return $this->error('email_taken', 'An account with this email already exists. Try signing in.', 422);
        }

        $customer = Role::where('code', 'customer')->first();
        if (!$customer) {
            Log::error('Customer role is missing; customer registration refused.');
            return $this->error('unavailable', 'Sign-up is unavailable right now.', 503);
        }

        // The account and its role are made together: a failure between them
        // must not leave someone with an account and no role.
        $user = DB::transaction(function () use ($data, $email, $customer) {
            $user = User::create([
                'first_name' => trim($data['first_name']),
                'last_name'  => trim($data['last_name']),
                'email'      => $email,
                'password'   => Hash::make($data['password']),
                'phone'      => $data['phone'] ?? null,
            ]);
            // By id: assignRole() mishandles being given the Role itself.
            $user->assignRole($customer->id);
            return $user;
        });

        try {
            event(new Registered($user));
            event(new SendMailUserRegistered($user));
        } catch (\Throwable $e) {
            Log::warning('Customer registered, but the welcome email failed: ' . $e->getMessage());
        }

        return $this->success($this->session($user, (string) $request->input('device_name', 'app')), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', Str::lower(trim($data['email'])))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error('invalid_credentials', 'That email and password do not match.', 401);
        }
        // A vendor or admin signs in on the portal, not here.
        if (!self::isCustomer($user)) {
            return $this->error('not_a_customer', 'This account is not a customer account.', 403);
        }

        return $this->success($this->session($user, $data['device_name'] ?? 'app'));
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->profile(self::customer($request)));
    }

    public function update(Request $request): JsonResponse
    {
        $user = self::customer($request);
        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name'  => 'sometimes|required|string|max:100',
            'phone'      => 'sometimes|nullable|string|max:30',
        ]);
        $user->fill($data)->save();
        return $this->success($this->profile($user->fresh()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = self::customer($request);
        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|max:100',
        ]);
        if (!Hash::check($data['current_password'], $user->password)) {
            return $this->error('invalid_credentials', 'Your current password is not right.', 422);
        }
        $user->password = Hash::make($data['password']);
        $user->save();
        return $this->success(['changed' => true]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = self::token($request);
        if ($token) {
            $token->delete();
        }
        return $this->success(['signed_out' => true]);
    }

    /**
     * Sends the portal's reset email. Always answers the same, so the endpoint
     * cannot be used to find out who has an account.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        try {
            Password::sendResetLink(['email' => Str::lower(trim($data['email']))]);
        } catch (\Throwable $e) {
            Log::warning('Customer password reset failed: ' . $e->getMessage());
        }
        return $this->success(['sent' => true]);
    }

    /**
     * Deletes the account: what identifies the person goes, the bookings stay
     * (the vendor still has to honour them) but no longer point at anyone.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = self::customer($request);
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->input('password'), $user->password)) {
            return $this->error('invalid_credentials', 'Your password is not right.', 422);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            DB::table('bc_bookings')->where('customer_id', $user->id)->update(['customer_id' => null]);
            $user->forceDelete();
        });

        return $this->success(['deleted' => true]);
    }

    // ── Who is calling ──────────────────────────────────────────────

    public static function token(Request $request): ?PersonalAccessToken
    {
        $plain = (string) $request->header('X-Customer-Token', '');
        return $plain === '' ? null : PersonalAccessToken::findToken($plain);
    }

    /** The signed-in customer, or a 401 JSON response thrown as an exception. */
    public static function customer(Request $request): User
    {
        $token = self::token($request);
        $user = $token?->tokenable;
        if (!$user instanceof User || !self::isCustomer($user)) {
            abort(response()->json(
                ['error' => ['code' => 'unauthenticated', 'message' => 'Please sign in.']],
                401
            ));
        }
        return $user;
    }

    public static function isCustomer(User $user): bool
    {
        return optional($user->role)->code === 'customer';
    }

    // ── Shapes ──────────────────────────────────────────────────────

    private function session(User $user, string $device): array
    {
        return [
            'token'   => $user->createToken(self::TOKEN_NAME . ':' . Str::limit($device, 60, ''))->plainTextToken,
            'profile' => $this->profile($user),
        ];
    }

    private function profile(User $user): array
    {
        return [
            'id'         => $user->id,
            'first_name' => (string) $user->first_name,
            'last_name'  => (string) $user->last_name,
            'email'      => (string) $user->email,
            'phone'      => (string) $user->phone,
        ];
    }
}
