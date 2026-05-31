<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'release' => env('APP_VERSION', '1.0.0'),

    'environment' => env('APP_ENV'),

    // Capture errors
    'breadcrumbs' => [
        'sql_bindings' => true,
        'logs' => true,
        'user_interactions' => true,
        'http_client_requests' => true,
    ],

    // Tracing - sample 20% of transactions in production
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    // Performance monitoring
    'attach_stacktrace' => true,
    'max_breadcrumbs' => 50,

    // Sensitive data
    'send_default_pii' => false,

    'request_headers' => true,
    'request_body' => 'small',

    'before_send' => null,

    'integrations' => [
        \Sentry\Laravel\Integration\RequestIntegration::class,
        \Sentry\Laravel\Integration\DatabaseIntegration::class,
        \Sentry\Laravel\Integration\CacheIntegration::class,
        \Sentry\Laravel\Integration\QueueIntegration::class,
    ],

    'excluded_paths' => [
        'health',
        'horizon',
    ],
];
