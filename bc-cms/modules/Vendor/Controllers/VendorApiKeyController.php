<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Vendor\Emails\ApiKeyRotatedEmail;
use Modules\Vendor\Models\VendorApiKey;

class VendorApiKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all API keys for the authenticated vendor (keys masked).
     */
    public function index(): JsonResponse
    {
        $keys = VendorApiKey::where('vendor_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($k) => $this->mask($k));

        return response()->json(['data' => $keys]);
    }

    /**
     * Generate a new API key. Plain-text key is returned ONCE.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'type'       => 'nullable|in:secret,publishable', // publishable = read-only, browser-safe
            'mode'       => 'nullable|in:live,test',          // test = sandbox key
            'domain'     => 'nullable|string|max:255',   // website domain — auto-registers CORS
            'rate_limit' => 'nullable|integer|min:0|max:10000000',
        ]);

        $key = VendorApiKey::generate(
            Auth::user(),
            $request->input('name'),
            $request->integer('rate_limit', 10000),
            $request->input('domain'),
            $request->input('type', 'secret'),
            $request->input('mode', 'live'),
        );

        return response()->json([
            'message' => 'API key created. Copy the key now — it will not be shown again.',
            'data'    => array_merge($this->mask($key)->toArray(), ['key' => $key->key]),
        ], 201);
    }

    /**
     * Revoke an API key.
     */
    public function destroy(int $id): JsonResponse
    {
        $key = VendorApiKey::where('vendor_id', Auth::id())->findOrFail($id);
        $key->update(['active' => false]);

        return response()->json(['message' => 'API key revoked.']);
    }

    /**
     * Rotate (regenerate) an API key. New plain-text key returned ONCE.
     */
    public function rotate(int $id): JsonResponse
    {
        $key    = VendorApiKey::where('vendor_id', Auth::id())->findOrFail($id);
        $vendor = Auth::user();
        $plain  = $key->rotate();

        // Notify the vendor so they know a rotation happened (security alert)
        Mail::to($vendor)->send(new ApiKeyRotatedEmail($key->name, $vendor->name ?? $vendor->email));

        return response()->json([
            'message' => 'API key rotated. Copy the new key now — it will not be shown again.',
            'data'    => array_merge($this->mask($key->fresh())->toArray(), ['key' => $plain]),
        ]);
    }

    /**
     * Usage stats for a specific key or all keys.
     */
    public function usage(Request $request): JsonResponse
    {
        $keyId = $request->query('key_id');

        $query = VendorApiKey::where('vendor_id', Auth::id());

        if ($keyId) {
            $query->where('id', $keyId);
        }

        // Single query: aggregate usage per key for this year — no N+1
        $usageCounts = \DB::table('bc_vendor_api_usage')
            ->whereIn('vendor_api_key_id', (clone $query)->pluck('id'))
            ->where('created_at', '>=', now()->startOfYear())
            ->selectRaw('vendor_api_key_id, COUNT(*) as total, ROUND(AVG(response_time_ms)) as avg_ms')
            ->groupBy('vendor_api_key_id')
            ->get()
            ->keyBy('vendor_api_key_id');

        $keys = $query->get();

        $stats = $keys->map(fn($k) => [
            'key_id'              => $k->id,
            'name'                => $k->name,
            'requests_this_year'  => (int) ($usageCounts[$k->id]->total ?? 0),
            'avg_response_ms'     => (int) ($usageCounts[$k->id]->avg_ms ?? 0),
            'rate_limit'          => $k->rate_limit,
            'rate_limit_percent'  => $k->rate_limit > 0
                ? round(($usageCounts[$k->id]->total ?? 0) / $k->rate_limit * 100, 1)
                : 0,
            'last_used_at'        => $k->last_used_at,
        ]);

        return response()->json(['data' => $stats]);
    }

    private function mask(VendorApiKey $key): VendorApiKey
    {
        $key->makeHidden(['key', 'key_hash']);
        return $key;
    }
}
