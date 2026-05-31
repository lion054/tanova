@extends('errors::layout')

@section('icon', '⏱️')
@section('code', '419')
@section('title', __('Session Expired'))
@section('message', __('Your session has timed out for security purposes. Please go back to the previous page and try your action again, or return to the homepage to start fresh.'))

@section('actions')
    <a href="javascript:history.back()" class="btn-primary">
        ↩ {{ __('Try Again') }}
    </a>
    <a href="/" class="btn-secondary">
        {{ __('Go to Home') }}
    </a>
@endsection
