<?php

namespace App\Http\Middleware;

use App\Services\VendorContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clears static VendorContext after every request.
 *
 * Registered as a terminating middleware so it runs after the response is sent.
 * Critical for long-lived process runtimes (Octane/Swoole/RoadRunner) where
 * static properties persist across requests on the same worker — without this,
 * a previous tenant's VendorContext leaks into the next request.
 */
class ClearVendorContext
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        VendorContext::clear();
    }
}
