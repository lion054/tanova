<?php

namespace Modules\Api\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Pro\Tanova\Models\TanovaRestaurant;
use Pro\Tanova\Services\RestaurantDetails;

/**
 * GET /api/v/services/restaurants — the vendor's own restaurants (TanovaRestaurant
 * is vendor-scoped), with the details that live in the description text read
 * out into fields. Filter by `location_id` (a bc_locations id) or `location`
 * (a name). Restaurants only carry a place NAME, so ids are matched by name.
 */
class VendorRestaurantController
{
    /** Names the catalogue and the locations table spell differently. */
    private const ALIASES = ['inyanga' => 'nyanga'];

    public function index(Request $request): JsonResponse
    {
        $name = $request->input('location');
        if (!$name && $request->filled('location_id')) {
            $name = Location::whereKey($request->integer('location_id'))->value('name');
        }

        $rows = TanovaRestaurant::published()
            ->orderByDesc('is_partner')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($name) {
            $want = self::key($name);
            $rows = $rows->filter(fn ($r) => self::key($r->location) === $want)->values();
        }

        return response()->json([
            'data' => [
                'data'  => $rows->map(fn ($r) => RestaurantDetails::forApi($r))->all(),
                'total' => $rows->count(),
            ],
        ]);
    }

    private static function key(?string $name): string
    {
        $k = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $name));
        return self::ALIASES[$k] ?? $k;
    }
}
