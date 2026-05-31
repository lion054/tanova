@extends('layouts.auth')
@section('title', __('Two-Factor Authentication') . ' — ' . config('app.name', 'Tsoka Travel'))

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
    <h1>Two-factor<br><em>check.</em></h1>
@endsection
@section('brand-sub')
    <p>Open your authenticator app and enter the code to complete sign in.</p>
@endsection

@section('content')
@if(request('type') == 'recovery_code')
<div class="form-heading">
    <h2>{{ __('Recovery Code') }}</h2>
    <p><a href="{{ route('two-factor.login') }}">{{ __('Use an authentication code instead') }}</a></p>
</div>
@else
<div class="form-heading">
    <h2>{{ __('Authentication Code') }}</h2>
    <p><a href="{{ route('two-factor.login', ['type' => 'recovery_code']) }}">{{ __('Use a recovery code instead') }}</a></p>
</div>
@endif

@if($errors->any())
    <div class="alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ url('two-factor-challenge') }}">
    @csrf

    @if(request('type') == 'recovery_code')
        <div class="field">
            <label>{{ __('Recovery Code') }}</label>
            <input type="text" name="recovery_code" required autofocus
                   autocomplete="one-time-code" placeholder="xxxx-xxxx-xxxx">
        </div>
    @else
        <div class="field">
            <label>{{ __('6-digit Code') }}</label>
            <input type="text" name="code" required autofocus
                   autocomplete="one-time-code" inputmode="numeric"
                   maxlength="6" placeholder="000000">
        </div>
    @endif

    <button type="submit" class="btn-auth">{{ __('Verify') }}</button>
</form>
@endsection
