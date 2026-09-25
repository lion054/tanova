<?php

namespace Modules\TourPay\Services;

use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Installment;
use Modules\TourPay\Models\Invoice;
use Modules\Vendor\Models\BookingPaymentPlan;

/**
 * One payment schedule per debt. A booking has a plan (deposit, balance, instalments) and so can its invoice; when a booking has a live
 * invoice in the same currency the two are the same schedule, whichever screen it is edited on: an edit on one is copied to the other,
 * and what has been paid (read from the ledger, earliest instalments first) decides which rows show as paid.
 */
class ScheduleSync
{
    private static bool $busy = false;

    /** The booking's plan becomes the invoice's schedule. */
    public function bookingToInvoice(Booking $booking): void
    {
        if (self::$busy || !($inv = $this->invoiceOf($booking))) {
            return;
        }
        self::$busy = true;
        try {
            Installment::where('invoice_id', $inv->id)->delete();
            $rows = BookingPaymentPlan::withoutVendorScope()->where('booking_id', $booking->id)->where('status', '!=', 'waived')->orderBy('sort_order')->orderBy('id')->get();
            foreach ($rows as $i => $r) {
                Installment::create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'label' => $r->label, 'amount' => $r->amount, 'due_date' => $r->due_date?->toDateString() ?: now()->toDateString(), 'sort_order' => $i]);
            }
            app(InvoiceBook::class)->rescaleSchedule($inv);   // rows add up to what the invoice is for
        } finally {
            self::$busy = false;
        }
    }

    /** The invoice's schedule becomes the booking's plan. */
    public function invoiceToBooking(Invoice $inv): void
    {
        if (self::$busy || !$inv->booking_id || $inv->type !== 'invoice' || !($booking = Booking::find($inv->booking_id)) || !$this->sameCurrency($booking, $inv)) {
            return;
        }
        self::$busy = true;
        try {
            BookingPaymentPlan::withoutVendorScope()->where('booking_id', $booking->id)->delete();
            foreach (Installment::where('invoice_id', $inv->id)->orderBy('sort_order')->orderBy('id')->get() as $i => $it) {
                BookingPaymentPlan::withoutVendorScope()->create(['vendor_id' => $booking->vendor_id, 'booking_id' => $booking->id, 'label' => $it->label, 'amount' => $it->amount,
                    'due_date' => $it->due_date->toDateString(), 'status' => 'pending', 'sort_order' => $i + 1]);
            }
            $this->refreshStatuses($booking);
        } finally {
            self::$busy = false;
        }
    }

    /** Rows are paid in order as far as what has been received reaches (a refund can take the last ones back to pending). Waived rows stay waived. */
    public function refreshStatuses(Booking $booking): void
    {
        $left = app(Ledger::class)->bookingPaid((int) $booking->id);
        foreach (BookingPaymentPlan::withoutVendorScope()->where('booking_id', $booking->id)->where('status', '!=', 'waived')->orderBy('sort_order')->orderBy('id')->get() as $row) {
            $covered = $left + 0.005 >= (float) $row->amount && (float) $row->amount > 0;
            $left = $covered ? round($left - (float) $row->amount, 2) : 0.0;
            if ($covered && $row->status !== 'paid') {
                $row->update(['status' => 'paid', 'paid_at' => now()]);
            } elseif (!$covered && $row->status === 'paid') {
                $row->update(['status' => 'pending', 'paid_at' => null]);
            }
        }
    }

    private function invoiceOf(Booking $booking): ?Invoice
    {
        $inv = Invoice::withoutVendorScope()->where('booking_id', $booking->id)->where('type', 'invoice')->where('status', '!=', 'void')->orderByDesc('id')->first();

        return $inv && $this->sameCurrency($booking, $inv) ? $inv : null;
    }

    private function sameCurrency(Booking $b, Invoice $i): bool
    {
        return !$b->currency || strtoupper($b->currency) === strtoupper((string) $i->currency);
    }
}
