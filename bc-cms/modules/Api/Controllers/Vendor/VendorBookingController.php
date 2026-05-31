<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Models\Booking;

class VendorBookingController extends Controller
{
    /**
     * List vendor's bookings with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::forVendor()
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->object_model, fn($q, $m) => $q->where('object_model', $m))
            ->when($request->from,  fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to,    fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 15), 100));

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

    /**
     * Update booking status (confirm, cancel, complete).
     * Uses model save + event so all side-effects fire:
     * customer/vendor emails, commission recalc, webhooks.
     */
    public function updateStatus(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed',
        ]);

        $booking = Booking::forVendor()->where('code', $code)->firstOrFail();

        $allowedTransitions = [
            Booking::PROCESSING => [Booking::CONFIRMED, Booking::CANCELLED],
            Booking::CONFIRMED  => [Booking::COMPLETED, Booking::CANCELLED],
            Booking::PAID       => [Booking::CONFIRMED, Booking::CANCELLED],
            Booking::UNPAID     => [Booking::CANCELLED],
        ];

        $allowed = $allowedTransitions[$booking->status] ?? [];

        if (!in_array($request->status, $allowed)) {
            return response()->json([
                'error' => [
                    'code'    => 'invalid_transition',
                    'message' => "Cannot change booking from '{$booking->status}' to '{$request->status}'.",
                ],
            ], 422);
        }

        $booking->status = $request->status;
        $booking->save();

        // Send status-change emails to admin, vendor, customer
        $booking->sendStatusUpdatedEmails();

        // Fire event — triggers commission recalc, outbound webhooks
        event(new BookingUpdatedEvent($booking));

        return response()->json([
            'message' => 'Booking status updated.',
            'data'    => ['code' => $booking->code, 'status' => $booking->status],
        ]);
    }
}
