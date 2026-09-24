<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Pro\Tanova\Services\AppCatalogue;

/**
 * GET /api/v/destinations lists the places; GET /api/v/catalogue — everything one place offers, in the shape the LuxSav
 * app works with, in a single request.
 *
 *   location_id   required (or `location`, a name)
 *   types         optional, comma separated: activities,packages,stays,transports,restaurants
 *   updated_since optional, a date/time: only rows changed since then
 */
class VendorCatalogueController extends Controller
{
    private const TYPES = ['activities', 'packages', 'stays', 'transports', 'restaurants'];

    /** GET /api/v/destinations — the places this vendor has something published in. */
    public function destinations(): JsonResponse
    {
        return response()->json([
            'data' => (new AppCatalogue((int) VendorContext::id()))->destinations(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $locationId = $request->integer('location_id');
        if (!$locationId && $request->filled('location')) {
            $locationId = (int) Location::where('name', $request->input('location'))->value('id');
        }
        if (!$locationId) {
            return response()->json(['error' => ['code' => 'location_required', 'message' => 'Pass location_id (or location).']], 422);
        }

        $types = null;
        if ($request->filled('types')) {
            $types = array_values(array_intersect(self::TYPES, array_map('trim', explode(',', (string) $request->input('types')))));
        }

        $data = (new AppCatalogue((int) VendorContext::id()))->forLocation($locationId, $types, $request->input('updated_since'));
        if (!$data) {
            return response()->json(['error' => ['code' => 'location_not_found', 'message' => 'No such location.']], 404);
        }

        return response()->json(['data' => $data]);
    }
}
