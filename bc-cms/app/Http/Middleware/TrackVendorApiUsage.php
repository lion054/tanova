<?php

namespace App\Http\Middleware;

use App\Notifications\VendorApiRateLimitWarning;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Vendor\Models\VendorApiKey;
use Symfony\Component\HttpFoundation\Response;

class TrackVendorApiUsage
{
    // Alert thresholds (percent of rate_limit)
    private const ALERT_THRESHOLDS = [80, 100];

    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        $response = $next($request);

        /** @var VendorApiKey|null $apiKey */
        $apiKey = $request->attributes->get('resolved_api_key');

        if ($apiKey) {
            $ms = (int) round((hrtime(true) - $start) / 1_000_000);

            $apiKey->recordUsage(
                $request->path(),
                $request->method(),
                $response->getStatusCode(),
                $ms,
            );

            $this->checkUsageAlerts($apiKey);
        }

        return $response;
    }

    private function checkUsageAlerts(VendorApiKey $apiKey): void
    {
        if ($apiKey->rate_limit <= 0) {
            return;
        }

        $used    = $apiKey->annualUsageCount();
        $percent = (int) round($used / $apiKey->rate_limit * 100);

        foreach (self::ALERT_THRESHOLDS as $threshold) {
            if ($percent < $threshold) {
                continue;
            }

            // Only send each threshold alert once per year per key
            $sentKey = "vendor_api_alert:{$apiKey->id}:{$threshold}:" . now()->format('Y');

            if (Cache::has($sentKey)) {
                continue;
            }

            Cache::put($sentKey, true, now()->endOfMonth());

            $vendor = $apiKey->vendor;

            if ($vendor) {
                $vendor->notify(new VendorApiRateLimitWarning(
                    $apiKey->name,
                    $used,
                    $apiKey->rate_limit,
                    $threshold,
                ));
            }
        }
    }
}
