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

        'paths' => ['api/*', 'sanctum/csrf-cookie'],

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