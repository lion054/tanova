@extends('layouts.auth')
@section('title', __('Create Account') . ' — ' . config('app.name'))

@section('content')
<div class="row align-items-start">

    <div class="col-lg-5 d-none d-lg-block text-center" style="padding-top:40px;">
        <div class="mb-30">
            <h2 style="color:#0D2D2F;font-size:26px;font-weight:600;">{{ __('Join') }} {{ config('app.name') }}</h2>
            <p style="color:#666;margin-top:10px;font-size:15px;">
                {{ __('Create an account to access the portal.') }}<br>
                {{ __('Already signed up?') }}
                <a href="{{ route('login') }}" style="color:#C5E0DF;font-weight:600;">{{ __('Sign in') }}</a>
            </p>
        </div>
        <img src="{{ url('/orion/images/sign-up.svg') }}" alt="" class="auth-illustration">
    </div>

    <div class="col-lg-6 ml-auto">
        <div class="user-form-wrapper">
            <div class="title-area pb-30">
                <h3>{{ __('Create Account') }}</h3>
                <p class="d-lg-none">
                    {{ __('Already have an account?') }}
                    <a href="{{ route('login') }}" style="color:#C5E0DF;">{{ __('Sign in') }}</a>
                </p>
            </div>

            @if($errors->any())
                <div class="alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.register.store') }}" class="bc-form-register">
                @csrf
                <input type="hidden" name="redirect" value="{{ request()->get('redirect') }}">

                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group-wrapper">
                            <label>{{ __('First Name') }}</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="{{ __('First Name') }}">
                        </div>
                        <span class="invalid-feedback error error-first_name" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-wrapper">
                            <label>{{ __('Last Name') }}</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="{{ __('Last Name') }}">
                        </div>
                        <span class="invalid-feedback error error-last_name" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label>{{ __('Phone') }}</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone number') }}">
                        </div>
                        <span class="invalid-feedback error error-phone" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label>{{ __('Email') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="{{ __('Email address') }}">
                        </div>
                        <span class="invalid-feedback error error-email" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label>{{ __('Password') }}</label>
                            <input type="password" name="password" required minlength="8" placeholder="{{ __('Min. 8 characters') }}">
                        </div>
                        <span class="invalid-feedback error error-password" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                </div>

                <div class="agreement-checkbox" style="margin-bottom:30px;">
                    <div>
                        <input type="checkbox" id="register-term" name="term">
                        <label for="register-term">{{ __('I accept the') }} <a href="#">{{ __('Terms') }}</a> {{ __('and') }} <a href="#">{{ __('Privacy Policy') }}</a></label>
                    </div>
                    <span class="error error-term" style="color:#e74c3c;font-size:13px;"></span>
                </div>

                <div class="error message-error" style="color:#e74c3c;font-size:14px;margin-bottom:15px;"></div>

                <button type="submit" class="theme-button-one" style="width:100%;display:block;border:none;cursor:pointer;">
                    {{ __('Create Account') }}
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
