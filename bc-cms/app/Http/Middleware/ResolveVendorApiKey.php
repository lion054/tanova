<?php

namespace App\Http\Middleware;

use App\Services\VendorContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Vendor\Models\VendorApiKey;
use Modules\Vendor\Models\VendorSubscription;
use Symfony\Component\HttpFoundation\Response;

class ResolveVendorApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        // Accept publishable/secret keys in live or test mode:
        // pk_live_ / sk_live_ / pk_test_ / sk_test_
        if (!$bearer || !preg_match('/^(pk|sk)_(live|test)_/', $bearer)) {
            return response()->json([
                'error' => ['code' => 'missing_api_key', 'message' => 'A valid vendor API key is required.'],
            ], 401);
        }

        $apiKey = VendorApiKey::findByKey($bearer);

        if (!$apiKey) {
            Log::warning('vendor_api_auth_failed', [
                'ip'  => $request->ip(),
                'ua'  => $request->userAgent(),
                'path' => $request->path(),
            ]);
            return response()->json([
                'error' => ['code' => 'invalid_api_key', 'message' => 'Invalid API key.'],
            ], 401);
        }

        if (!$apiKey->isValid()) {
            $code = !$apiKey->active
                ? 'api_key_revoked'
                : ($apiKey->expires_at?->isPast() ? 'api_key_expired' : 'rate_limit_exceeded');

            $message = match ($code) {
                'api_key_revoked'     => 'This API key has been revoked.',
                'api_key_expired'     => 'This API key has expired.',
                'rate_limit_exceeded' => 'This key has used its request allowance for the year. Upgrade your plan or ask for a higher limit.',
            };

            Log::info('vendor_api_key_blocked', [
                'key_id' => $apiKey->id,
                'vendor_id' => $apiKey->vendor_id,
                'code'   => $code,
                'ip'     => $request->ip(),
            ]);

            return response()->json(['error' => ['code' => $code, 'message' => $message]], 403);
        }

        // ── Read-only gate for publishable keys ────────────────────────────────
        // Publishable (pk_live_) keys are meant for a vendor's website front-end,
        // so they may ONLY perform safe, read-only requests. Any write is rejected.
        if ($apiKey->isPublishable() && !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return response()->json([
                'error' => [
                    'code'    => 'read_only_key',
                    'message' => 'This is a publishable (read-only) key. Use a secret key (sk_live_) from your server for write operations.',
                ],
            ], 403);
        }

        $vendor = $apiKey->vendor;

        // ── Subscription gate ─────────────────────────────────────────────────
        // An active subscription is required to use the LIVE API.
        // Test (sandbox) keys are exempt so developers can build before subscribing.
        // A grace period (setting_item vendor_subscription_grace_period) is respected
        // via vendor_plan_enable, which already adds grace days to vendor_plan_expires_at.
        if (!$apiKey->isTest() && !$vendor->vendor_plan_enable) {
            $active = VendorSubscription::activeForVendor($vendor->id);

            if (!$active) {
                Log::info('vendor_api_subscription_required', [
                    'vendor_id' => $vendor->id,
                    'key_id'    => $apiKey->id,
                    'ip'        => $request->ip(),
                ]);

                return response()->json([
                    'error' => [
                        'code'    => 'subscription_required',
                        'message' => 'Your subscription has expired or is inactive. Please renew to continue using the API.',
                    ],
                ], 402);
            }
        }

        // Set vendor context — all scopes and controllers read from here
        VendorContext::set($vendor);
        VendorContext::setMode($apiKey->isTest() ? 'test' : 'live');
        if ($apiKey->isTest()) {
            // A test key never sends real e-mail: anything the request triggers is captured, not delivered.
            config(['mail.default' => 'array']);
        }

        // Set the authenticated user so auth()->user() calls work throughout the app
        Auth::setUser($vendor);

        // Attach resolved key to request for TrackVendorApiUsage middleware
        $request->attributes->set('resolved_api_key', $apiKey);

        return $next($request);
    }
}
