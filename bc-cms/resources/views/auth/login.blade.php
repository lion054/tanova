@extends('layouts.auth')
@section('title', __('Sign In') . ' — ' . config('app.name', 'Tsoka Travel'))

@section('panel-style')
<style>
    #auth-left-panel {
        background:
            linear-gradient(to bottom,
                rgba(10,9,8,.82) 0%,
                rgba(10,9,8,.55) 42%,
                rgba(10,9,8,.88) 100%),
            url('https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=1100&q=85')
            center center / cover no-repeat;
    }
    #auth-left-panel::before { display: none; }
</style>
@endsection

@section('brand-heading')
    <h1>Welcome<br><em>back.</em></h1>
@endsection
@section('brand-sub')
    <p>Sign in to manage tours, bookings and AI-assisted itineraries across your EMEA travel operations.</p>
@endsection

@section('content')
<div class="form-heading">
    <h2>{{ __('Sign In') }}</h2>
    <p>{{ __("Don't have an account?") }}
        @if(is_enable_registration())
            <a href="{{ route('auth.register') }}">{{ __('Create your company account') }}</a>
        @endif
    </p>
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

    <div class="field">
        <label>{{ __('Email') }}</label>
        <div class="input-wrap has-icon">
            <svg class="lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>
            </svg>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="email" placeholder="you@example.com">
        </div>
    </div>

    <div class="field">
        <label>{{ __('Password') }}</label>
        <div class="input-wrap has-icon has-toggle">
            <svg class="lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
            </svg>
            <input id="pw" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••••">
            <button type="button" class="toggle-pw" aria-label="{{ __('Show password') }}"
                    onclick="(function(b){var i=document.getElementById('pw');var s=i.type==='password';i.type=s?'text':'password';b.querySelector('.eye').style.display=s?'none':'';b.querySelector('.eye-off').style.display=s?'':'none';})(this)">
                <svg class="eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:none" aria-hidden="true">
                    <path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a18 18 0 0 1-3 4M6.6 6.6A18 18 0 0 0 2 11s3.5 7 10 7a10.9 10.9 0 0 0 4.1-.8"/><path d="m3 3 18 18"/><path d="M9.7 9.7a3 3 0 0 0 4.2 4.2"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="form-extras">
        <label><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> {{ __('Remember me') }}</label>
        <a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
    </div>

    <button type="submit" class="btn-auth">
        {{ __('Sign In') }}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </button>
</form>
@endsection
