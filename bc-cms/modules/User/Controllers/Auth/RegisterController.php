<?php


	namespace Modules\User\Controllers\Auth;


	use App\Helpers\ReCaptchaEngine;
    use Illuminate\Auth\Events\Registered;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\MessageBag;
    use Illuminate\Validation\Rules\Password;
    use Matrix\Exception;
    use Modules\User\Events\SendMailUserRegistered;

    class RegisterController extends \App\Http\Controllers\Auth\RegisterController
	{

	    public function register(Request $request)
        {
            if(!is_enable_registration()){
                return $this->sendError(__("You are not allowed to register"));
            }
            $rules = [
                'first_name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'last_name'  => [
                    'required',
                    'string',
                    'max:255'
                ],
                'email'      => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:users'
                ],
                'password'   => [
                    'required',
                    'string',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
                'phone'       => ['required','unique:users'],
                'business_name' => ['required', 'string', 'max:255'],   // signing up creates a company
                'term'       => ['required'],
                'plan_id'    => ['required', 'integer'],
                'os'         => ['nullable', 'array'],
                'os.*'       => ['string', 'max:20'],
            ];
            $messages = [
                'phone.required'      => __('Phone is required field'),
                'email.required'      => __('Email is required field'),
                'email.email'         => __('Email invalidate'),
                'password.required'   => __('Password is required field'),
                'first_name.required' => __('The first name is required field'),
                'last_name.required'  => __('The last name is required field'),
                'term.required'       => __('The terms and conditions field is required'),
                'business_name.required' => __('Your company name is required'),
                'plan_id.required'    => __('Choose a plan.'),
            ];
            if (ReCaptchaEngine::isEnable() and setting_item("user_enable_register_recaptcha")) {
                $codeCapcha = $request->input('g-recaptcha-response');
                if (!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)) {
                    $errors = new MessageBag(['message_error' => __('Please verify the captcha')]);
                    return response()->json([
                        'error'    => true,
                        'messages' => $errors
                    ], 200);
                }
            }
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'error'    => true,
                    'messages' => $validator->errors()
                ], 200);
            } elseif ($problem = \Modules\Vendor\Services\Onboarding::checkChoice((int) $request->input('plan_id'), (array) $request->input('os', []))) {
                return response()->json(['error' => true, 'messages' => ['os' => [$problem]]], 200);
            } else {

                $user = \App\User::create([
                    'first_name'    => $request->input('first_name'),
                    'last_name'     => $request->input('last_name'),
                    'email'         => $request->input('email'),
                    'password'      => Hash::make($request->input('password')),
                    'status'        => $request->input('publish', 'publish'),
                    'phone'         => $request->input('phone'),
                    'business_name' => $request->input('business_name'),
                    'address'       => $request->input('address'),
                    'city'          => $request->input('city'),
                    'country'       => $request->input('country'),
                ]);
                // Signing up creates a vendor company; the person is made its owner before they are signed in.
                $onboarding = app(\Modules\Vendor\Services\Onboarding::class)->registerCompany($user, (int) $request->input('plan_id'), (array) $request->input('os', []));
                if ($onboarding['approved']) {
                    $days = $onboarding['trial'] ? (int) now()->diffInDays($onboarding['trial']->ends_at) + 1 : 0;
                    session()->put('welcome_notice', __('Welcome to :site! :company is ready.', ['site' => setting_item('site_title') ?: 'the portal', 'company' => $user->business_name])
                        . ($days ? ' ' . __('You have a free :d-day trial: add your first listing, and invite your team under Team.', ['d' => $days]) : ' ' . __('Invite your team under Team.')));
                    $redirectTo = '/user/dashboard';
                } else {
                    session()->flash('area_notice', __('Thanks for signing up. The platform team will approve your company shortly; we will email you.'));
                    $redirectTo = route('user.profile.index');
                }
                event(new Registered($user));
                Auth::loginUsingId($user->id);
                try {
                    event(new SendMailUserRegistered($user));
                } catch (Exception $exception) {

                    Log::warning("SendMailUserRegistered: " . $exception->getMessage());
                }
                return response()->json([
                    'error'    => false,
                    'messages' => false,
                    'redirect' => $redirectTo
                ], 200);
            }
        }
    }
