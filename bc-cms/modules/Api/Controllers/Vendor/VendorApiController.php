<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorContext;
use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base for the vendor API controllers. Lists come back as
 * { "data": [...], "meta": { "page", "per_page", "total", "last_page" } }.
 */
abstract class VendorApiController extends Controller
{
    use ApiResponse;

    protected function vendorId(): int
    {
        return (int) VendorContext::id();
    }

    /** A page of a query, each row shaped by $shape. */
    protected function page($query, Request $request, callable $shape, array $sizes = [10, 25, 50, 100]): JsonResponse
    {
        $p = $query->paginate(ListQuery::perPage($request, $sizes, 25));

        return response()->json([
            'data' => collect($p->items())->map($shape)->values()->all(),
            'meta' => ['page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }

    /** A test key: nothing goes out to a guest (no e-mail, message or payment). */
    protected function isTest(): bool
    {
        return VendorContext::isTest();
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function conflict(string $code, string $message): JsonResponse
    {
        return $this->error($code, $message, 409);
    }

    /** "2026-10-01" or null; anything else is ignored, never trusted. */
    protected function date(Request $r, string $key): ?string
    {
        $d = ListQuery::date($r->query($key));

        return $d !== '' ? $d : null;
    }
}
