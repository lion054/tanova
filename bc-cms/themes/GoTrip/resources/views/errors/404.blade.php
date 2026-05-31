@extends('errors::layout')

@section('icon', '🗺️')
@section('code', '404')
@section('title', __('Page Not Found'))
@section('message', __('This page has wandered off the trail. It may have been moved, renamed, or removed. Let\'s get you back on the right path.'))

@section('actions')
    <a href="/" class="btn-primary">
        ← {{ __('Back to Home') }}
    </a>
    <a href="javascript:history.back()" class="btn-secondary">
        {{ __('Go Back') }}
    </a>
@endsection
