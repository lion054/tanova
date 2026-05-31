@extends('layouts.auth')
@section('title', __('Sign In') . ' — ' . config('app.name', 'Tsoka Travel'))

@section('panel-style')
<style>
    #auth-left-panel {
        background:
            linear-gradient(to bottom,
                rgba(0,0,0,.82) 0%,
                rgba(0,0,0,.60) 40%,
                rgba(0,0,0,.85) 100%),
            url('https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=900&q=85')
            center center / cover no-repeat;
    }
    #auth-left-panel::before { display: none; }
</style>
@endsection

@section('panel-deco')
    <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:28px;">
        <span style="width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.4);display:inline-block;"></span>
        <span style="font-size:10px;font-weight:500;letter-spacing:.12em;color:rgba(255,255,255,.4);text-transform:uppercase;">EMEA Travel Portal</span>
    </div>
@endsection

@section('brand-heading')
    <h1>Welcome<br><em>back.</em></h1>
@endsection
@section('brand-sub')
    <p>Sign in to manage your travel operations across EMEA.</p>
@endsection

@section('content')
<div class="form-heading">
    <h2>{{ __('Sign In') }}</h2>
    <p>{{ __("Don't have an account?") }}
        @if(is_enable_registration())
            <a href="{{ route('auth.register') }}">{{ __('Create one') }}</a>
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
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="you@example.com">
    </div>

    <div class="field">
        <label>{{ __('Password') }}</label>
        <input type="password" name="password" required placeholder="••••••••••">
    </div>

    <div class="form-extras">
        <label><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> &nbsp;{{ __('Remember me') }}</label>
        <a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
    </div>

    <button type="submit" class="btn-auth">{{ __('Sign In') }}</button>
</form>
@endsection
