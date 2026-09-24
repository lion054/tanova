<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * One error shape for everything under /api/v/:
 *   { "error": { "code": "validation_failed", "message": "...", "fields": { "email": ["..."] } } }
 * A 422 also keeps the older top-level "message" and "errors", so clients written against the
 * default framework body keep working.
 */
class ApiErrors
{
    public static function register(Exceptions $exceptions): void
    {
        $api = fn (Request $r) => $r->is('api/v/*');

        $exceptions->render(function (ValidationException $e, Request $r) use ($api) {
            if (!$api($r)) {
                return null;
            }
            $first = collect($e->errors())->flatten()->first() ?: 'The data sent is not valid.';

            return response()->json([
                'error'   => ['code' => 'validation_failed', 'message' => $first, 'fields' => $e->errors()],
                'message' => $first,
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $r) use ($api) {
            return $api($r) ? response()->json(['error' => ['code' => 'not_found', 'message' => 'That does not exist.']], 404) : null;
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $r) use ($api) {
            return $api($r) ? response()->json(['error' => ['code' => 'method_not_allowed', 'message' => 'That method is not allowed here.']], 405) : null;
        });

        $exceptions->render(function (AuthorizationException $e, Request $r) use ($api) {
            return $api($r) ? response()->json(['error' => ['code' => 'forbidden', 'message' => 'You are not allowed to do that.']], 403) : null;
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $r) use ($api) {
            return $api($r) ? response()->json(['error' => ['code' => 'unauthenticated', 'message' => 'Please sign in.']], 401) : null;
        });

        // abort(403), the request-rate limit and the like: keep their status and headers (Retry-After), give them our shape.
        $exceptions->render(function (HttpExceptionInterface $e, Request $r) use ($api) {
            if (!$api($r) || in_array($e->getStatusCode(), [404, 405], true)) {
                return null;
            }
            $status = $e->getStatusCode();
            [$code, $message] = match (true) {
                $status === 429 => ['too_many_requests', 'You are sending requests too fast. Wait for the number of seconds in the Retry-After header.'],
                $status === 403 => ['forbidden', 'You are not allowed to do that.'],
                $status === 401 => ['unauthenticated', 'Please sign in.'],
                $status === 419 => ['expired', 'That request has expired.'],
                $status >= 500 => ['server_error', 'Something went wrong on our side.'],
                default => ['request_failed', $e->getMessage() !== '' ? $e->getMessage() : 'The request could not be completed.'],
            };

            return response()->json(['error' => ['code' => $code, 'message' => $message]], $status, $e->getHeaders());
        });

        // Anything else is a bug on our side: say so plainly, without leaking internals, and give a reference.
        $exceptions->render(function (\Throwable $e, Request $r) use ($api) {
            if (!$api($r) || $e instanceof HttpExceptionInterface || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException || $e instanceof \Illuminate\Session\TokenMismatchException) {
                return null;
            }
            $ref = substr(bin2hex(random_bytes(6)), 0, 12);
            \Illuminate\Support\Facades\Log::error("api_error {$ref}: " . $e->getMessage(), ['exception' => $e, 'path' => $r->path()]);

            return response()->json(['error' => ['code' => 'server_error', 'message' => 'Something went wrong on our side. Quote this reference if you contact us.', 'reference' => $ref]], 500);
        });
    }
}
