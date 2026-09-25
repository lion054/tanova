<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Payment;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\VendorPayout;

/**
 * Checks that every total agrees with the ledger, and says so loudly when it does not.
 *
 * Every check is one set-based query (a join or a grouped sum), never a query per row, so the whole run stays a matter of seconds for
 * hundreds of thousands of payments. It never edits the ledger to make a number fit. `--fix` only does the safe things: writes a missing
 * row for a fact that exists in its source table, reverses a row whose source is gone, and refreshes a stored total from the rows behind it.
 */
class MoneyReconcile
{
    public const CACHE_KEY = 'money.reconcile';
    private const CAP = 2000;   // a run reports at most this many of each kind (it says "2000+" past that)

    /** @return array{ok:bool,ran_at:string,problems:array<string,int>,samples:array<string,array>,fixed:int,protected:bool} */
    public function run(bool $fix = false): array
    {
        $problems = []; $samples = []; $fixed = 0;
        $found = function (string $type, array $rows, callable $label) use (&$problems, &$samples) {
            $n = count($rows);
            $problems[$type] = $n >= self::CAP ? self::CAP : $n;
            $samples[$type] = array_slice(array_map($label, $rows), 0, 5);
        };
        $ledger = app(Ledger::class);
        $cap = self::CAP;

        // 1. Every confirmed fact in a source table has its ledger row.
        $missing = [
            'payment_missing_in_ledger' => [DB::select("SELECT p.id FROM bc_tourpay_payments p LEFT JOIN bc_money_ledger l ON l.source = 'tourpay_payment' AND l.source_id = p.id AND l.reverses_id IS NULL
                WHERE p.status = 'confirmed' AND p.source <> 'booking' AND l.id IS NULL LIMIT {$cap}"), 'payment #', fn ($id) => LedgerHooks::mirrorPayment(Payment::withoutVendorScope()->find($id))],
            'bill_payment_missing_in_ledger' => [DB::select("SELECT p.id FROM bc_tourpay_bill_payments p LEFT JOIN bc_money_ledger l ON l.source = 'bill_payment' AND l.source_id = p.id AND l.reverses_id IS NULL
                WHERE l.id IS NULL LIMIT {$cap}"), 'bill payment #', fn ($id) => LedgerHooks::mirrorBillPayment(BillPayment::withoutVendorScope()->find($id))],
            'payout_missing_in_ledger' => [DB::select("SELECT p.id FROM bc_payouts p LEFT JOIN bc_money_ledger l ON l.source = 'payout' AND l.source_id = p.id AND l.reverses_id IS NULL
                WHERE p.status = 'paid' AND l.id IS NULL LIMIT {$cap}"), 'payout #', fn ($id) => LedgerHooks::mirrorPayout(VendorPayout::find($id))],
            'booking_entry_missing_in_ledger' => [DB::select("SELECT p.id FROM bc_booking_ledger p JOIN bc_bookings b ON b.id = p.booking_id LEFT JOIN bc_money_ledger l ON l.source = 'booking_ledger' AND l.source_id = p.id AND l.reverses_id IS NULL
                WHERE l.id IS NULL LIMIT {$cap}"), 'booking entry #', fn ($id) => ($l = BookingLedger::withoutVendorScope()->find($id)) && ($b = Booking::find($l->booking_id)) ? LedgerHooks::mirrorBookingLedger($l, $b) : null],
        ];
        foreach ($missing as $type => [$rows, $label, $repair]) {
            if ($fix) { foreach ($rows as $r) { $repair($r->id); $fixed++; } continue; }
            if ($rows) { $found($type, $rows, fn ($r) => $label . $r->id); }
        }

        // 2. Every ledger row still has its source (else the source was deleted behind the ledger's back), unless it was reversed.
        $orphans = DB::select("SELECT l.id, l.entry_key FROM bc_money_ledger l WHERE l.reverses_id IS NULL AND l.source IN ('tourpay_payment', 'bill_payment', 'booking_ledger')
            AND NOT EXISTS (SELECT 1 FROM bc_money_ledger r WHERE r.reverses_id = l.id)
            AND ((l.source = 'tourpay_payment' AND NOT EXISTS (SELECT 1 FROM bc_tourpay_payments p WHERE p.id = l.source_id AND p.status = 'confirmed'))
              OR (l.source = 'bill_payment' AND NOT EXISTS (SELECT 1 FROM bc_tourpay_bill_payments p WHERE p.id = l.source_id))
              OR (l.source = 'booking_ledger' AND NOT EXISTS (SELECT 1 FROM bc_booking_ledger p WHERE p.id = l.source_id))) LIMIT {$cap}");
        if ($fix) { foreach ($orphans as $o) { $ledger->reverse($o->entry_key, null, __('Source record no longer exists')); $fixed++; } }
        elseif ($orphans) { $found('ledger_entry_without_source', $orphans, fn ($o) => $o->entry_key); }

        // 3. A booking's stored paid amount equals its rows (in the booking's own currency).
        $bookings = DB::select("SELECT b.id, COALESCE(b.paid, 0) AS paid_stored, GREATEST(0, ROUND(COALESCE(x.s, 0), 2)) AS ledger FROM bc_bookings b
            LEFT JOIN (SELECT booking_id, SUM(COALESCE(booking_amount, amount)) AS s FROM bc_money_ledger WHERE kind IN ('payment', 'refund') AND booking_id IS NOT NULL GROUP BY booking_id) x ON x.booking_id = b.id
            WHERE ABS(COALESCE(b.paid, 0) - GREATEST(0, COALESCE(x.s, 0))) > 0.005 LIMIT {$cap}");
        if ($fix) { foreach ($bookings as $r) { Booking::where('id', $r->id)->update(['paid' => $r->ledger]); $fixed++; } }
        elseif ($bookings) { $found('booking_paid_differs_from_ledger', $bookings, fn ($r) => "booking #{$r->id}: stored " . number_format((float) $r->paid_stored, 2) . ', ledger ' . number_format((float) $r->ledger, 2)); }

        // 4. An invoice's stored paid amount equals its confirmed payments; nobody has paid an invoice more than it is worth.
        $invoices = DB::select("SELECT i.id, i.invoice_number, i.amount_paid AS paid_stored, COALESCE(x.s, 0) AS paid, (i.total - i.credit_total) AS payable, i.status FROM bc_tourpay_invoices i
            LEFT JOIN (SELECT invoice_id, SUM(amount) AS s FROM bc_tourpay_payments WHERE status = 'confirmed' GROUP BY invoice_id) x ON x.invoice_id = i.id
            WHERE i.type = 'invoice' AND i.deleted_at IS NULL AND (ABS(i.amount_paid - COALESCE(x.s, 0)) > 0.005 OR (COALESCE(x.s, 0) - (i.total - i.credit_total) > 0.01 AND i.status <> 'void')) LIMIT {$cap}");
        $drift = array_filter($invoices, fn ($r) => abs((float) $r->paid_stored - (float) $r->paid) > 0.005);
        $over = array_filter($invoices, fn ($r) => (float) $r->paid - (float) $r->payable > 0.01 && $r->status !== 'void');
        if ($fix) { foreach ($drift as $r) { Invoice::withoutVendorScope()->find($r->id)?->recalculate(); $fixed++; } }
        elseif ($drift) { $found('invoice_paid_differs_from_payments', array_values($drift), fn ($r) => "{$r->invoice_number}: stored " . number_format((float) $r->paid_stored, 2) . ', payments ' . number_format((float) $r->paid, 2)); }
        if ($over) { $found('invoice_overpaid', array_values($over), fn ($r) => "{$r->invoice_number}: paid " . number_format((float) $r->paid, 2) . ' of ' . number_format((float) $r->payable, 2)); }

        // 5. A bill's stored paid amount equals its payments.
        $bills = DB::select("SELECT b.id FROM bc_tourpay_bills b LEFT JOIN (SELECT bill_id, SUM(amount) AS s FROM bc_tourpay_bill_payments GROUP BY bill_id) x ON x.bill_id = b.id
            WHERE ABS(b.amount_paid - COALESCE(x.s, 0)) > 0.005 LIMIT {$cap}");
        if ($fix) { foreach ($bills as $r) { Bill::withoutVendorScope()->find($r->id)?->recalculate(); $fixed++; } }
        elseif ($bills) { $found('bill_paid_differs_from_payments', $bills, fn ($r) => "bill #{$r->id}"); }

        // 6. An invoice made from a booking shows the same money as the booking (same currency only: others are converted, not equal).
        $pairs = DB::select("SELECT i.id, i.invoice_number, b.id AS booking, COALESCE(ip.s, 0) AS invoice_paid, GREATEST(0, COALESCE(bp.s, 0)) AS booking_paid
            FROM bc_tourpay_invoices i JOIN bc_bookings b ON b.id = i.booking_id
            LEFT JOIN (SELECT invoice_id, SUM(amount) AS s FROM bc_tourpay_payments WHERE status = 'confirmed' GROUP BY invoice_id) ip ON ip.invoice_id = i.id
            LEFT JOIN (SELECT booking_id, SUM(COALESCE(booking_amount, amount)) AS s FROM bc_money_ledger WHERE kind IN ('payment', 'refund') AND booking_id IS NOT NULL GROUP BY booking_id) bp ON bp.booking_id = b.id
            WHERE i.type = 'invoice' AND i.deleted_at IS NULL AND i.status NOT IN ('void', 'draft') AND (b.currency IS NULL OR b.currency = '' OR b.currency = i.currency)
            AND ABS(COALESCE(ip.s, 0) - GREATEST(0, COALESCE(bp.s, 0))) > 0.01 LIMIT {$cap}");
        if ($pairs) { $found('invoice_and_booking_disagree', $pairs, fn ($r) => "{$r->invoice_number}: invoice " . number_format((float) $r->invoice_paid, 2) . ", booking #{$r->booking} " . number_format((float) $r->booking_paid, 2)); }

        // 7. An invoice payment for a booking in another currency that could not be converted (no rate known) is listed, not guessed.
        $unlinked = DB::select("SELECT l.id, l.entry_key, l.currency FROM bc_money_ledger l JOIN bc_tourpay_invoices i ON i.id = l.invoice_id
            WHERE l.source = 'tourpay_payment' AND l.reverses_id IS NULL AND l.booking_id IS NULL AND i.booking_id IS NOT NULL LIMIT {$cap}");
        if ($unlinked) { $found('payment_not_linked_to_booking_no_rate', $unlinked, fn ($r) => $r->entry_key . ' (' . $r->currency . ')'); }

        // 8. In production the database itself must refuse to change the ledger.
        $protected = $this->protectedByDatabase();
        $result = ['ok' => empty($problems), 'ran_at' => now()->toIso8601String(), 'problems' => $problems, 'samples' => $samples, 'fixed' => $fixed, 'protected' => $protected];
        Cache::forever(self::CACHE_KEY, $result);

        return $result;
    }

    /** True when both append-only triggers exist on the ledger table. */
    public function protectedByDatabase(): bool
    {
        try {
            return (int) DB::selectOne("SELECT COUNT(*) AS c FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND event_object_table = 'bc_money_ledger'")->c >= 2;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
