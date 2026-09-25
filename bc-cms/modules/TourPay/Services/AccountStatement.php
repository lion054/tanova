<?php

namespace Modules\TourPay\Services;

use Carbon\Carbon;
use Modules\TourPay\Models\LedgerEntry;

/**
 * One running account for the business, read straight from the money ledger: what clients paid (on invoices and on bookings),
 * what the platform paid out to the business, and what was paid to suppliers. Each currency has its own balance; nothing is converted.
 *
 * Cash is money in the business's own hands. Money the platform collected on the business's behalf is shown, but is not cash
 * until the platform pays it out (that payout is what lands in the account).
 */
class AccountStatement
{
    public const KINDS = ['invoice' => 'Invoice payment', 'booking' => 'Booking payment', 'payout' => 'Paid out by platform', 'expense' => 'Expense', 'commission' => 'Commission owed to platform'];

    /**
     * @return array{entries:array,totals:array,opening:array,currencies:array,held:array,owed:array}
     *   entries: [{date,kind,ref,who,method,note,cur,in,out,held,balance}] oldest first; balance runs per currency from the opening balance
     *   held: per currency, what the platform holds for the business and has not paid out yet
     *   owed: per currency, commission the business owes the platform on money it collected itself (each currency on its own)
     */
    public function build(Carbon $from, Carbon $to, ?string $kind = null): array
    {
        $start = $from->copy()->startOfDay();

        // Everything before the period is added up by the database, not loaded: the opening cash and what the platform held then.
        $balance = []; $held = [];
        foreach (LedgerEntry::query()->where('occurred_at', '<', $start)->groupBy('currency')
            ->selectRaw("currency, SUM(CASE WHEN held_by = 'vendor' AND kind <> 'commission' THEN amount ELSE 0 END) AS cash, SUM(CASE WHEN held_by = 'platform' THEN amount WHEN kind = 'payout' THEN -amount ELSE 0 END) AS held")->get() as $r) {
            $balance[$r->currency] = (float) $r->cash;
            $held[$r->currency] = (float) $r->held;
        }
        $opening = array_map(fn ($v) => round($v, 2), $balance);

        $rows = LedgerEntry::query()->whereBetween('occurred_at', [$start, $to->copy()->endOfDay()])->orderBy('occurred_at')->orderBy('id')->get();
        $labels = $this->labels($rows);

        $entries = []; $totals = [];
        foreach ($rows as $r) {
            $cur = $r->currency; $amt = (float) $r->amount;
            $isOwed = $r->kind === 'commission';   // owed to the platform, or settled: an account between the two, not cash in or out
            $isHeld = $r->heldByPlatform();
            $type = $this->typeOf($r);
            if (!$isHeld && !$isOwed) { $balance[$cur] = ($balance[$cur] ?? 0) + $amt; }
            // What the platform holds: what it collected, less what it has paid out.
            if ($isHeld) { $held[$cur] = ($held[$cur] ?? 0) + $amt; } elseif ($r->kind === 'payout') { $held[$cur] = ($held[$cur] ?? 0) - $amt; }

            $t = &$totals[$cur];
            $t ??= ['invoice' => 0.0, 'booking' => 0.0, 'payout' => 0.0, 'expense' => 0.0, 'net' => 0.0];
            if (!$isHeld && !$isOwed) {
                $t[$type] += $type === 'expense' ? -$amt : $amt;   // expenses are shown as what was spent
                $t['net'] += $amt;
            }
            unset($t);
            if ($kind === null || $kind === $type) {
                [$ref, $who] = $labels[$r->id];
                $entries[] = ['date' => $r->occurred_at->copy(), 'kind' => $type, 'ref' => $ref, 'who' => $who, 'method' => (string) $r->method, 'note' => (string) ($r->isReversal() ? __('Reversal') : ($r->kind === 'refund' ? __('Refund') : '')),
                    'cur' => $cur, 'in' => !$isHeld && !$isOwed && $amt > 0 ? $amt : 0.0, 'out' => !$isHeld && !$isOwed && $amt < 0 ? -$amt : 0.0, 'held' => $isHeld ? $amt : 0.0, 'owed' => $isOwed ? -$amt : 0.0, 'balance' => round($balance[$cur] ?? 0, 2)];
            }
        }
        foreach ($totals as &$t) { $t = array_map(fn ($v) => round($v, 2), $t); }
        unset($t);
        $owed = app(Commission::class)->owed((int) resolve_current_vendor_id());
        $currencies = array_values(array_unique(array_merge(array_keys($opening), array_keys($totals), array_keys($balance), array_keys($held), array_keys($owed))));
        sort($currencies);

        return ['entries' => $entries, 'totals' => $totals, 'opening' => $opening, 'currencies' => $currencies, 'held' => array_map(fn ($v) => round($v, 2), array_filter($held, fn ($v) => abs($v) > 0.004)), 'owed' => $owed];
    }

    private function typeOf(LedgerEntry $r): string
    {
        return match (true) {
            $r->kind === 'expense' => 'expense',
            $r->kind === 'payout'  => 'payout',
            $r->kind === 'commission' => 'commission',
            $r->invoice_id !== null => 'invoice',
            default                => 'booking',
        };
    }

    /** Reference and name for each row (invoice number and client, booking code and guest, supplier and bill). @return array<int,array{0:string,1:string}> */
    private function labels($rows): array
    {
        $inv = \Modules\TourPay\Models\Invoice::withoutVendorScope()->withTrashed()->whereIn('id', $rows->pluck('invoice_id')->filter()->unique())->get(['id', 'invoice_number', 'client_name'])->keyBy('id');
        $bill = \Modules\TourPay\Models\Bill::withoutVendorScope()->withTrashed()->whereIn('id', $rows->pluck('bill_id')->filter()->unique())->get(['id', 'reference', 'supplier_name'])->keyBy('id');
        $book = \Modules\Booking\Models\Booking::whereIn('id', $rows->pluck('booking_id')->filter()->unique())->get(['id', 'code', 'first_name', 'last_name'])->keyBy('id');
        $out = [];
        foreach ($rows as $r) {
            $out[$r->id] = match (true) {
                $r->invoice_id !== null && isset($inv[$r->invoice_id]) => [$inv[$r->invoice_id]->invoice_number, (string) $inv[$r->invoice_id]->client_name],
                $r->bill_id !== null && isset($bill[$r->bill_id])       => [$bill[$r->bill_id]->reference ?: ('BILL-' . $r->bill_id), (string) $bill[$r->bill_id]->supplier_name],
                $r->booking_id !== null && isset($book[$r->booking_id]) => [$book[$r->booking_id]->code ?: ('#' . $r->booking_id), trim($book[$r->booking_id]->first_name . ' ' . $book[$r->booking_id]->last_name)],
                $r->kind === 'payout'                                  => ['PAYOUT-' . $r->payout_id, (string) ($r->method ?: __('Platform'))],
                default                                                => [(string) $r->reference, (string) $r->note],
            };
        }

        return $out;
    }
}
