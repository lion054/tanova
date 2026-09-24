<?php

    return [

        /*
        |--------------------------------------------------------------------------
        | Cross-Origin Resource Sharing (CORS) Configuration
        |--------------------------------------------------------------------------
        |
        | Here you may configure your settings for cross-origin resource sharing
        | or "CORS". This determines what cross-origin operations may execute
        | in web browsers. You are free to adjust these settings as needed.
        |
        | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        |
        */

        // Everything under api/ EXCEPT the vendor API (api/v/*): that one answers CORS itself, per business, from the origins
    // each vendor registered (App\Http\Middleware\VendorCors). A wildcard here would let any website read a leaked publishable key's data.
    // A new top-level api route group must be added below (a test fails until it is).
    'paths' => [
        'sanctum/csrf-cookie',
        'api/auth/*', 'api/user', 'api/user/*', 'api/vendor/*', 'api/mcp/*', 'api/booking/*', 'api/configs*', 'api/forgot-password', 'api/reset-password', 'api/gateways*', 'api/geo/*',
        'api/home-page*', 'api/location*', 'api/media*', 'api/news*', 'api/services*', 'api/sitemap*',
        'api/tour/*', 'api/hotel/*', 'api/car/*', 'api/space/*', 'api/event/*', 'api/boat/*', 'api/flight/*', 'api/visa/*',
    ],

        'allowed_methods' => ['*'],

        // API security is enforced by API keys, not by origin.
        // VendorCors middleware additionally restricts /api/v/* to registered origins for browser clients.
        // Server-to-server calls (the primary vendor integration pattern) bypass CORS entirely.
        'allowed_origins' => ['*'],

        'allowed_origins_patterns' => [],

        'allowed_headers' => ['*'],

        'exposed_headers' => [],

        'max_age' => 0,

        'supports_credentials' => false,

    ];