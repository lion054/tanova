<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '*/gateway_callback/*',
            '*/callback/*',
            '*/order/confirm/*',
            'tourpay/notify/*',   // gateways POST here; nothing in the message is trusted, the gateway is asked again
        ]);

        // Redirect to installer if not installed
        $middleware->append(\App\Http\Middleware\RedirectToInstaller::class);

        // Clears static VendorContext after every request — prevents cross-tenant
        // leakage under Octane/Swoole where static properties survive between requests.
        $middleware->append(\App\Http\Middleware\ClearVendorContext::class);

        $middleware->web([
            \App\Http\Middleware\FrontendGuard::class,
            \App\Http\Middleware\RedirectForMultiLanguage::class,
            \App\Http\Middleware\SetLanguageForAdmin::class,
            \App\Http\Middleware\SetCurrentCurrency::class,
            \App\Http\Middleware\RequireChangePassword::class,
        ]);
        $middleware->api([
            \App\Http\Middleware\MayAuthenticateWithSanctum::class,
        ], [
            \App\Http\Middleware\SetLanguageForApi::class,
            \App\Http\Middleware\RequireChangePassword::class,
        ]);

        $middleware->alias([
            "dashboard"            => \App\Http\Middleware\Dashboard::class,
            "translation_manager"  => \App\Http\Middleware\TranslationManager::class,
            "system_log_view"      => \App\Http\Middleware\CheckForLogPermission::class,
            "set_language_for_api" => \App\Http\Middleware\SetLanguageForApi::class,
            "pro_plan"             => \App\Pro\Middlewares\ProPlan::class,
            "api.scope"            => \App\Http\Middleware\RequireApiScope::class,
        ]);
        // Note: vendor-api rate limiter is registered in AppServiceProvider::boot()

        // Sanctum Middleware
        $middleware->statefulApi();
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        \App\Support\ApiErrors::register($exceptions);
    })->create();
