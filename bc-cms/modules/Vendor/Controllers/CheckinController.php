<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingCheckin;

/**
 * Phase 2 — Check-in / check-out / no-show tracking for the vendor's bookings.
 * BookingCheckin uses BelongsToVendor; bookings resolved explicitly per vendor.
 */
class CheckinController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $bookings = Booking::where('vendor_id', resolve_current_vendor_id())
            ->whereDate('start_date', $date)
            ->whereNotIn('status', Booking::$notAcceptedStatus)
            ->orderBy('start_date')
            ->paginate(30);

        // BookingCheckin is auto-scoped to this vendor by the global scope.
        $checkins = BookingCheckin::whereIn('booking_id', $bookings->pluck('id'))
            ->get()
            ->keyBy('booking_id');

        return view('vendor.checkin.index', [
            'bookings'   => $bookings,
            'checkins'   => $checkins,
            'date'       => $date,
            'page_title' => __('Check-In'),
        ]);
    }

    public function checkIn(Request $request, $bookingId)
    {
        return $this->setState($bookingId, BookingCheckin::STATUS_CHECKED_IN, [
            'checkin_at'     => now(),
            'guests_present' => $request->integer('guests_present') ?: null,
            'notes'          => $request->input('notes'),
        ], __('Guest checked in.'));
    }

    public function checkOut($bookingId)
    {
        return $this->setState($bookingId, BookingCheckin::STATUS_CHECKED_OUT, [
            'checkout_at' => now(),
        ], __('Guest checked out.'));
    }

    public function noShow($bookingId)
    {
        return $this->setState($bookingId, BookingCheckin::STATUS_NO_SHOW, [], __('Marked as no-show.'));
    }

    private function setState($bookingId, string $status, array $extra, string $message)
    {
        // Confirm the booking belongs to this vendor before recording anything.
        $booking = Booking::where('vendor_id', resolve_current_vendor_id())->findOrFail($bookingId);

        BookingCheckin::updateOrCreate(
            ['booking_id' => $booking->id],
            array_merge(['status' => $status], $extra)
        );

        return back()->with('success', $message);
    }
}
