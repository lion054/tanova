<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $page_title ?? 'Dashboard'}} - {{setting_item('site_title') ?? 'Booking Core'}}</title>

    <link rel="icon" type="image/png" href="{{ url('uploads/0000/6/2026/05/23/favicon2.png') }}"/>

    <meta name="robots" content="noindex, nofollow"/>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- Styles -->
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/flags/css/flag-icon.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{url('libs/daterange/daterangepicker.css')}}"/>
    <link href="{{ asset('themes/admin/libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/admin/libs/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/admin/dist/css/style.css') }}" rel="stylesheet">
    {!! \App\Helpers\Assets::css() !!}
    {!! \App\Helpers\Assets::js() !!}
    @include('Layout::admin.parts.global-script')
    <script src="{{ asset('libs/tinymce/js/tinymce/tinymce.min.js') }}"></script>
    @stack('css')

</head>
<body class="{{($enable_multi_lang ?? '') ? 'enable_multi_lang' : '' }} @if(setting_item('site_enable_multi_lang')) site_enable_multi_lang @endif">
@yield('content')

@include('Media::browser')

<!-- Heavy CSS File -->
<link href="
https://cdn.jsdelivr.net/npm/ionicons@4.6.3/dist/css/ionicons.min.css
" rel="stylesheet">

<!-- Scripts -->
{!! \App\Helpers\Assets::css(true) !!}
<script src="{{ asset('libs/pusher.min.js') }}"></script>
<script src="{{ asset('libs/jquery-3.6.3.min.js?_ver='.config('app.asset_version')) }}"></script>
<script src="{{ asset('themes/admin/libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js?_ver='.config('app.asset_version')) }}"></script>
<script src="{{ asset('libs/filerobot-image-editor/filerobot-image-editor.min.js?_ver='.config('app.asset_version')) }}"></script>
<script type="module" src="{{ asset('themes/admin/dist/js/app.js?_ver='.config('app.asset_version')) }}"></script>
<script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('libs/bootbox/bootbox.min.js') }}"></script>

<script src="{{url('libs/daterange/moment.min.js')}}"></script>
<script src="{{url('libs/daterange/daterangepicker.min.js?_ver='.config('app.asset_version'))}}"></script>

{!! \App\Helpers\Assets::js(true) !!}

@stack('js')

</body>
</html>
