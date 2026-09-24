<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Vendor\Services\Shelves;

/**
 * GET /api/v/services/tours/trending and /bestsellers[?limit=N]: the vendor's own shelves,
 * ranked from real bookings with the vendor's pins first. Each tour carries the same
 * fields as the tour list, plus a "shelf" block saying why it is there.
 */
class VendorShelvesController extends Controller
{
    public function trending(Request $request, Shelves $shelves, VendorServiceController $services): JsonResponse
    {
        return $this->shelf('trending', $request, $shelves, $services);
    }

    public function bestsellers(Request $request, Shelves $shelves, VendorServiceController $services): JsonResponse
    {
        return $this->shelf('bestseller', $request, $shelves, $services);
    }

    private function shelf(string $shelf, Request $request, Shelves $shelves, VendorServiceController $services): JsonResponse
    {
        $rows = $shelves->shelf($shelf, (int) VendorContext::id(), min(max($request->integer('limit', 10), 1), 30));
        $tours = $rows->pluck('tour');
        $services->presentTours($tours);

        return response()->json(['data' => $rows->map(function ($r) {
            $t = $r['tour'];
            $t->shelf = ['reason' => $r['reason'], 'pinned' => $r['pinned'], 'bookings_total' => $r['sales_total'], 'bookings_30d' => $r['sales_30d']];

            return $t;
        })->values()]);
    }
}
