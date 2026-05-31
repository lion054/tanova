@extends('errors::layout')

@section('icon', '🔒')
@section('code', '403')
@section('title', __('Access Restricted'))
@section('message', !empty($exception->getMessage()) ? $exception->getMessage() : __('You don\'t have permission to access this area. If you believe this is a mistake, please contact support or sign in with the correct account.'))

@section('actions')
    <a href="/" class="btn-primary">
        ← {{ __('Back to Home') }}
    </a>
    <a href="{{ route('dashboard') }}" class="btn-secondary">
        {{ __('Go to Dashboard') }}
    </a>
@endsection
