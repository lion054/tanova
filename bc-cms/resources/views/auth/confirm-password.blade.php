@extends('layouts.auth')
@section('title', __('Confirm Password') . ' — ' . config('app.name', 'Tsoka Travel'))

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
    <h1>Secure<br><em>area.</em></h1>
@endsection
@section('brand-sub')
    <p>Confirm your password to continue into this protected section of your account.</p>
@endsection

@section('content')
<div class="form-heading">
    <h2>{{ __('Confirm Password') }}</h2>
    <p>{{ __('This is a protected area. Please re-enter your password to proceed.') }}</p>
</div>

@if($errors->any())
    <div class="alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="field">
        <label>{{ __('Password') }}</label>
        <input type="password" name="password" required autocomplete="current-password"
               placeholder="••••••••••" autofocus>
    </div>

    <button type="submit" class="btn-auth">{{ __('Confirm') }}</button>
</form>
@endsection
