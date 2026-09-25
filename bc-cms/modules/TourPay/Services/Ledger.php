<?php

namespace Modules\TourPay\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Models\Payment;
use Modules\TourPay\Models\Setting;

/**
 * The single writer of money facts.
 *
 * A fact is written once, under a key that names where it came from ("tourpay_payment:41"); writing it again returns the row
 * that is already there. Rows are never changed: {@see reverse()} posts the opposite row. Everything that shows a total
 * (a booking's paid amount, the statement, a payout balance) is worked out from these rows, and the booking's stored `paid`
 * is only a cache that {@see syncBooking()} keeps equal to them.
 */
class Ledger
{
    public const KINDS = ['payment', 'refund', 'expense', 'payout', 'commission'];

    /** True while this class is itself updating a booking's cache, so the legacy-gateway hook does not count it twice. */
    public static bool $syncing = false;

    /** True while the backfill loads history: rows are written without moving bookings or invoices, which already hold those totals. */
    public static bool $backfilling = false;

    /**
     * Writes one fact, once.
     *
     * @param array{vendor_id:int,entry_key:string,kind:string,amount:float|int|string,currency:string,source:string} $a
     *        optional: held_by, method, source_id, booking_id, invoice_id, bill_id, payout_id, reference, note, occurred_at, created_by
     */
    public function record(array $a): LedgerEntry
    {
        foreach (['vendor_id', 'entry_key', 'kind', 'amount', 'currency', 'source'] as $need) {
            if (!isset($a[$need]) || $a[$need] === '') {
                throw new \InvalidArgumentException("A ledger entry needs {$need}.");
            }
        }
        if (!in_array($a['kind'], self::KINDS, true)) {
            throw new \InvalidArgumentException('Unknown ledger kind ' . $a['kind']);
        }
        if ($existing = $this->find($a['entry_key'])) {
            return $existing;
        }

        $currency = strtoupper(substr((string) $a['currency'], 0, 3));
        $amount = round((float) $a['amount'], 2);
        [$base, $baseAmount] = $this->inBase((int) $a['vendor_id'], $amount, $currency, isset($a['occurred_at']) ? Carbon::parse($a['occurred_at']) : null);

        try {
            $entry = $this->append([
                'vendor_id' => (int) $a['vendor_id'], 'entry_key' => $a['entry_key'], 'kind' => $a['kind'], 'amount' => $amount, 'currency' => $currency,
                'held_by' => $a['held_by'] ?? 'vendor', 'method' => $a['method'] ?? null, 'source' => $a['source'], 'source_id' => $a['source_id'] ?? null,
                'booking_id' => $a['booking_id'] ?? null, 'invoice_id' => $a['invoice_id'] ?? null, 'bill_id' => $a['bill_id'] ?? null, 'payout_id' => $a['payout_id'] ?? null,
                'reverses_id' => $a['reverses_id'] ?? null, 'booking_amount' => isset($a['booking_amount']) ? round((float) $a['booking_amount'], 2) : null, 'fx_rate' => $a['fx_rate'] ?? null, 'fx_source' => $a['fx_source'] ?? null, 'reference' => isset($a['reference']) ? substr((string) $a['reference'], 0, 120) : null,
                'note' => isset($a['note']) ? substr((string) $a['note'], 0, 255) : null, 'base_currency' => $base, 'base_amount' => $baseAmount,
                'occurred_at' => $a['occurred_at'] ?? now(), 'created_by' => $a['created_by'] ?? null,
            ]);
        } catch (QueryException $e) {
            // Two requests wrote the same fact at once: the unique key let one through, the other reads it.
            if ($again = $this->find($a['entry_key'])) {
                return $again;
            }
            throw $e;
        }

        if (!self::$backfilling && $entry->booking_id && in_array($entry->kind, ['payment', 'refund'], true)) {
            $this->afterBookingMoney($entry);
        }
        if (!self::$backfilling) {
            app(Commission::class)->accrue($entry);
        }

        return $entry;
    }

    /** Posts the opposite of an earlier entry (once). Returns the reversal, or null when there is nothing to reverse. */
    public function reverse(string $entryKey, ?int $by = null, ?string $note = null): ?LedgerEntry
    {
        $orig = $this->find($entryKey);
        if (!$orig || $orig->isReversal()) {
            return null;
        }
        $entry = $this->find($entryKey . ':reversal') ?: $this->append([
            'vendor_id' => $orig->vendor_id, 'entry_key' => $entryKey . ':reversal', 'kind' => $orig->kind, 'amount' => -1 * (float) $orig->amount, 'currency' => $orig->currency,
            'held_by' => $orig->held_by, 'method' => $orig->method, 'source' => $orig->source, 'source_id' => $orig->source_id, 'booking_id' => $orig->booking_id,
            'invoice_id' => $orig->invoice_id, 'bill_id' => $orig->bill_id, 'payout_id' => $orig->payout_id, 'reverses_id' => $orig->id, 'reference' => $orig->reference,
            'booking_amount' => $orig->booking_amount === null ? null : -1 * (float) $orig->booking_amount, 'fx_rate' => $orig->fx_rate, 'fx_source' => $orig->fx_source,
            'note' => $note ?: __('Reversal'), 'base_currency' => $orig->base_currency, 'base_amount' => $orig->base_amount === null ? null : -1 * (float) $orig->base_amount,
            'occurred_at' => now(), 'created_by' => $by,
        ]);
        if ($entry->wasRecentlyCreated && $entry->booking_id && in_array($entry->kind, ['payment', 'refund'], true)) {
            $this->afterBookingMoney($entry);
        }
        if ($entry->wasRecentlyCreated) {
            app(Commission::class)->accrue($entry);   // a reversed payment gives its commission share back
        }

        return $entry;
    }

    /**
     * The one place a row is written. The business's chain is locked, the row is sealed with the hash of the row before it, and the
     * chain's anchor moves on, all in one transaction, so two writers can never fork the chain.
     */
    private function append(array $row): LedgerEntry
    {
        return DB::transaction(function () use ($row) {
            $vendor = (int) $row['vendor_id'];
            DB::table('bc_money_ledger_anchor')->insertOrIgnore(['vendor_id' => $vendor, 'last_id' => 0, 'last_hash' => LedgerChain::ZERO, 'updated_at' => now()]);
            $anchor = DB::table('bc_money_ledger_anchor')->where('vendor_id', $vendor)->lockForUpdate()->first();
            $row['occurred_at'] = Carbon::parse($row['occurred_at']);
            $row['chain_prev'] = $anchor->last_hash;
            $row['chain_hash'] = LedgerChain::hash($anchor->last_hash, $row);
            $entry = LedgerEntry::withoutVendorScope()->create($row);
            DB::table('bc_money_ledger_anchor')->where('vendor_id', $vendor)->update(['last_id' => $entry->id, 'last_hash' => $row['chain_hash'], 'updated_at' => now()]);

            return $entry;
        });
    }

    public function find(string $entryKey): ?LedgerEntry
    {
        return LedgerEntry::withoutVendorScope()->where('entry_key', $entryKey)->first();
    }

    // ── Totals, always from the rows ────────────────────────────────────────────

    /** What has been received on a booking, refunds taken off. Zero at least. */
    public function bookingPaid(int $bookingId): float
    {
        return max(0.0, round((float) LedgerEntry::withoutVendorScope()->where('booking_id', $bookingId)->whereIn('kind', ['payment', 'refund'])->sum(DB::raw('COALESCE(booking_amount, amount)')), 2));
    }

    /**
     * Money the platform holds for a business (collected by the platform, not yet paid out), by booking.
     * Never more than is still paid on the booking: if the business refunded the guest out of its own pocket, the platform is
     * not left holding money that no longer exists. @return array<int,float>
     */
    public function platformHeldByBooking(int $vendorId): array
    {
        $rows = LedgerEntry::withoutVendorScope()->where('vendor_id', $vendorId)->whereIn('kind', ['payment', 'refund'])->whereNotNull('booking_id')
            ->groupBy('booking_id')->selectRaw("booking_id, SUM(COALESCE(booking_amount, amount)) AS everything, SUM(CASE WHEN held_by = 'platform' THEN COALESCE(booking_amount, amount) ELSE 0 END) AS platform")->get();
        $out = [];
        foreach ($rows as $r) {
            $held = round(max(0.0, min((float) $r->platform, (float) $r->everything)), 2);
            if ($held > 0) {
                $out[(int) $r->booking_id] = $held;
            }
        }

        return $out;
    }

    /**
     * What the platform holds that belongs to the business, in the payout currency: for each booking in the payout statuses, the money the
     * platform holds (capped at what is still paid), capped again at the business's own share (price before fees, less commission, plus
     * its service fee). A booking in another currency is converted at today's rate; with no rate it is left out rather than guessed.
     */
    public function payableShare(int $vendorId): float
    {
        $statuses = setting_item_array('vendor_payout_booking_status');
        $held = $this->platformHeldByBooking($vendorId);
        if (empty($statuses) || empty($held)) {
            return 0.0;
        }
        $main = strtoupper((string) (setting_item('currency_main') ?: 'USD'));
        $rates = app(Rates::class);
        $total = 0.0;
        // A booking with no fee or commission recorded has NULLs, which count as zero.
        Booking::query()->whereIn('status', $statuses)->where('vendor_id', $vendorId)->whereIn('id', array_keys($held))
            ->selectRaw('id, currency, (COALESCE(total_before_fees, total, 0) - COALESCE(commission, 0) + COALESCE(vendor_service_fee_amount, 0)) AS vendor_share')->get()->each(function ($b) use ($held, $main, $rates, $vendorId, &$total) {
                $part = max(0.0, min((float) $held[$b->id], (float) $b->vendor_share));
                $cur = strtoupper((string) ($b->currency ?: $main));
                $conv = $cur === $main ? ['amount' => $part] : $rates->convert($part, $cur, $main, null, $vendorId);
                $total += $conv['amount'] ?? 0.0;
            });

        return round($total, 2);
    }

    /** Makes the booking's stored `paid` equal to the ledger, then lets the status and payment schedule follow. */
    public function syncBooking(Booking $booking, bool $allowDowngrade = false): void
    {
        $sum = $this->bookingPaid((int) $booking->id);
        if (abs((float) $booking->paid - $sum) < 0.005) {
            return;
        }
        $refunded = $sum < (float) $booking->paid;
        $booking->paid = $sum;
        self::$syncing = true;
        try {
            app(\Modules\Vendor\Services\BookingPayments::class)->follow($booking, $allowDowngrade || $refunded);
        } finally {
            self::$syncing = false;
        }
        if ($refunded) {
            app(ScheduleSync::class)->refreshStatuses($booking);   // a refund can take the last instalments back to pending
        }
    }

    // ── What a new booking-related entry sets in motion ─────────────────────────

    private function afterBookingMoney(LedgerEntry $entry): void
    {
        $booking = Booking::find($entry->booking_id);
        if (!$booking) {
            return;
        }
        $this->syncBooking($booking);
        // Money that did not come through the booking page (an invoice payment, a gateway) still counts towards the booking's own schedule.
        if ($entry->kind === 'payment' && $entry->source !== 'booking_ledger' && $entry->amount > 0 && !$entry->isReversal()) {
            app(\Modules\Vendor\Services\BookingPayments::class)->settlePlan($booking, (float) $entry->amount, null);
        }
        if ($entry->invoice_id === null) {
            $this->allocateToInvoice($entry, $booking);
        }
    }

    /**
     * What happens on a booking also happens on its live invoice, so both always show the same paid amount: money that comes in is
     * allocated to the invoice, and a refund or reversal takes it back off. One allocation per entry (its key is the payment's
     * reference); it never goes over what the invoice still owes, nor takes it below nothing paid.
     */
    public function allocateToInvoice(LedgerEntry $entry, ?Booking $booking = null): ?Payment
    {
        $inv = Invoice::withoutVendorScope()->where('booking_id', $entry->booking_id)->where('type', 'invoice')->where('status', '!=', 'void')->orderByDesc('id')->first();
        if (!$inv) {
            return null;
        }
        if ($existing = Payment::withoutVendorScope()->where('invoice_id', $inv->id)->where('gateway_ref', $entry->entry_key)->first()) {
            return $existing;
        }
        // The booking was paid in one currency and its invoice is in another: the invoice gets the converted amount and keeps its own currency.
        $signed = (float) $entry->amount;
        $invCur = strtoupper((string) $inv->currency);
        $note = null;
        if ($invCur !== $entry->currency) {
            $c = app(Rates::class)->convert($signed, $entry->currency, $invCur, $entry->occurred_at, (int) $inv->vendor_id);
            if (!$c) {
                return null;   // no rate known: nothing is guessed (the nightly check lists it)
            }
            $note = number_format(abs($signed), 2) . ' ' . $entry->currency . ' at ' . $c['rate'];
            $signed = $c['amount'];
        }
        $confirmed = Payment::withoutVendorScope()->where('invoice_id', $inv->id)->where('status', 'confirmed');
        if ($signed > 0) {
            $room = round(max(0.0, (float) $inv->total - (float) $inv->credit_total - (float) (clone $confirmed)->sum('amount')), 2);
            $amount = min($signed, $room);
        } else {
            // A refund recorded on the booking comes off the invoice too, whichever side the money first came in on; never below nothing paid.
            $paid = round((float) (clone $confirmed)->sum('amount'), 2);
            $amount = -min(-$signed, max(0.0, $paid));
        }
        if (abs($amount) < 0.005) {
            return null;
        }
        $p = Payment::withoutVendorScope()->create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'amount' => $amount, 'method' => $entry->method === 'card' || $entry->held_by === 'platform' ? 'card' : ($entry->method ?: 'other'),
            'reference' => $entry->reference, 'gateway_ref' => $entry->entry_key, 'paid_at' => $entry->occurred_at->toDateString(), 'notes' => ($amount < 0 ? __('Refunded through the booking') : __('Paid through the booking')) . ($note ? ' (' . $note . ')' : ''), 'source' => 'booking', 'status' => 'confirmed']);
        $inv->recalculate();

        return $p;
    }

    private function inBase(int $vendorId, float $amount, string $currency, ?Carbon $at = null): array
    {
        try {
            $settings = Setting::withoutVendorScope()->where('vendor_id', $vendorId)->first();
            $base = $settings ? strtoupper((string) $settings->base_currency) : '';
            if ($base === '') {
                return [null, null];
            }
            $c = app(Rates::class)->convert($amount, $currency, $base, $at, $vendorId);

            return [$base, $c['amount'] ?? null];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }
}
