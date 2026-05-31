@extends('errors.layout')

@section('title', 'Access Forbidden')
@section('code', '403')
@section('code-color', '#f59e0b')
@section('icon-bg', '#fffbeb')
@section('badge-bg', '#fffbeb')
@section('badge-color', '#d97706')
@section('badge-label', 'Forbidden')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
</svg>
@endsection

@section('title', 'Access Forbidden')
@section('message', "You don't have permission to access this page. If you believe this is a mistake, please contact support or log in with a different account.")

@section('actions')
    <a href="{{ url('/') }}" class="err-btn" style="background:#f59e0b;color:#fff;box-shadow:0 2px 8px rgba(245,158,11,.3)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
        Go Home
    </a>
    @guest
    <a href="{{ route('login') }}" class="err-btn err-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
        Sign In
    </a>
    @endguest
@endsection
