<?php

namespace Modules\TourPay\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Models\Payment;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\VendorPayout;

/**
 * Feeds the ledger from every place money is recorded, so no path can forget to, and none can write twice (each fact has a key).
 *
 *   invoice payments and refunds  -> tourpay_payment:{id}      (confirmed ones only)
 *   supplier bill payments        -> bill_payment:{id}
 *   payouts the platform has paid -> payout:{id}
 *   payments recorded on a booking -> booking_ledger:{id}      (written by BookingPayments, mirrored here only by the backfill)
 *   a booking's `paid` moved by a gateway that still writes it directly -> gateway:{booking}:{uuid}
 *
 * The mirror* methods are also what the backfill and the reconciliation use to repair a missing row.
 */
class LedgerHooks
{
    private static ?bool $ready = null;

    /** False until the ledger table exists (a fresh install runs its migrations in order). Only "yes" is remembered, so a later migration is noticed. */
    private static function ready(): bool
    {
        if (self::$ready === true) {
            return true;
        }

        return Schema::hasTable('bc_money_ledger') ? (self::$ready = true) : false;
    }

    public static function register(): void
    {
        Payment::saved(fn (Payment $p) => self::ready() && self::mirrorPayment($p));
        Payment::deleted(fn (Payment $p) => self::ready() && app(Ledger::class)->reverse('tourpay_payment:' . $p->id, null, __('Payment removed')));

        BillPayment::saved(fn (BillPayment $bp) => self::ready() && self::mirrorBillPayment($bp));
        BillPayment::deleted(fn (BillPayment $bp) => self::ready() && app(Ledger::class)->reverse('bill_payment:' . $bp->id, null, __('Payment removed')));

        VendorPayout::saved(fn (VendorPayout $po) => self::ready() && self::mirrorPayout($po));

        // Gateways in the booking engine (PayPal, Stripe, Paystack, Payrexx...) add to `paid` on the booking themselves.
        // Whatever moves it, other than the ledger's own cache update, becomes a ledger row.
        Booking::saved(function (Booking $b) {
            if (Ledger::$syncing || Ledger::$backfilling || !self::ready() || !($b->wasRecentlyCreated ? (float) $b->paid > 0 : $b->wasChanged('paid'))) {
                return;
            }
            $before = $b->wasRecentlyCreated ? 0.0 : (float) $b->getOriginal('paid');
            $delta = round((float) $b->paid - $before, 2);
            if (abs($delta) < 0.005) {
                return;
            }
            $ledger = app(Ledger::class);
            // A booking that predates the ledger: what it already held goes in first, so the ledger and the booking start level.
            if ($before > 0 && !LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->exists()) {
                $b->paid = $before;
                app(MoneyBackfill::class)->opening($b);
                $b->paid = round($before + $delta, 2);
            }
            $ledger->record(['vendor_id' => (int) $b->vendor_id, 'entry_key' => 'gateway:' . $b->id . ':' . Str::uuid(), 'kind' => $delta < 0 ? 'refund' : 'payment', 'amount' => $delta,
                'currency' => self::currencyOf($b), 'held_by' => 'platform', 'method' => $b->gateway ?: 'gateway', 'source' => 'gateway', 'source_id' => $b->id, 'booking_id' => $b->id,
                'reference' => $b->code, 'note' => __('Paid through :g', ['g' => $b->gateway ?: 'the booking']), 'occurred_at' => now()]);
            // A gateway works from the total it loaded, which may be stale: the stored total is always made equal to the ledger.
            $sum = $ledger->bookingPaid((int) $b->id);
            if (abs((float) $b->paid - $sum) > 0.004) {
                Booking::where('id', $b->id)->update(['paid' => $sum]);
                $b->paid = $sum;
            }
        });
    }

    // ── One mirror per source; each is safe to call again ───────────────────────

    public static function mirrorPayment(Payment $p): ?LedgerEntry
    {
        if ($p->source === 'booking') {
            return null;   // an allocation of money already in the ledger through the booking
        }
        $ledger = app(Ledger::class);
        $key = 'tourpay_payment:' . $p->id;
        if ($p->status !== 'confirmed') {
            $ledger->reverse($key);   // only does anything if it had been confirmed before

            return null;
        }
        $inv = Invoice::withoutVendorScope()->withTrashed()->find($p->invoice_id);
        if (!$inv) {
            return null;
        }
        $currency = strtoupper((string) $inv->currency);
        $booking = $inv->booking_id ? Booking::find($inv->booking_id) : null;
        $signed = (float) $p->amount;
        $at = self::when($p->paid_at, $p->created_at);

        // The invoice keeps its own currency. If its booking is in another one, the booking's paid amount gets the converted value
        // (the business's own rate, else the daily feed); with no known rate the payment is simply not linked to the booking.
        $bookingId = null; $extra = [];
        if ($booking) {
            $bookingCurrency = strtoupper((string) ($booking->currency ?: $currency));
            if ($bookingCurrency === $currency) {
                $bookingId = $booking->id;
            } elseif ($c = app(Rates::class)->convert($signed, $currency, $bookingCurrency, $at, (int) $inv->vendor_id)) {
                $bookingId = $booking->id;
                $extra = ['booking_amount' => $c['amount'], 'fx_rate' => $c['rate'], 'fx_source' => $c['source']];
            }
        }

        return $ledger->record($extra + ['vendor_id' => (int) $inv->vendor_id, 'entry_key' => $key, 'kind' => $signed < 0 ? 'refund' : 'payment', 'amount' => $signed, 'currency' => $currency,
            'held_by' => 'vendor', 'method' => $p->method, 'source' => 'tourpay_payment', 'source_id' => $p->id, 'booking_id' => $bookingId, 'invoice_id' => $inv->id,
            'reference' => $p->reference ?: $p->gateway_ref, 'note' => $p->notes, 'occurred_at' => $at, 'created_by' => $p->recorded_by]);
    }

    public static function mirrorBillPayment(BillPayment $bp): ?LedgerEntry
    {
        $bill = Bill::withoutVendorScope()->withTrashed()->find($bp->bill_id);
        if (!$bill) {
            return null;
        }

        return app(Ledger::class)->record(['vendor_id' => (int) $bill->vendor_id, 'entry_key' => 'bill_payment:' . $bp->id, 'kind' => 'expense', 'amount' => -1 * abs((float) $bp->amount),
            'currency' => strtoupper((string) ($bill->currency ?: 'USD')), 'held_by' => 'vendor', 'method' => $bp->method, 'source' => 'bill_payment', 'source_id' => $bp->id,
            'bill_id' => $bill->id, 'reference' => $bp->reference ?: $bill->reference, 'note' => $bp->notes ?: $bill->supplier_name, 'occurred_at' => self::when($bp->paid_at, $bp->created_at)]);
    }

    public static function mirrorPayout(VendorPayout $po): ?LedgerEntry
    {
        $ledger = app(Ledger::class);
        $key = 'payout:' . $po->id;
        if ($po->status !== 'paid') {
            $ledger->reverse($key, null, __('Payout no longer paid'));

            return null;
        }

        $entry = $ledger->record(['vendor_id' => (int) $po->vendor_id, 'entry_key' => $key, 'kind' => 'payout', 'amount' => abs((float) $po->amount),
            'currency' => strtoupper((string) (setting_item('currency_main') ?: 'USD')), 'held_by' => 'vendor', 'method' => (string) $po->payout_method, 'source' => 'payout',
            'source_id' => $po->id, 'payout_id' => $po->id, 'note' => $po->note_to_vendor, 'occurred_at' => self::when($po->pay_date, $po->updated_at), 'created_by' => $po->last_process_by]);
        app(Commission::class)->settleFromRetained((int) $po->vendor_id, 'payout:' . $po->id);   // owed commission is settled from what the platform still holds

        return $entry;
    }

    /** A payment or refund recorded on a booking's page. */
    public static function mirrorBookingLedger(BookingLedger $l, Booking $booking): LedgerEntry
    {
        $signed = $l->type === 'refund' ? -1 * abs((float) $l->amount) : abs((float) $l->amount);

        return app(Ledger::class)->record(['vendor_id' => (int) $booking->vendor_id, 'entry_key' => 'booking_ledger:' . $l->id, 'kind' => $signed < 0 ? 'refund' : 'payment', 'amount' => $signed,
            'currency' => self::currencyOf($booking), 'held_by' => 'vendor', 'method' => $l->method, 'source' => 'booking_ledger', 'source_id' => $l->id, 'booking_id' => $booking->id,
            'reference' => $l->reference, 'note' => $l->note, 'occurred_at' => $l->occurred_at ?: now(), 'created_by' => $l->created_by]);
    }

    public static function currencyOf(Booking $b): string
    {
        return strtoupper($b->currency ?: (string) (setting_item('currency_main') ?: 'USD'));
    }

    /** A date (no time) becomes that day; keep the time of day of the row when it is the same day, so the order within a day is real. */
    private static function when($date, $created): Carbon
    {
        $d = $date ? Carbon::parse($date) : now();
        $c = $created ? Carbon::parse($created) : null;

        return $c && $c->toDateString() === $d->toDateString() ? $c : $d->copy()->setTime(12, 0);
    }
}
