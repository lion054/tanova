@extends('errors.layout')

@section('title', 'Server Error')
@section('code', '500')
@section('code-color', '#ef4444')
@section('icon-bg', '#fef2f2')
@section('badge-bg', '#fef2f2')
@section('badge-color', '#dc2626')
@section('badge-label', 'Server Error')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="1.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
</svg>
@endsection

@section('title', 'Internal Server Error')
@section('message', "Something went wrong on our end. Our team has been notified and we're working to fix it. Please try again in a few minutes.")

@section('actions')
    <a href="javascript:location.reload()" class="err-btn" style="background:#ef4444;color:#fff;box-shadow:0 2px 8px rgba(239,68,68,.3)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
        Try Again
    </a>
    <a href="{{ url('/') }}" class="err-btn err-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
        Go Home
    </a>
@endsection
