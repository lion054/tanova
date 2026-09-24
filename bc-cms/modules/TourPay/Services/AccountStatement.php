<?php

namespace Modules\TourPay\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Models\Payment;
use Modules\Vendor\Models\VendorPayout;

/**
 * One running account for the business: what clients paid on invoices, what the platform paid out to the vendor, and what
 * was paid to suppliers (expenses). Each currency has its own balance; nothing is converted.
 *
 * Money in:  confirmed invoice payments (refunds are negative payments, so they come off) and payouts the platform has paid.
 * Money out: payments on supplier bills.
 * Vendor-scoped by the models (bc_payouts is filtered explicitly).
 */
class AccountStatement
{
    public const KINDS = ['invoice' => 'Invoice payment', 'payout' => 'Paid out by platform', 'expense' => 'Expense'];

    /**
     * @return array{entries:array,totals:array,opening:array,currencies:array}
     *   entries: [{date,kind,ref,who,method,cur,in,out,balance}] oldest first, balance running per currency starting from the opening balance
     */
    public function build(Carbon $from, Carbon $to, ?string $kind = null): array
    {
        $vendorId = (int) resolve_current_vendor_id();
        $all = $this->events($vendorId, $to);

        // Opening balance: everything before the period, per currency.
        $balance = []; $opening = [];
        foreach ($all as $e) {
            if ($e['date']->lt($from->copy()->startOfDay())) { $balance[$e['cur']] = ($balance[$e['cur']] ?? 0) + $e['in'] - $e['out']; }
        }
        $opening = array_map(fn ($v) => round($v, 2), $balance);

        $entries = []; $totals = [];
        foreach ($all as $e) {
            if ($e['date']->lt($from->copy()->startOfDay())) { continue; }
            $balance[$e['cur']] = ($balance[$e['cur']] ?? 0) + $e['in'] - $e['out'];
            $t = &$totals[$e['cur']];
            $t ??= ['invoice' => 0.0, 'payout' => 0.0, 'expense' => 0.0, 'net' => 0.0];
            $t[$e['kind']] += $e['kind'] === 'expense' ? $e['out'] : $e['in'] - $e['out'];   // expenses are shown as what was spent
            $t['net'] += $e['in'] - $e['out'];
            unset($t);
            if ($kind === null || $kind === $e['kind']) {
                $entries[] = $e + ['balance' => round($balance[$e['cur']], 2)];
            }
        }
        foreach ($totals as &$t) { $t = array_map(fn ($v) => round($v, 2), $t); }
        unset($t);
        $currencies = array_values(array_unique(array_merge(array_keys($opening), array_keys($totals))));
        sort($currencies);

        return ['entries' => $entries, 'totals' => $totals, 'opening' => $opening, 'currencies' => $currencies];
    }

    /** Every movement up to $to, oldest first. */
    private function events(int $vendorId, Carbon $to): array
    {
        $out = [];

        $payments = Payment::query()->join('bc_tourpay_invoices as inv', 'inv.id', '=', 'bc_tourpay_payments.invoice_id')
            ->where('bc_tourpay_payments.status', 'confirmed')->where('bc_tourpay_payments.paid_at', '<=', $to->toDateString())
            ->select('bc_tourpay_payments.*', 'inv.invoice_number', 'inv.client_name', 'inv.currency as cur')->get();
        foreach ($payments as $p) {
            $amount = (float) $p->amount;
            $out[] = ['date' => $p->paid_at->copy(), 'kind' => 'invoice', 'ref' => $p->invoice_number, 'who' => $p->client_name, 'method' => (string) $p->method,
                'note' => $amount < 0 ? __('Refund') : '', 'cur' => (string) $p->cur, 'in' => $amount > 0 ? $amount : 0.0, 'out' => $amount < 0 ? -$amount : 0.0, 'sort' => $p->id];
        }

        $bills = BillPayment::query()->join('bc_tourpay_bills as b', 'b.id', '=', 'bc_tourpay_bill_payments.bill_id')->whereNull('b.deleted_at')->where('b.status', '!=', 'void')
            ->where('bc_tourpay_bill_payments.paid_at', '<=', $to->toDateString())
            ->select('bc_tourpay_bill_payments.*', 'b.supplier_name', 'b.reference as bill_ref', 'b.description', 'b.currency as cur')->get();
        foreach ($bills as $b) {
            $out[] = ['date' => Carbon::parse($b->paid_at), 'kind' => 'expense', 'ref' => $b->bill_ref ?: ('BILL-' . $b->bill_id), 'who' => $b->supplier_name, 'method' => (string) $b->method,
                'note' => (string) ($b->notes ?: $b->description), 'cur' => (string) $b->cur, 'in' => 0.0, 'out' => (float) $b->amount, 'sort' => $b->id];
        }

        $main = strtoupper((string) (setting_item('currency_main') ?: 'USD'));
        foreach (VendorPayout::query()->where('vendor_id', $vendorId)->where('status', 'paid')->get() as $p) {
            $date = $p->pay_date ? Carbon::parse($p->pay_date) : Carbon::parse($p->updated_at);
            if ($date->gt($to)) { continue; }
            $out[] = ['date' => $date, 'kind' => 'payout', 'ref' => 'PAYOUT-' . $p->id, 'who' => $p->payout_method_name ?: __('Platform'), 'method' => (string) $p->payout_method,
                'note' => (string) $p->note_to_vendor, 'cur' => $main, 'in' => (float) $p->amount, 'out' => 0.0, 'sort' => $p->id];
        }

        usort($out, fn ($a, $b) => [$a['date']->timestamp, $a['kind'], $a['sort']] <=> [$b['date']->timestamp, $b['kind'], $b['sort']]);

        return $out;
    }
}
