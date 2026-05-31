@extends('layouts.auth')
@section('title', __('Reset Password') . ' — ' . config('app.name', 'Tsoka Travel'))

@section('panel-style')
<style>
    #auth-left-panel {
        background:
            linear-gradient(to bottom,
                rgba(0,0,0,.85) 0%,
                rgba(0,0,0,.65) 40%,
                rgba(0,0,0,.88) 100%),
            url('https://images.unsplash.com/photo-1488085061387-422e29b40080?auto=format&fit=crop&w=900&q=85')
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
    <h1>Forgot your<br><em>password?</em></h1>
@endsection
@section('brand-sub')
    <p>No problem. Enter your email and we'll send a reset link straight to your inbox.</p>
@endsection

@section('content')
<div class="form-heading">
    <h2>{{ __('Reset Password') }}</h2>
    <p>{{ __('Remember it?') }} <a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></p>
</div>

@if(session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="field">
        <label>{{ __('Email Address') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="you@example.com">
    </div>

    <button type="submit" class="btn-auth">{{ __('Send Reset Link') }}</button>
</form>
@endsection
