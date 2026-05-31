@extends('errors::layout')

@section('icon', '⚙️')
@section('code', '500')
@section('title', __('Server Error'))
@section('message', __('Something went wrong on our end. Our team has been notified and is working to fix the issue. Please try again in a few moments.'))

@section('actions')
    <a href="/" class="btn-primary">
        ← {{ __('Back to Home') }}
    </a>
    <a href="javascript:location.reload()" class="btn-secondary">
        ↻ {{ __('Retry') }}
    </a>
@endsection
