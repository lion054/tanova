<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiting
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Extract API key from Authorization header
        $apiKey = $request->bearerToken();

        if (!$apiKey) {
            return response()->json(['error' => 'Missing API key'], 401);
        }

        // Rate limits per endpoint
        $limits = [
            'tanova/generate' => 30,    // 30 per minute
            'tanova/trips' => 100,      // 100 per minute
            'services' => 100,
            'bookings' => 100,
            'enquiries' => 50,          // More strict
            'concierge' => 60,
        ];

        $endpoint = $this->getEndpoint($request->path());
        $limit = $limits[$endpoint] ?? 100;
        $key = "api:{$apiKey}:{$endpoint}";

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        $this->limiter->hit($key, 60); // 1 minute window

        $response = $next($request);

        return $response
            ->header('X-RateLimit-Limit', $limit)
            ->header('X-RateLimit-Remaining', max(0, $limit - $this->limiter->attempts($key)))
            ->header('X-RateLimit-Reset', now()->addMinute()->timestamp);
    }

    private function getEndpoint(string $path): string
    {
        $segments = explode('/', trim($path, '/'));

        // Skip 'api' and 'v' segments
        $relevant = array_slice($segments, 2);

        if (count($relevant) > 0) {
            return $relevant[0];
        }

        return 'default';
    }
}
