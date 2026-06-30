<?php

namespace App\Http\Middleware;

use App\Services\VendorContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Vendor\Models\VendorApiKey;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds standard response headers to the vendor API:
 *   - X-Tsoka-Mode:        live | test
 *   - X-RateLimit-Limit / -Remaining / -Reset  (annual cap; omitted for test/unlimited)
 *   - ETag + Cache-Control on successful GETs, with 304 Not Modified support.
 */
class VendorApiResponseHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Tsoka-Mode', VendorContext::mode());

        /** @var VendorApiKey|null $apiKey */
        $apiKey = $request->attributes->get('resolved_api_key');
        if ($apiKey && !$apiKey->isTest() && $apiKey->rate_limit > 0) {
            $used      = $apiKey->annualUsageCount();
            $remaining = max(0, $apiKey->rate_limit - $used);
            $response->headers->set('X-RateLimit-Limit', (string) $apiKey->rate_limit);
            $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
            $response->headers->set('X-RateLimit-Reset', (string) now()->endOfYear()->timestamp);
        }

        // Conditional caching for safe reads.
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $content = $response->getContent();
            if (is_string($content) && $content !== '') {
                $etag = '"' . md5($content) . '"';
                $response->headers->set('ETag', $etag);
                $response->headers->set('Cache-Control', 'private, max-age=30');

                $ifNoneMatch = $request->headers->get('If-None-Match');
                if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
                    $response->setNotModified(); // 304, drops body
                }
            }
        }

        return $response;
    }
}
