@extends('errors.layout')

@section('title', 'Session Expired')
@section('code', '419')
@section('code-color', '#0ea5e9')
@section('icon-bg', '#f0f9ff')
@section('badge-bg', '#f0f9ff')
@section('badge-color', '#0284c7')
@section('badge-label', 'Session Expired')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#0ea5e9" stroke-width="1.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>
@endsection

@section('title', 'Session Expired')
@section('message', 'Your session has timed out for security. This usually happens after a period of inactivity. Simply go back and try again — your work may still be there.')

@section('actions')
    <a href="javascript:history.back()" class="err-btn" style="background:#0ea5e9;color:#fff;box-shadow:0 2px 8px rgba(14,165,233,.3)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Go Back & Retry
    </a>
    <a href="{{ url('/') }}" class="err-btn err-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
        Go Home
    </a>
@endsection
