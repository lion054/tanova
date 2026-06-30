<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingQuote;

/**
 * Phase 1 — Counter-offer / quote negotiation thread on a booking.
 * BookingQuote uses BelongsToVendor (auto-scoped). Bookings resolved explicitly
 * against the current vendor.
 */
class QuoteController extends Controller
{
    /** Vendor sends a fresh quote on a booking. */
    public function send(Request $request, $bookingId)
    {
        $booking = $this->vendorBooking($bookingId);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0'],
            'message'     => ['nullable', 'string'],
            'valid_until' => ['nullable', 'date'],
        ]);

        BookingQuote::create([
            'booking_id'  => $booking->id,
            'direction'   => 'vendor',
            'amount'      => $data['amount'],
            'currency'    => $booking->currency,
            'message'     => $data['message'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'status'      => BookingQuote::STATUS_SENT,
            'created_by'  => Auth::id(),
        ]);

        return back()->with('success', __('Quote sent to customer.'));
    }

    /** Vendor counters an existing (usually customer) quote. */
    public function counter(Request $request, BookingQuote $quote)
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0'],
            'message'     => ['nullable', 'string'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $quote->update(['status' => BookingQuote::STATUS_COUNTERED]);

        BookingQuote::create([
            'booking_id'  => $quote->booking_id,
            'parent_id'   => $quote->id,
            'direction'   => 'vendor',
            'amount'      => $data['amount'],
            'currency'    => $quote->currency,
            'message'     => $data['message'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'status'      => BookingQuote::STATUS_SENT,
            'created_by'  => Auth::id(),
        ]);

        return back()->with('success', __('Counter-offer sent.'));
    }

    public function accept(BookingQuote $quote)
    {
        DB::transaction(function () use ($quote) {
            $quote->update(['status' => BookingQuote::STATUS_ACCEPTED]);

            // Supersede any other still-open quotes on the same booking.
            BookingQuote::where('booking_id', $quote->booking_id)
                ->where('id', '!=', $quote->id)
                ->whereIn('status', [BookingQuote::STATUS_SENT, BookingQuote::STATUS_COUNTERED])
                ->update(['status' => BookingQuote::STATUS_DECLINED]);

            // Apply the agreed amount to the booking total (owner-scoped lookup).
            $booking = Booking::where('vendor_id', resolve_current_vendor_id())->find($quote->booking_id);
            if ($booking) {
                $booking->total = $quote->amount;
                if ($quote->currency) {
                    $booking->currency = $quote->currency;
                }
                $booking->save();
            }
        });

        return back()->with('success', __('Quote accepted and applied to the booking.'));
    }

    public function decline(BookingQuote $quote)
    {
        $quote->update(['status' => BookingQuote::STATUS_DECLINED]);

        return back()->with('success', __('Quote declined.'));
    }

    private function vendorBooking($id): Booking
    {
        return Booking::where('vendor_id', resolve_current_vendor_id())->findOrFail($id);
    }
}
