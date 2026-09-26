<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $html_class ?? '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d0d10">
    <link rel="icon" type="image/svg+xml" href="{{ url('favicon.svg') }}">
    @php $pg = $page ?? []; @endphp
    <title>{{ ($pg['title'] ?? '') ? $pg['title'] . ' · ' : '' }}{{ setting_item('site_title') ?: 'Tanova' }}</title>
    @if (!empty($pg['preview']))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(($pg['short'] ?? '') ?: trim(preg_replace('/\s+/', ' ', strip_tags(clean($pg['overview'] ?? '')))), 160) }}">
        <link rel="canonical" href="{{ $pg['url'] ?? url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $pg['title'] ?? '' }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(($pg['short'] ?? '') ?: trim(preg_replace('/\s+/', ' ', strip_tags(clean($pg['overview'] ?? '')))), 200) }}">
        @if (!empty($pg['gallery'][0]['large']))<meta property="og:image" content="{{ $pg['gallery'][0]['large'] }}">@endif
    @endif
    {{-- The booking box still runs on the platform's own scripts and styles; the page around it is new (see the style block below). --}}
    <link href="{{ asset('themes/gotrip/css/vendors.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gotrip/css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/icofont/icofont.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/daterange/daterangepicker.css') }}">
    <link href="{{ asset('libs/carousel-2/owl.carousel.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('themes/gotrip/dist/frontend/css/app.css?_v=' . config('app.asset_version')) }}">
    {!! \App\Helpers\Assets::css() !!}
    {!! \App\Helpers\Assets::js() !!}
    @include('Layout::parts.global-script')
    @stack('css')
    @include('Layout::public.style')
</head>
<body class="pv-body {{ $body_class ?? '' }}">
    @include('Layout::public.header')
    @if (!empty($pg['preview']))
        <div class="pv-preview">
            <div class="pv-wrap">
                <b>{{ __('Preview: not published yet.') }}</b>
                {{ __('Only you and your team can see this page. Guests get a "not found" until you publish it.') }}
                @if (!empty($edit_url))<a href="{{ $edit_url }}">{{ __('Edit this service') }}</a>@endif
            </div>
        </div>
    @endif
    <main class="pv">
        @yield('content')
    </main>
    @include('Layout::public.footer')
    @include('Layout::parts.login-register-modal')

    <script src="{{ asset('libs/lodash.min.js') }}"></script>
    <script src="{{ asset('libs/jquery-3.6.3.min.js') }}"></script>
    <script src="{{ asset('libs/vue/vue' . (!env('APP_DEBUG') ? '.min' : '') . '.js') }}"></script>
    <script type="text/javascript" src="{{ asset('themes/gotrip/libs/bs/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('libs/bootbox/bootbox.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('themes/gotrip/js/vendors.js') }}"></script>
    <script type="text/javascript" src="{{ asset('themes/gotrip/js/main.js?_ver=' . config('app.asset_version')) }}"></script>
    {!! App\Helpers\MapEngine::scripts() !!}
    <script src="{{ asset('libs/carousel-2/owl.carousel.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('libs/daterange/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('libs/daterange/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('themes/gotrip/dist/frontend/js/gotrip.js?_ver=' . config('app.asset_version')) }}"></script>
    @php \App\Helpers\ReCaptchaEngine::scripts() @endphp
    @stack('js')
    @include('Layout::public.script')
</body>
</html>
