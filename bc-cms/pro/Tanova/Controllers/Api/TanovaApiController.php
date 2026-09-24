<?php

namespace Pro\Tanova\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pro\Tanova\Models\TanovaTrip;
use Pro\Tanova\Services\TanovaEngine;

class TanovaApiController extends Controller
{
    protected TanovaEngine $engine;

    public function __construct()
    {
        $this->engine = new TanovaEngine();
    }

    /** GET /api/tanova/trips — recent trips for the authenticated user or vendor */
    public function index(Request $request): JsonResponse
    {
        $query = TanovaTrip::active()->latest();

        if (VendorContext::active()) {
            $query->where('vendor_id', VendorContext::id());
        } else {
            $query->recent(24)->where('user_id', Auth::id());
        }

        $trips = $query->paginate(
            min($request->integer('per_page', 15), 100),
            ['id', 'title', 'destination', 'start_date', 'end_date', 'guests',
             'trip_type', 'estimated_price', 'currency', 'status', 'created_at']
        );

        return response()->json($trips);
    }

    /** GET /api/tanova/trips/{id} — full itinerary */
    public function show(TanovaTrip $trip): JsonResponse
    {
        if (VendorContext::active()) {
            abort_if($trip->vendor_id !== VendorContext::id(), 403);
        } else {
            abort_if($trip->user_id !== Auth::id(), 403);
        }

        return response()->json(['data' => $trip]);
    }

    /** POST /api/tanova/generate — Tanova deterministic trip generation */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'guests'      => 'required|integer|min:1',
            'place_id'    => 'nullable|integer',
            'budget'      => 'nullable|in:budget,mid-range,luxury',
            'trip_type'   => 'nullable|string|max:100',
            'notes'       => 'nullable|string|max:1000',
        ]);

        // Map budget tier to numeric value
        $budgetMap = [
            'budget' => 2000,
            'mid-range' => 5000,
            'luxury' => 10000,
        ];

        try {
            // Use Tanova Engine to generate from real database data
            $generated = $this->engine->generate([
                'location_id' => $validated['place_id'] ?? 6, // Default to Victoria Falls
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'guests' => $validated['guests'],
                'budget' => $budgetMap[$validated['budget'] ?? 'mid-range'],
                'stay_type' => null,
                'vendor_id' => VendorContext::id(),
            ]);
        } catch (\Exception $e) {
            \Log::warning("Tanova generation error: " . $e->getMessage());
            return response()->json([
                'error' => [
                    'code' => 'trip_generation_failed',
                    'message' => 'Trip generation encountered an issue. Accommodations may be missing for this destination.',
                    'details' => $e->getMessage(),
                ],
                'warning' => 'Please add accommodations for this location to enable full trip generation.',
            ], 500);
        }

        if (!$generated) {
            return response()->json([
                'error' => ['code' => 'trip_generation_failed', 'message' => 'Trip generation failed. Check destination and dates.'],
                'warning' => 'No valid packages could be generated. Accommodations may be missing for this destination.',
            ], 500);
        }

        $trip = TanovaTrip::create([
            // In vendor context Auth::id() = vendor — guest trips have no registered user_id
            'user_id'         => VendorContext::active() ? null : Auth::id(),
            'vendor_id'       => VendorContext::id(),
            'create_user'     => Auth::id(),
            'title'           => $generated['title'] ?? "Trip to {$validated['destination']}",
            'destination'     => $validated['destination'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'guests'          => $validated['guests'],
            'trip_type'       => $generated['trip_type'] ?? ($validated['trip_type'] ?? null),
            // TanovaEngine::generate() returns the package list under 'packages', not
            // 'itinerary' — this key was wrong, so every generated trip was persisted
            // with an empty itinerary and formatTripResponse() (which reads
            // $trip->itinerary[0] as the first package) always returned packages: 0.
            'itinerary'       => $generated['packages'] ?? [],
            'daily_weather'   => $generated['daily_weather'] ?? [],
            'estimated_price' => $generated['packages'][0]['total_cost'] ?? null,
            'currency'        => $generated['currency'] ?? 'USD',
            'status'          => TanovaTrip::STATUS_CREATED,
            'prompt'          => json_encode($validated),
        ]);

        return response()->json([
            'data' => $this->formatTripResponse($trip),
        ], 201);
    }

    /** GET /api/tanova/trips/{id}/replan — Get Claude's replan suggestions */
    public function replan(TanovaTrip $trip): JsonResponse
    {
        if (VendorContext::active()) {
            abort_if($trip->vendor_id !== VendorContext::id(), 403);
        } else {
            abort_if($trip->user_id !== Auth::id(), 403);
        }

        $replanService = new \Pro\Tanova\Services\ReplanService();
        $suggestions = $replanService->getSuggestions($trip);

        return response()->json([
            'status' => 1,
            'data' => $suggestions,
        ]);
    }

    /** Format trip response with all details */
    protected function formatTripResponse(TanovaTrip $trip): array
    {
        $pkg = $trip->itinerary[0] ?? null;

        return [
            'id' => $trip->id,
            'title' => $trip->title,
            'destination' => $trip->destination,
            'dates' => [
                'start' => $trip->start_date->format('Y-m-d'),
                'end' => $trip->end_date->format('Y-m-d'),
                'nights' => $trip->nightCount(),
            ],
            'guests' => $trip->guests,
            'status' => $trip->status,
            'pricing' => [
                'estimated' => (float) ($trip->estimated_price ?? 0),
                'currency' => $trip->currency,
            ],
            'packages' => count($trip->itinerary ?? []),
            'first_package' => $pkg ? [
                'total_cost' => $pkg['total_cost'] ?? 0,
                'activity_cost' => $pkg['activity_cost'] ?? 0,
                'stay_cost' => $pkg['stay_cost'] ?? 0,
                'days' => count($pkg['itinerary'] ?? []),
            ] : null,
            'weather' => $trip->daily_weather ?? [],
            'created_at' => $trip->created_at->toIso8601String(),
            // Built defensively: the vendor route group exposes show but has no
            // replan endpoint, so a hard route() call here 500s the whole response.
            'links' => array_filter([
                'self'   => \Route::has('api.v.tanova.show') ? route('api.v.tanova.show', $trip->id) : null,
                'replan' => \Route::has('api.v.tanova.replan') ? route('api.v.tanova.replan', $trip->id) : null,
            ]),
        ];
    }
}
