<?php

namespace App\Http\Middleware;

use App\Services\VendorContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotent writes. When a client sends an `Idempotency-Key` header on a write
 * request, the first response is stored and replayed for any retry with the same
 * key — preventing duplicate side-effects (e.g. double bookings).
 *
 * Scoped per vendor. Runs AFTER ResolveVendorApiKey (needs the vendor context).
 */
class VendorIdempotency
{
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const TABLE = 'bc_vendor_idempotency_keys';

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (!$key || !in_array($request->method(), self::WRITE_METHODS, true)) {
            return $next($request);
        }

        $vendorId = VendorContext::id();
        if (!$vendorId) {
            return $next($request);
        }

        $key  = substr(trim($key), 0, 191);
        $hash = hash('sha256', $request->method() . '|' . $request->path() . '|' . $request->getContent());

        $existing = DB::table(self::TABLE)
            ->where('vendor_id', $vendorId)
            ->where('idempotency_key', $key)
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $hash) {
                return response()->json([
                    'error' => [
                        'code'    => 'idempotency_key_reused',
                        'message' => 'This Idempotency-Key was already used with a different request. Use a new key.',
                    ],
                ], 422);
            }

            if ($existing->response_status) {
                return response($existing->response_body ?? '', $existing->response_status)
                    ->header('Content-Type', 'application/json')
                    ->header('Idempotent-Replayed', 'true');
            }

            // Reserved but no stored response yet → a concurrent request is in flight.
            return response()->json([
                'error' => [
                    'code'    => 'idempotency_in_progress',
                    'message' => 'A request with this Idempotency-Key is already being processed.',
                ],
            ], 409);
        }

        // Reserve the key (unique index makes this race-safe).
        try {
            DB::table(self::TABLE)->insert([
                'vendor_id'       => $vendorId,
                'idempotency_key' => $key,
                'method'          => $request->method(),
                'path'            => substr($request->path(), 0, 255),
                'request_hash'    => $hash,
                'created_at'      => now(),
            ]);
        } catch (\Throwable) {
            return response()->json([
                'error' => [
                    'code'    => 'idempotency_in_progress',
                    'message' => 'A request with this Idempotency-Key is already being processed.',
                ],
            ], 409);
        }

        $response = $next($request);

        // Persist the outcome so retries replay it. Only cache final responses.
        DB::table(self::TABLE)
            ->where('vendor_id', $vendorId)
            ->where('idempotency_key', $key)
            ->update([
                'response_status' => $response->getStatusCode(),
                'response_body'   => $response->getContent() ?: null,
            ]);

        return $response;
    }
}
