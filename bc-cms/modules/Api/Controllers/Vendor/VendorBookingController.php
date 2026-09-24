<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Models\Booking;

class VendorBookingController extends VendorApiController
{
    /**
     * List vendor's bookings with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $q = Booking::forVendor();
        \App\Support\ListQuery::search($q, $request->query('q'), ['first_name', 'last_name', 'email', 'phone', 'code'], 'id');
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        $service = $request->query('service', $request->query('object_model'));
        if ($service) {
            $q->where('object_model', $service);
        }
        // created between, and trip starting between
        foreach ([['from', '>=', 'created_at'], ['to', '<=', 'created_at'], ['trip_from', '>=', 'start_date'], ['trip_to', '<=', 'start_date']] as [$k, $op, $col]) {
            if ($d = $this->date($request, $k)) {
                $q->whereDate($col, $op, $d);
            }
        }
        \App\Support\ListQuery::sort($q, $request->query('sort'), ['newest' => ['id', 'desc'], 'oldest' => ['id', 'asc'], 'trip' => ['start_date', 'asc'], 'trip_late' => ['start_date', 'desc'], 'amount' => ['total', 'desc']], 'newest');
        $bookings = $q->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $bookings]);
    }

    /**
     * Get full detail of a single booking.
     */
    public function show(string $code): JsonResponse
    {
        $booking = Booking::forVendor()
            ->where('code', $code)
            ->with(['payment'])
            ->firstOrFail();

        return response()->json(['data' => $booking]);
    }
}
