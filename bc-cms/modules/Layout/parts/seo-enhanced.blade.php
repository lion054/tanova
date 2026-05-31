@php
$page_url = request()->url();
$page_title = $seo_meta['seo_title'] ?? $seo_meta['service_title'] ?? $page_title ?? setting_item_with_lang('site_title', false, 'Tsoka Travel');
$page_desc = $seo_meta['seo_desc'] ?? $seo_meta['service_desc'] ?? setting_item_with_lang('site_desc');
$page_image = $seo_meta['seo_image'] ?? $seo_meta['service_image'] ?? '';
$site_name = setting_item_with_lang('site_title', false, 'Tsoka Travel');

// Fallback if no image
if (empty($page_image)) {
    $page_image = asset('images/og-default.png');
}
@endphp

<!-- ========== ESSENTIAL SEO ========== -->
<title>{{ $page_title }}</title>
<meta name="description" content="{{ $page_desc }}">
<meta name="keywords" content="travel, tours, hotels, destinations, bookings">
<meta name="author" content="{{ $site_name }}">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<link rel="canonical" href="{{ $page_url }}">

<!-- ========== GEOGRAPHIC META ========== -->
<meta name="geo.placename" content="Worldwide">
<meta name="geo.region" content="Worldwide">
<meta name="ICBM" content="0,0">

<!-- ========== OPEN GRAPH (Facebook) ========== -->
<meta property="og:url" content="{{ $page_url }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $page_title }}">
<meta property="og:description" content="{{ $page_desc }}">
<meta property="og:image" content="{{ $page_image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="{{ $site_name }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

<!-- ========== TWITTER CARD ========== -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $page_title }}">
<meta name="twitter:description" content="{{ $page_desc }}">
<meta name="twitter:image" content="{{ $page_image }}">
<meta name="twitter:site" content="@TsokaTravelCo">

<!-- ========== STRUCTURED DATA (JSON-LD) ========== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TravelAgency",
  "name": "{{ $site_name }}",
  "url": "{{ url('/') }}",
  "logo": "{{ asset('images/logo.png') }}",
  "description": "{{ $page_desc }}",
  "sameAs": [
    "https://www.facebook.com/TsokaTravel",
    "https://twitter.com/TsokaTravelCo",
    "https://www.instagram.com/TsokaTravel",
    "https://www.linkedin.com/company/tsoka-travel"
  ],
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ setting_item('company_address') }}",
    "addressCountry": "{{ setting_item('company_country') }}"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "Customer Service",
    "telephone": "{{ setting_item('company_phone') }}",
    "email": "{{ setting_item('company_email') }}"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "2500"
  }
}
</script>

@if(!empty($seo_meta) && $seo_meta['seo_index'] == 0)
<meta name="robots" content="noindex, nofollow">
@endif

<!-- ========== THEME & APPEARANCE ========== -->
<meta name="theme-color" content="#2563eb">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<!-- ========== PRECONNECT & DNS PREFETCH ========== -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- ========== FAVICONS (ALL FORMATS) ========== -->
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicons/favicon-96x96.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicons/favicon-192x192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/favicons/apple-touch-icon-180x180.png') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">

<!-- ========== MOBILE OPTIMIZATION ========== -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">

<!-- ========== BROWSER OPTIMIZATION ========== -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

