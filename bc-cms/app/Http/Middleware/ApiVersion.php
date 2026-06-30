<?php

namespace App\Http\Middleware;

use App\Services\VendorContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Date-based API versioning (Stripe-style). Clients may pin a version with the
 * `Tsoka-Version` request header; the resolved version is echoed back in
 * `X-Tsoka-Version`. Unknown/blank versions fall back to CURRENT.
 */
class ApiVersion
{
    public const CURRENT   = '2026-06-30';
    public const SUPPORTED = ['2026-06-30'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = trim((string) $request->header('Tsoka-Version', self::CURRENT));
        $version   = in_array($requested, self::SUPPORTED, true) ? $requested : self::CURRENT;

        VendorContext::setVersion($version);

        $response = $next($request);
        $response->headers->set('X-Tsoka-Version', $version);

        return $response;
    }
}
