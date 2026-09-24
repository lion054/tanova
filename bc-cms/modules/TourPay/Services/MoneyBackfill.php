<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Models\Payment;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\VendorPayout;

/**
 * Loads the money that was recorded before the ledger existed. Safe to run again: every fact has a key, so it is only written once.
 *
 * Order matters. Payments and payouts first; then payments recorded on booking pages; then, for each booking, whatever its `paid`
 * held that nothing above explains (a gateway that wrote it directly) as one "opening" row; then the bookings' stored `paid` is
 * set to the ledger, and each invoice made from a booking is brought level with the money that came in on the booking.
 * Bookings' statuses are not touched, and no e-mail or webhook is sent.
 */
class MoneyBackfill
{
    /** Gateway names that mean the platform's own checkout (the money reached the platform), rather than the business. */
    private const OFFLINE = ['', 'offline_payment', 'offline', 'cash', 'bank', 'manual'];

    /** @return array<string,int> what was written or adjusted */
    public function run(): array
    {
        $n = ['tourpay_payments' => 0, 'booking_payments' => 0, 'bill_payments' => 0, 'payouts' => 0, 'opening' => 0, 'booking_caches' => 0, 'invoice_allocations' => 0];
        Ledger::$backfilling = true;
        try {
            Payment::withoutVendorScope()->where('status', 'confirmed')->where('source', '!=', 'booking')->orderBy('id')->each(function (Payment $p) use (&$n) {
                $n['tourpay_payments'] += $this->fresh(fn () => LedgerHooks::mirrorPayment($p), 'tourpay_payment:' . $p->id);
            });
            BillPayment::withoutVendorScope()->orderBy('id')->each(function (BillPayment $bp) use (&$n) {
                $n['bill_payments'] += $this->fresh(fn () => LedgerHooks::mirrorBillPayment($bp), 'bill_payment:' . $bp->id);
            });
            VendorPayout::where('status', 'paid')->orderBy('id')->each(function (VendorPayout $po) use (&$n) {
                $n['payouts'] += $this->fresh(fn () => LedgerHooks::mirrorPayout($po), 'payout:' . $po->id);
            });
            BookingLedger::withoutVendorScope()->orderBy('id')->each(function (BookingLedger $l) use (&$n) {
                if ($b = Booking::find($l->booking_id)) {
                    $n['booking_payments'] += $this->fresh(fn () => LedgerHooks::mirrorBookingLedger($l, $b), 'booking_ledger:' . $l->id);
                }
            });

            Booking::where('paid', '!=', 0)->whereNotNull('paid')->orderBy('id')->each(function (Booking $b) use (&$n) {
                $n['opening'] += $this->opening($b);
            });
        } finally {
            Ledger::$backfilling = false;
        }

        // The stored paid amount follows the ledger (quietly: no status change, no e-mail, no webhook).
        $ledger = app(Ledger::class);
        LedgerEntry::withoutVendorScope()->whereNotNull('booking_id')->distinct()->pluck('booking_id')->each(function ($id) use ($ledger, &$n) {
            $sum = $ledger->bookingPaid((int) $id);
            $n['booking_caches'] += Booking::where('id', $id)->where(fn ($q) => $q->whereNull('paid')->orWhere('paid', '!=', $sum))->update(['paid' => $sum]);   // paid can be NULL: NULL != x is never true in SQL
        });

        // Invoices made from a booking: bring them level with the money that came in on the booking.
        Invoice::withoutVendorScope()->whereNotNull('booking_id')->where('type', 'invoice')->where('status', '!=', 'void')->orderBy('id')->each(function (Invoice $inv) use (&$n) {
            $n['invoice_allocations'] += $this->levelInvoice($inv);
        });

        return $n;
    }

    /**
     * Whatever a booking's stored `paid` holds that no ledger row explains (a gateway that wrote it directly, a raw edit) becomes one
     * "opening" row, so the ledger and the booking agree. Booking-side money only: invoice payments never went into the old `paid`.
     * Also used when an invoice is made from a booking, so a booking that predates the ledger never shows as unpaid there.
     * Returns 1 when a row was written.
     */
    public function opening(Booking $b): int
    {
        $explained = (float) LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->whereNull('invoice_id')->whereIn('kind', ['payment', 'refund'])->sum('amount');
        $rest = round((float) $b->paid - $explained, 2);
        if (abs($rest) < 0.005) {
            return 0;
        }
        $platform = DB::table('bc_booking_payments')->where('booking_id', $b->id)->where('status', 'completed')->exists() || !in_array(strtolower((string) $b->gateway), self::OFFLINE, true);
        $key = 'opening:booking:' . $b->id;
        $was = Ledger::$backfilling;
        Ledger::$backfilling = true;
        try {
            return $this->fresh(fn () => app(Ledger::class)->record(['vendor_id' => (int) $b->vendor_id, 'entry_key' => $key, 'kind' => $rest < 0 ? 'refund' : 'payment', 'amount' => $rest,
                'currency' => LedgerHooks::currencyOf($b), 'held_by' => $platform ? 'platform' : 'vendor', 'method' => $b->gateway ?: 'other', 'source' => 'opening', 'source_id' => $b->id, 'booking_id' => $b->id,
                'reference' => $b->code, 'note' => __('Balance carried in when the ledger started'), 'occurred_at' => $b->updated_at ?: $b->created_at ?: now()]), $key);
        } finally {
            Ledger::$backfilling = $was;
        }
    }

    /** Booking-side money in the ledger against what the invoice already holds from the booking: one adjusting allocation for the difference. */
    private function levelInvoice(Invoice $inv): int
    {
        $entries = LedgerEntry::withoutVendorScope()->where('booking_id', $inv->booking_id)->whereNull('invoice_id')->whereIn('kind', ['payment', 'refund'])->where('currency', strtoupper((string) $inv->currency));
        $onBooking = round((float) (clone $entries)->sum('amount'), 2);
        $allocated = round((float) Payment::withoutVendorScope()->where('invoice_id', $inv->id)->where('source', 'booking')->where('status', 'confirmed')->sum('amount'), 2);
        $room = round(max(0.0, (float) $inv->payable() - (float) Payment::withoutVendorScope()->where('invoice_id', $inv->id)->where('status', 'confirmed')->sum('amount')), 2);
        $diff = round($onBooking - $allocated, 2);
        $diff = $diff > 0 ? min($diff, $room) : max($diff, -$allocated);
        if (abs($diff) < 0.005) {
            return 0;
        }
        $key = 'backfill:invoice:' . $inv->id;
        if (Payment::withoutVendorScope()->where('invoice_id', $inv->id)->where('gateway_ref', $key)->exists()) {
            return 0;
        }
        Payment::withoutVendorScope()->create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'amount' => $diff, 'method' => 'other', 'gateway_ref' => $key, 'paid_at' => now()->toDateString(),
            'notes' => __('Brought level with the booking'), 'source' => 'booking', 'status' => 'confirmed']);
        $inv->recalculate();

        return 1;
    }

    /** 1 when the row did not exist before the call, else 0. */
    private function fresh(callable $write, string $key): int
    {
        $before = LedgerEntry::withoutVendorScope()->where('entry_key', $key)->exists();
        $write();

        return $before ? 0 : (LedgerEntry::withoutVendorScope()->where('entry_key', $key)->exists() ? 1 : 0);
    }
}
