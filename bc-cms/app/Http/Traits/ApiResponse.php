<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Consistent JSON envelope for all vendor API responses.
 *
 * Success:  { "data": ..., "meta": ... }
 * Error:    { "error": { "code": "snake_case_code", "message": "Human message." } }
 */
trait ApiResponse
{
    protected function success(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $body = ['data' => $data];
        if ($meta) {
            $body['meta'] = $meta;
        }
        return response()->json($body, $status);
    }

    protected function created(mixed $data, string $message = ''): JsonResponse
    {
        $body = ['data' => $data];
        if ($message) {
            $body['message'] = $message;
        }
        return response()->json($body, 201);
    }

    protected function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }

    protected function notFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->error('not_found', "{$resource} not found.", 404);
    }

    protected function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return $this->error('forbidden', $message, 403);
    }
}
