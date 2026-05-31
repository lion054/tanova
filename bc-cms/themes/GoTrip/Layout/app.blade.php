<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $html_class ?? '' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Professional Branding & Meta Tags -->
    <meta name="theme-color" content="#FF6B35">
    <meta name="msapplication-TileColor" content="#FF6B35">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Tsoka Travel">
    <link rel="manifest" href="{{ url('site.webmanifest') }}">
    <link rel="icon" type="image/svg+xml" href="{{ url('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ url('apple-touch-icon.png') }}">

    @php $favicon = setting_item('site_favicon'); @endphp
    @if ($favicon)
        @php
            $file = (new \Modules\Media\Models\MediaFile())->findById($favicon);
        @endphp
        @if (!empty($file))
            <link rel="icon" type="{{ $file['file_type'] }}" href="{{ asset('uploads/' . $file['file_path']) }}" />
        @endif
    @endif

    @include('Layout::parts.seo-enhanced')
    <link href="{{ asset('themes/gotrip/css/vendors.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gotrip/css/main.css') }}" rel="stylesheet">
    <!-- Premium Design System -->
    <link href="{{ asset('custom/premium.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/icofont/icofont.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/daterange/daterangepicker.css') }}">
    <link href="{{ asset('libs/carousel-2/owl.carousel.css') }}" rel="stylesheet">
    <!-- Tsoka Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{--ink:#1a0900;--bronze:#eea61a;--bone:#f9f5ec;--paper:#fefdf9;}
        body{font-family:'Instrument Sans',system-ui,sans-serif!important;background-color:var(--bone)!important;color:var(--ink)!important;}
        h1,h2,h3,h4,h5,h6,.fw-500,.fw-600,.fw-700{font-family:'DM Sans',system-ui,sans-serif!important;}
        :focus-visible{outline:2px solid var(--bronze);outline-offset:3px;border-radius:4px;}
    </style>
    <link rel="stylesheet"
        href="{{ asset('themes/gotrip/dist/frontend/css/app.css?_v=' . config('app.asset_version')) }}">

    @if (setting_item('cookie_agreement_type') == 'cookie_consent')
        <link rel="stylesheet" href="{{ asset('libs/cookie-consent/cookieconsent.css') }}" media="print"
            onload="this.media='all'">
    @endif

    {!! \App\Helpers\Assets::css() !!}
    {!! \App\Helpers\Assets::js() !!}
    @include('Layout::parts.global-script')
    <!-- Styles -->
    @stack('css')
    {{-- Custom Style --}}
    <link href="{{ route('core.style.customCss') }}" rel="stylesheet">
    @if (setting_item_with_lang('enable_rtl'))
        <link href="{{ asset('themes/gotrip/dist/frontend/css/rtl.css') }}" rel="stylesheet">
    @endif
    @if (!is_demo_mode())
        {!! setting_item('head_scripts') !!}
        {!! setting_item_with_lang_raw('head_scripts') !!}
    @endif
</head>
<?php
// Show Tsoka user bar for ALL authenticated users on every page
$hasAdminbar = Auth::check() && empty(request('preview'));
?>
<body
    class="frontend-page {{ $hasAdminbar ? 'has-adminbar' : '' }} {{ !empty($row->header_style) ? 'header-' . $row->header_style : 'header-normal' }} {{ $body_class ?? '' }} {{ setting_item_with_lang('enable_rtl') ? 'is-rtl' : '' }} {{ is_api() ? 'is_api' : '' }}">
    @if (!is_demo_mode())
        {!! setting_item('body_scripts') !!}
        {!! setting_item_with_lang_raw('body_scripts') !!}
    @endif
    @if ($hasAdminbar)
        @include('Layout::parts.adminbar')
    @endif
    <div class="bc_wrap overflow-hidden">
        @if (!empty($row))
            @php $hideHeaderMargin = ['transparent','transparent_v2','transparent_v3','transparent_v4','transparent_v5','transparent_v6','transparent_v7','transparent_v8','transparent_v9'] @endphp
            @if (!in_array($row->header_style, $hideHeaderMargin))
                <div class="header-margin"></div>
            @endif
        @else
            @if (empty($hide_header_margin))
                <div class="header-margin"></div>
            @endif
        @endif
        @include('Layout::parts.preload')
        @include('Layout::parts.header')
        @yield('content')
        @include('Layout::parts.footer')
    </div>
    @if (!is_demo_mode())
        {!! setting_item('footer_scripts') !!}
        {!! setting_item_with_lang_raw('footer_scripts') !!}
    @endif

</body>

</html>
