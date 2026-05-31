<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;

class VendorCreateBookingController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v/bookings
     *
     * Server-side booking creation for vendor websites.
     * The vendor passes guest details; the booking is attributed to the vendor.
     *
     * Required body:
     *   service_type  string   hotel|tour|car|boat|event
     *   service_id    int
     *   start_date    date
     *   end_date      date     (optional for single-day services)
     *   adults        int
     *   children      int
     *   first_name    string
     *   last_name     string
     *   email         email
     *   phone         string
     *
     * Optional:
     *   notes         string
     *   extra_price   array
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'service_type' => 'required|string|in:hotel,tour,car,boat,event',
            'service_id'   => 'required|integer',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'nullable|date|after:start_date',
            'adults'       => 'required|integer|min:1|max:50',
            'children'     => 'nullable|integer|min:0|max:20',
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:255',
            'phone'        => 'nullable|string|max:30',
            'notes'        => 'nullable|string|max:2000',
        ]);

        $class = get_bookable_service_by_id($request->service_type);

        if (!$class || !class_exists($class)) {
            return $this->error('invalid_type', "Service type '{$request->service_type}' is not supported.", 422);
        }

        // Enforce vendor ownership — cannot book a service they don't own
        $service = $class::forVendor()->find($request->service_id);

        if (!$service) {
            return $this->notFound(ucfirst($request->service_type));
        }

        if (!$service->isBookable()) {
            return $this->error('not_bookable', 'This service is not currently available for booking.', 409);
        }

        // Use the service's addToCart() to build the booking (price calc, extra_price, fees)
        $result = $service->addToCart($request);

        if ($result !== true) {
            $msg = is_array($result) ? ($result['message'] ?? 'Booking validation failed.') : (string) $result;
            return $this->error('booking_validation', $msg, 422);
        }

        // addToCart() stores the booking in the session; retrieve it
        $booking = Booking::where('status', Booking::DRAFT)
            ->where('vendor_id', VendorContext::id())
            ->where('object_id', $service->id)
            ->where('object_model', $request->service_type)
            ->latest()
            ->first();

        if (!$booking) {
            return $this->error('booking_failed', 'Booking could not be created.', 500);
        }

        // Stamp guest details. customer_id must be null — the caller is the vendor acting
        // on behalf of an end-customer who is not a registered user on this platform.
        // addToCart() sets customer_id = Auth::id() (= vendor's ID), so we override it here.
        $booking->update([
            'customer_id' => null,
            'first_name'  => $request->first_name,
            'last_name'   => $request->last_name,
            'email'       => $request->email,
            'phone'       => $request->phone ?? '',
            'note'        => $request->notes ?? '',
            'status'      => Booking::PROCESSING,
        ]);

        return $this->success([
            'booking_code' => $booking->code,
            'status'       => $booking->status,
            'total'        => $booking->total,
            'checkout_url' => $booking->getCheckoutUrl(),
        ], 201);
    }
}
