<?php

namespace App\Http\Middleware;

use App\Services\VendorContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Vendor\Models\VendorAllowedOrigin;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dynamic CORS for /api/v/* vendor routes.
 *
 * Allows cross-origin requests only from origins the vendor has registered.
 * Falls back to allowing APP_URL (for portal/testing) and the Tsoka domain.
 * Must run AFTER ResolveVendorApiKey so VendorContext is populated.
 */
class VendorCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin', '');

        $allowed = $this->isAllowed($origin);

        // Handle preflight — browser sends OPTIONS before the real request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        if ($allowed && $origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With, Idempotency-Key, X-Customer-Token, Tsoka-Version, If-None-Match');
            $response->headers->set('Access-Control-Expose-Headers', 'ETag, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, Retry-After, Idempotent-Replayed, X-Tsoka-Mode, X-Tsoka-Version');
            $response->headers->set('Access-Control-Max-Age', '86400');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    private function isAllowed(string $origin): bool
    {
        if (!$origin) {
            return true; // server-to-server — no Origin header, always allow
        }

        // Always allow the platform's own origin (portal, testing)
        $appUrl = rtrim(config('app.url'), '/');
        if ($origin === $appUrl) {
            return true;
        }

        // Allow any origin registered by the authenticated vendor (cached 5 min)
        $vendor = VendorContext::get();
        if ($vendor) {
            $cacheKey = "vendor_cors:{$vendor->id}:" . md5($origin);
            $allowed  = Cache::remember($cacheKey, 300, fn() =>
                VendorAllowedOrigin::isAllowed($vendor->id, $origin)
            );
            if ($allowed) {
                return true;
            }
        }

        // Fallback: allow wildcard origins configured in env (for dev environments)
        $extra = array_filter(explode(',', env('VENDOR_API_ALLOWED_ORIGINS', '')));
        foreach ($extra as $pattern) {
            if (fnmatch(trim($pattern), $origin)) {
                return true;
            }
        }

        return false;
    }
}
