<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Service;
use Modules\Hotel\Models\Hotel;
use Modules\Tour\Models\Tour;
use Modules\Car\Models\Car;
use Modules\Boat\Models\Boat;
use Modules\Event\Models\Event;

class VendorServiceController extends Controller
{
    /**
     * Check whether the vendor's current plan allows creating another service
     * of the given type. Returns a 403 JsonResponse on breach, null if allowed.
     *
     * @param string $type  post_type key e.g. 'hotel', 'tour', 'car'
     */
    private function checkPlanLimit(string $type): ?JsonResponse
    {
        $vendor = VendorContext::get();

        if (!$vendor) {
            return response()->json(['error' => ['code' => 'unauthenticated', 'message' => 'No vendor context.']], 401);
        }

        // If global plan enforcement is disabled, skip
        if (!is_enable_plan()) {
            return null;
        }

        // vendor_plan_enable accounts for grace period
        if (!$vendor->vendor_plan_enable) {
            return response()->json([
                'error' => [
                    'code'    => 'subscription_required',
                    'message' => 'An active subscription is required to create listings.',
                ],
            ], 402);
        }

        $planData = $vendor->vendorPlanData;

        // No plan meta at all → no restrictions
        if (empty($planData) || !isset($planData[$type])) {
            return null;
        }

        $meta = $planData[$type];

        // Service type not enabled on this plan
        if (empty($meta['enable'])) {
            return response()->json([
                'error' => [
                    'code'    => 'plan_service_disabled',
                    'message' => "Your plan does not include {$type} listings.",
                ],
            ], 403);
        }

        // Check maximum_create limit
        $max = $meta['maximum_create'] ?? 0;
        if ($max > 0) {
            $current = Service::where('author_id', $vendor->id)
                ->where('object_model', $type)
                ->count();

            if ($current >= $max) {
                return response()->json([
                    'error' => [
                        'code'    => 'plan_limit_reached',
                        'message' => "Your plan allows a maximum of {$max} {$type} listings. Upgrade your plan to add more.",
                    ],
                ], 403);
            }
        }

        return null;
    }

    // ── Hotels ────────────────────────────────────────────────────────────────

    public function indexHotels(Request $request): JsonResponse
    {
        $hotels = Hotel::forVendor()
            ->with(['translation', 'location'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $hotels]);
    }

    public function showHotel(int $id): JsonResponse
    {
        $hotel = Hotel::forVendor()->with(['translation', 'location', 'rooms'])->findOrFail($id);
        return response()->json(['data' => $hotel]);
    }

    public function storeHotel(Request $request): JsonResponse
    {
        if ($err = $this->checkPlanLimit('hotel')) return $err;

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'location_id' => 'nullable|integer',
            'address'     => 'nullable|string|max:500',
            'content'     => 'nullable|string',
            'status'      => 'nullable|in:publish,draft,pending',
            'star_rate'   => 'nullable|integer|min:1|max:5',
        ]);

        $data['status'] = $data['status'] ?? 'draft';

        $hotel = Hotel::create($data);

        // author_id is not in $fillable — set directly after create
        $hotel->author_id = VendorContext::id();
        $hotel->saveQuietly();

        return response()->json(['message' => 'Hotel created.', 'data' => $hotel], 201);
    }

    public function updateHotel(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::forVendor()->findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'location_id' => 'sometimes|nullable|integer',
            'address'     => 'sometimes|nullable|string|max:500',
            'content'     => 'sometimes|nullable|string',
            'status'      => 'sometimes|in:publish,draft,pending',
            'star_rate'   => 'sometimes|integer|min:1|max:5',
        ]);

        $hotel->update($data);

        return response()->json(['message' => 'Hotel updated.', 'data' => $hotel->fresh()]);
    }

    public function destroyHotel(int $id): JsonResponse
    {
        $hotel = Hotel::forVendor()->findOrFail($id);
        $hotel->delete();
        return response()->json(['message' => 'Hotel deleted.']);
    }

    // ── Tours ─────────────────────────────────────────────────────────────────

    public function indexTours(Request $request): JsonResponse
    {
        $tours = Tour::forVendor()
            ->with(['translation', 'location'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $tours]);
    }

    public function showTour(int $id): JsonResponse
    {
        $tour = Tour::forVendor()->with(['translation', 'location'])->findOrFail($id);
        return response()->json(['data' => $tour]);
    }

    public function storeTour(Request $request): JsonResponse
    {
        if ($err = $this->checkPlanLimit('tour')) return $err;

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'location_id' => 'nullable|integer',
            'content'     => 'nullable|string',
            'status'      => 'nullable|in:publish,draft,pending',
            'duration'    => 'nullable|string|max:100',
            'price'       => 'nullable|numeric|min:0',
        ]);

        $data['status'] = $data['status'] ?? 'draft';

        $tour = Tour::create($data);

        // author_id is not in $fillable — set directly after create
        $tour->author_id = VendorContext::id();
        $tour->saveQuietly();

        return response()->json(['message' => 'Tour created.', 'data' => $tour], 201);
    }

    public function updateTour(Request $request, int $id): JsonResponse
    {
        $tour = Tour::forVendor()->findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'location_id' => 'sometimes|nullable|integer',
            'content'     => 'sometimes|nullable|string',
            'status'      => 'sometimes|in:publish,draft,pending',
            'duration'    => 'sometimes|nullable|string|max:100',
            'price'       => 'sometimes|numeric|min:0',
        ]);

        $tour->update($data);

        return response()->json(['message' => 'Tour updated.', 'data' => $tour->fresh()]);
    }

    public function destroyTour(int $id): JsonResponse
    {
        $tour = Tour::forVendor()->findOrFail($id);
        $tour->delete();
        return response()->json(['message' => 'Tour deleted.']);
    }

    // ── Cars ──────────────────────────────────────────────────────────────────

    public function indexCars(Request $request): JsonResponse
    {
        $cars = Car::forVendor()
            ->with(['translation'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $cars]);
    }

    public function showCar(int $id): JsonResponse
    {
        $car = Car::forVendor()->with(['translation'])->findOrFail($id);
        return response()->json(['data' => $car]);
    }

    // ── Boats ─────────────────────────────────────────────────────────────────

    public function indexBoats(Request $request): JsonResponse
    {
        $boats = Boat::forVendor()
            ->with(['translation'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $boats]);
    }

    public function showBoat(int $id): JsonResponse
    {
        $boat = Boat::forVendor()->with(['translation'])->findOrFail($id);
        return response()->json(['data' => $boat]);
    }

    // ── Events ────────────────────────────────────────────────────────────────

    public function indexEvents(Request $request): JsonResponse
    {
        $events = Event::forVendor()
            ->with(['translation'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $events]);
    }

    public function showEvent(int $id): JsonResponse
    {
        $event = Event::forVendor()->with(['translation'])->findOrFail($id);
        return response()->json(['data' => $event]);
    }
}
