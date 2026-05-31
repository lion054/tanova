@extends('layouts.auth')
@section('title', __('Verify Email') . ' — ' . config('app.name', 'Tsoka Travel'))

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
    <h1>Check your<br><em>inbox.</em></h1>
@endsection
@section('brand-sub')
    <p>We sent a verification link to your email address. Click it to activate your account.</p>
@endsection

@section('content')
<div class="form-heading">
    <h2>{{ __('Verify Your Email') }}</h2>
    <p>{{ __('Wrong account?') }} <a href="{{ route('logout') }}"
        onclick="event.preventDefault(); document.getElementById('verify-logout').submit();">{{ __('Sign out') }}</a></p>
</div>

@if(session('resent'))
    <div class="alert-success">{{ __('A fresh verification link has been sent to your email address.') }}</div>
@endif

<p style="font-size:13px;color:var(--g600);line-height:1.7;margin-bottom:24px;">
    {{ __('Before proceeding, please check your email for a verification link.') }}
    {{ __("If you didn't receive it, click below to resend.") }}
</p>

<form action="{{ route('verification.send') }}" method="POST">
    @csrf
    <button type="submit" class="btn-auth">{{ __('Resend Verification Email') }}</button>
</form>

<form id="verify-logout" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
@endsection
