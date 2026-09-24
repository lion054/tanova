<?php

namespace Modules\Vendor\Services;

use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Models\VendorUpsellService;

/**
 * Add-ons on a booking: attach one at the price that applies to that booking's own service, take
 * one off, and keep the booking's total right. The portal booking page and the API both use this.
 */
class BookingAddons
{
    /** Attach an add-on, snapshotting its name and the price that applied. */
    public function attach(Booking $booking, VendorUpsell $upsell, int $qty = 1): BookingUpsell
    {
        $qty = max(1, $qty);
        // The price for this booking's own service, when the add-on has one there.
        $link = VendorUpsellService::where('upsell_id', $upsell->id)
            ->where('object_model', (string) $booking->object_model)
            ->where('object_id', (int) $booking->object_id)
            ->first();
        $unit = $link && $link->price_override !== null ? (float) $link->price_override : (float) $upsell->price;

        $item = BookingUpsell::create([
            'booking_id' => $booking->id,
            'upsell_id'  => $upsell->id,
            'name'       => $upsell->name,
            'unit_price' => $unit,
            'qty'        => $qty,
            'total'      => $upsell->computeTotal($qty, (int) ($booking->total_guests ?: 1), $this->nightsFor($booking), $this->daysFor($booking), $unit),
        ]);
        $this->recompute($booking);

        return $item;
    }

    public function detach(Booking $booking, BookingUpsell $item): void
    {
        $item->delete();
        $this->recompute($booking);
    }

    /**
     * Reflect attached add-ons in the booking totals, idempotently and correctly.
     *
     * Both `total` and `total_before_fees` are snapshotted once (ops_base_total /
     * ops_base_tbf) and recomputed as base + sum(add-ons). Bumping BOTH by the same
     * amount keeps the fee delta (total - total_before_fees) constant — so earning/
     * fee reports are unaffected — while increasing the vendor payout
     * (total_before_fees - commission + service_fee) by exactly the add-on amount.
     * Add-ons are therefore vendor revenue and NOT commissionable; `commission`
     * itself is left untouched. total_before_fees is only adjusted when it was
     * already decomposed (> 0); Tanova/MCP bookings that never set it are left as-is.
     */
    public function recompute(Booking $booking): void
    {
        $upsellTotal = (float) BookingUpsell::where('booking_id', $booking->id)->sum('total');

        $baseTotal = $booking->getMeta('ops_base_total');
        if ($baseTotal === '' || $baseTotal === null) {
            $baseTotal = (float) $booking->total;
            $booking->addMeta('ops_base_total', (string) $baseTotal);
        }
        $booking->total = round((float) $baseTotal + $upsellTotal, 2);

        if ((float) $booking->total_before_fees > 0 || $booking->getMeta('ops_base_tbf') !== '') {
            $baseTbf = $booking->getMeta('ops_base_tbf');
            if ($baseTbf === '' || $baseTbf === null) {
                $baseTbf = (float) $booking->total_before_fees;
                $booking->addMeta('ops_base_tbf', (string) $baseTbf);
            }
            $booking->total_before_fees = round((float) $baseTbf + $upsellTotal, 2);
        }

        $booking->save();
    }

    private function nightsFor(Booking $booking): int
    {
        if ($booking->start_date && $booking->end_date) {
            return max(1, \Carbon\Carbon::parse($booking->start_date)
                ->diffInDays(\Carbon\Carbon::parse($booking->end_date)));
        }

        return 1;
    }

    /** Calendar days the booking covers (a one-day tour is one). */
    private function daysFor(Booking $booking): int
    {
        if ($booking->start_date && $booking->end_date) {
            return max(1, \Carbon\Carbon::parse($booking->start_date)->startOfDay()
                ->diffInDays(\Carbon\Carbon::parse($booking->end_date)->startOfDay()) + 1);
        }

        return 1;
    }
}
