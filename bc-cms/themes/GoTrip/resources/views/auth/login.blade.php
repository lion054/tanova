@extends('layouts.auth')
@section('title', __('Sign In') . ' — ' . config('app.name'))

@section('content')
<div class="row align-items-center">

    <div class="col-lg-6 d-none d-lg-block text-center">
        <img src="{{ url('/orion/images/sign-up.svg') }}" alt="" class="auth-illustration">
        <div class="mt-30">
            <h2 style="color:#0D2D2F;font-size:28px;font-weight:600;">Welcome to {{ config('app.name') }}</h2>
            <p style="color:#666;margin-top:10px;font-size:16px;">Your travel management portal.<br>Sign in to get started.</p>
        </div>
    </div>

    <div class="col-lg-5 ml-auto">
        <div class="user-form-wrapper">
            <div class="title-area pb-40">
                <h3>{{ __('Sign In') }}</h3>
                <p>{{ __("Welcome back! Please login to your account.") }}</p>
            </div>

            @if(session('status'))
                <div class="alert-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="redirect" value="{{ request()->query('redirect') }}">

                <div class="row">
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label>{{ __('Email') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="{{ __('Enter your email') }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label>{{ __('Password') }}</label>
                            <input type="password" name="password" required placeholder="{{ __('Enter your password') }}">
                        </div>
                    </div>
                </div>

                <div class="agreement-checkbox d-flex justify-content-between align-items-center" style="margin-bottom:30px;">
                    <div>
                        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">{{ __('Remember Me') }}</label>
                    </div>
                    <a href="{{ route('password.request') }}" style="color:#0D2D2F;font-size:14px;">{{ __('Forgot Password?') }}</a>
                </div>

                <button type="submit" class="theme-button-one" style="width:100%;display:block;border:none;cursor:pointer;">
                    <i class="fa fa-sign-in" aria-hidden="true"></i> &nbsp;{{ __('Login') }}
                </button>
            </form>

            @if(is_enable_registration())
                <p class="mt-20 text-center" style="color:#888;font-size:14px;">
                    {{ __("Don't have an account?") }}
                    <a href="{{ route('auth.register') }}" style="color:#0D2D2F;font-weight:600;">{{ __('Sign up') }}</a>
                </p>
            @endif
        </div>
    </div>

</div>
@endsection
