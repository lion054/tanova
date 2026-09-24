<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pro\Tanova\Models\TanovaTransport;

/**
 * Ground transport for a location's Tanova catalog. Not part of
 * TanovaEngine::generate()'s output (trip generation only covers activities
 * + accommodation) — transport is location-scoped, not trip-scoped, so it's
 * its own small lookup rather than something bundled into a generated trip.
 */
class VendorTanovaTransportController extends Controller
{
    /** GET /api/v/tanova/transports?location_id= */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['location_id' => 'required|integer']);

        $vendorId = VendorContext::id();

        $transports = TanovaTransport::with('activeOptions')
            ->published()
            ->forLocation((int) $request->integer('location_id'))
            ->visibleTo($vendorId)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $transports]);
    }
}
