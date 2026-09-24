<?php

namespace Modules\TourPay\Services;

use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\Invoice;

/**
 * What a booking really earned: what the client is billed (its live invoices, less credit notes; the booking's own total when
 * there is no invoice yet) minus what suppliers charge for it (bills linked to it). Amounts in different currencies are never
 * added together: the answer says so instead of guessing.
 */
class Profit
{
    /** @return array{currency:?string,revenue:float,cost:float,profit:float,margin:?float,mixed:bool,invoiced:bool,bills:int} */
    public function forBooking(Booking $booking): array
    {
        $invoices = Invoice::where('booking_id', $booking->id)->where('type', 'invoice')->whereNotIn('status', ['void', 'draft'])->get();
        $bills = Bill::where('booking_id', $booking->id)->where('status', '!=', 'void')->get();
        $cur = strtoupper((string) ($invoices->first()->currency ?? $booking->currency ?: ''));

        $mixed = $invoices->pluck('currency')->merge($bills->pluck('currency'))->map(fn ($c) => strtoupper((string) $c))->unique()->count() > 1;
        $revenue = $invoices->isNotEmpty() ? (float) $invoices->sum(fn ($i) => $i->payable()) : (float) $booking->total;
        $cost = (float) $bills->sum('total');
        $profit = round($revenue - $cost, 2);

        return ['currency' => $cur ?: null, 'revenue' => round($revenue, 2), 'cost' => round($cost, 2), 'profit' => $profit, 'margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : null,
            'mixed' => $mixed, 'invoiced' => $invoices->isNotEmpty(), 'bills' => $bills->count()];
    }

    /** Every booking that has at least one bill, with its profit: for the report. */
    public function withBills(int $limit = 200): array
    {
        $ids = Bill::whereNotNull('booking_id')->where('status', '!=', 'void')->distinct()->orderByDesc('booking_id')->limit($limit)->pluck('booking_id');
        $out = [];
        foreach (Booking::whereIn('id', $ids)->get() as $b) {
            $out[] = ['booking' => $b] + $this->forBooking($b);
        }

        return $out;
    }
}
