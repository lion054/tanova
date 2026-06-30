<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingCheckin;
use Modules\Vendor\Models\BookingQuote;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorUpsell;

/**
 * Phase 1 + 2 — Single booking "operations" page for the vendor: manage add-ons,
 * run the quote/counter-offer thread, and record check-in/out. Aggregates the
 * tenant-scoped models for one booking the current vendor owns.
 */
class BookingOpsController extends Controller
{
    public function show($bookingId)
    {
        $booking = Booking::where('vendor_id', resolve_current_vendor_id())->findOrFail($bookingId);

        $bookingUpsells = BookingUpsell::where('booking_id', $booking->id)->get();
        $quotes         = BookingQuote::where('booking_id', $booking->id)
            ->orderBy('id')->get();
        $checkin        = BookingCheckin::where('booking_id', $booking->id)->first();

        return view('vendor.bookings.ops', [
            'booking'        => $booking,
            'bookingUpsells' => $bookingUpsells,
            'upsellsTotal'   => $bookingUpsells->sum('total'),
            'quotes'         => $quotes,
            'checkin'        => $checkin,
            'catalog'        => VendorUpsell::where('status', 'publish')
                ->orderBy('sort_order')->get(),
            'page_title'     => __('Booking #:code', ['code' => $booking->code ?: $booking->id]),
        ]);
    }
}
