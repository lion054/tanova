<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Models\Payment;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\VendorPayout;

/**
 * Checks that every total agrees with the ledger, and says so loudly when it does not.
 *
 * It never edits the ledger to make a number fit. `--fix` only does the safe things: writes a missing row for a fact that
 * exists in its source table, reverses a row whose source is gone, and refreshes a stored total from the rows behind it.
 */
class MoneyReconcile
{
    public const CACHE_KEY = 'money.reconcile';

    /** @return array{ok:bool,ran_at:string,problems:array<string,int>,samples:array<string,array>,fixed:int} */
    public function run(bool $fix = false): array
    {
        $problems = []; $samples = []; $fixed = 0;
        $note = function (string $type, string $what, bool $repaired = false) use (&$problems, &$samples, &$fixed) {
            if ($repaired) { $fixed++; return; }
            $problems[$type] = ($problems[$type] ?? 0) + 1;
            if (count($samples[$type] ?? []) < 5) { $samples[$type][] = $what; }
        };
        $has = fn (string $key) => LedgerEntry::withoutVendorScope()->where('entry_key', $key)->exists();
        $ledger = app(Ledger::class);

        // 1. Every confirmed fact in a source table has its row.
        Payment::withoutVendorScope()->where('status', 'confirmed')->where('source', '!=', 'booking')->orderBy('id')->each(function (Payment $p) use ($has, $note, $fix) {
            if (!$has('tourpay_payment:' . $p->id)) {
                $fix ? LedgerHooks::mirrorPayment($p) : null;
                $note('payment_missing_in_ledger', "payment #{$p->id}", $fix);
            }
        });
        BillPayment::withoutVendorScope()->orderBy('id')->each(function (BillPayment $bp) use ($has, $note, $fix) {
            if (!$has('bill_payment:' . $bp->id)) {
                $fix ? LedgerHooks::mirrorBillPayment($bp) : null;
                $note('bill_payment_missing_in_ledger', "bill payment #{$bp->id}", $fix);
            }
        });
        VendorPayout::where('status', 'paid')->orderBy('id')->each(function (VendorPayout $po) use ($has, $note, $fix) {
            if (!$has('payout:' . $po->id)) {
                $fix ? LedgerHooks::mirrorPayout($po) : null;
                $note('payout_missing_in_ledger', "payout #{$po->id}", $fix);
            }
        });
        BookingLedger::withoutVendorScope()->orderBy('id')->each(function (BookingLedger $l) use ($has, $note, $fix) {
            if (!$has('booking_ledger:' . $l->id) && ($b = Booking::find($l->booking_id))) {
                $fix ? LedgerHooks::mirrorBookingLedger($l, $b) : null;
                $note('booking_entry_missing_in_ledger', "booking entry #{$l->id}", $fix);
            }
        });

        // 2. Every ledger row still has its source (else the source was deleted behind the ledger's back).
        LedgerEntry::withoutVendorScope()->whereIn('source', ['tourpay_payment', 'bill_payment', 'booking_ledger'])->whereNull('reverses_id')->orderBy('id')->each(function (LedgerEntry $e) use ($note, $fix, $ledger) {
            $exists = match ($e->source) {
                'tourpay_payment' => Payment::withoutVendorScope()->where('id', $e->source_id)->where('status', 'confirmed')->exists(),
                'bill_payment'    => BillPayment::withoutVendorScope()->where('id', $e->source_id)->exists(),
                default           => BookingLedger::withoutVendorScope()->where('id', $e->source_id)->exists(),
            };
            if (!$exists && !LedgerEntry::withoutVendorScope()->where('reverses_id', $e->id)->exists()) {
                $fix ? $ledger->reverse($e->entry_key, null, __('Source record no longer exists')) : null;
                $note('ledger_entry_without_source', $e->entry_key, $fix);
            }
        });

        // 3. Stored totals equal the rows behind them.
        $ledgerByBooking = LedgerEntry::withoutVendorScope()->whereNotNull('booking_id')->whereIn('kind', ['payment', 'refund'])->groupBy('booking_id')->selectRaw('booking_id, SUM(amount) s')->pluck('s', 'booking_id');
        Booking::where(fn ($q) => $q->where('paid', '!=', 0)->orWhereIn('id', $ledgerByBooking->keys()))->orderBy('id')->each(function (Booking $b) use ($ledgerByBooking, $note, $fix) {
            $sum = max(0.0, round((float) ($ledgerByBooking[$b->id] ?? 0), 2));
            if (abs((float) $b->paid - $sum) > 0.005) {
                if ($fix) { Booking::where('id', $b->id)->update(['paid' => $sum]); }
                $note('booking_paid_differs_from_ledger', "booking #{$b->id}: stored " . number_format((float) $b->paid, 2) . ', ledger ' . number_format($sum, 2), $fix);
            }
        });
        Invoice::withoutVendorScope()->where('type', 'invoice')->orderBy('id')->each(function (Invoice $i) use ($note, $fix) {
            $sum = round((float) Payment::withoutVendorScope()->where('invoice_id', $i->id)->where('status', 'confirmed')->sum('amount'), 2);
            if (abs((float) $i->amount_paid - $sum) > 0.005) {
                if ($fix) { $i->recalculate(); }
                $note('invoice_paid_differs_from_payments', "{$i->invoice_number}: stored " . number_format((float) $i->amount_paid, 2) . ', payments ' . number_format($sum, 2), $fix);
            }
            if ($sum - $i->payable() > 0.01 && $i->status !== 'void') {
                $note('invoice_overpaid', "{$i->invoice_number}: paid " . number_format($sum, 2) . ' of ' . number_format($i->payable(), 2));
            }
        });
        Bill::withoutVendorScope()->orderBy('id')->each(function (Bill $b) use ($note, $fix) {
            $sum = round((float) BillPayment::withoutVendorScope()->where('bill_id', $b->id)->sum('amount'), 2);
            if (abs((float) $b->amount_paid - $sum) > 0.005) {
                if ($fix) { $b->recalculate(); }
                $note('bill_paid_differs_from_payments', "bill #{$b->id}", $fix);
            }
        });

        // 4. An invoice made from a booking shows the same money as the booking (same currency only).
        Invoice::withoutVendorScope()->whereNotNull('booking_id')->where('type', 'invoice')->whereNotIn('status', ['void'])->orderBy('id')->each(function (Invoice $i) use ($note) {
            $b = Booking::find($i->booking_id);
            if (!$b || ($b->currency && strtoupper($b->currency) !== strtoupper((string) $i->currency))) {
                return;
            }
            $booking = max(0.0, round((float) LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->whereIn('kind', ['payment', 'refund'])->sum('amount'), 2));
            $invoice = round((float) Payment::withoutVendorScope()->where('invoice_id', $i->id)->where('status', 'confirmed')->sum('amount'), 2);
            if (abs($booking - $invoice) > 0.01 && $i->status !== 'draft') {
                $note('invoice_and_booking_disagree', "{$i->invoice_number}: invoice " . number_format($invoice, 2) . ", booking #{$b->id} " . number_format($booking, 2));
            }
        });

        $result = ['ok' => empty($problems), 'ran_at' => now()->toIso8601String(), 'problems' => $problems, 'samples' => $samples, 'fixed' => $fixed];
        Cache::forever(self::CACHE_KEY, $result);

        return $result;
    }
}
