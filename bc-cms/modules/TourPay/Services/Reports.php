<?php

namespace Modules\TourPay\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Payment;

/** The numbers a vendor asks their books: who owes what and how late, what came in, tax collected, and one client's account. Vendor-scoped by the models. */
class Reports
{
    public const BUCKETS = ['current' => 'Not due yet', 'd30' => '1 to 30 days late', 'd60' => '31 to 60 days late', 'd90' => '61 to 90 days late', 'd90p' => 'Over 90 days late'];

    /** Open invoices, by currency and how late they are. @return array<string,array{buckets:array,total:float,clients:array}> */
    public function receivables(): array
    {
        $today = now()->startOfDay();
        $out = [];
        $open = Invoice::where('type', 'invoice')->whereNotIn('status', ['paid', 'void', 'draft', 'credited'])->get()->filter(fn ($i) => $i->balance() > 0);
        foreach ($open as $i) {
            $cur = $i->currency;
            $out[$cur] ??= ['buckets' => array_fill_keys(array_keys(self::BUCKETS), 0.0), 'total' => 0.0, 'clients' => []];
            $late = $i->due_date ? (int) $i->due_date->copy()->startOfDay()->diffInDays($today, false) : 0;
            $b = $late <= 0 ? 'current' : ($late <= 30 ? 'd30' : ($late <= 60 ? 'd60' : ($late <= 90 ? 'd90' : 'd90p')));
            $bal = $i->balance();
            $out[$cur]['buckets'][$b] += $bal;
            $out[$cur]['total'] += $bal;
            $key = $i->client_email ?: $i->client_name;
            $c = &$out[$cur]['clients'][$key];
            $c ??= ['name' => $i->client_name, 'email' => $i->client_email, 'buckets' => array_fill_keys(array_keys(self::BUCKETS), 0.0), 'total' => 0.0, 'invoices' => 0];
            $c['buckets'][$b] += $bal;
            $c['total'] += $bal;
            $c['invoices']++;
            unset($c);
        }
        foreach ($out as $cur => &$row) {
            uasort($row['clients'], fn ($a, $b) => $b['total'] <=> $a['total']);
            $row['total'] = round($row['total'], 2);
            $row['buckets'] = array_map(fn ($v) => round($v, 2), $row['buckets']);
        }

        return $out;
    }

    /** Money in by month and currency (refunds count against it), and what was invoiced. */
    public function revenue(Carbon $from, Carbon $to): array
    {
        $paid = Payment::query()->join('bc_tourpay_invoices as inv', 'inv.id', '=', 'bc_tourpay_payments.invoice_id')->where('bc_tourpay_payments.status', 'confirmed')
            ->whereBetween('bc_tourpay_payments.paid_at', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(bc_tourpay_payments.paid_at, '%Y-%m') AS ym, inv.currency AS cur, SUM(bc_tourpay_payments.amount) AS amt, COUNT(*) AS n")->groupBy('ym', 'cur')->orderBy('ym')->get();
        $invoiced = Invoice::where('type', 'invoice')->whereNotIn('status', ['draft', 'void'])->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_FORMAT(issue_date, '%Y-%m') AS ym, currency AS cur, SUM(total - credit_total) AS amt, COUNT(*) AS n")->groupBy('ym', 'cur')->orderBy('ym')->get();
        $rows = [];
        foreach ($invoiced as $r) {
            $rows[$r->ym][$r->cur]['invoiced'] = round((float) $r->amt, 2);
            $rows[$r->ym][$r->cur]['invoices'] = (int) $r->n;
        }
        foreach ($paid as $r) {
            $rows[$r->ym][$r->cur]['received'] = round((float) $r->amt, 2);
        }
        ksort($rows);

        return $rows;
    }

    /** Tax on invoices issued in the period, by tax and currency. Credit notes take it back. */
    public function tax(Carbon $from, Carbon $to): array
    {
        $out = [];
        $docs = Invoice::whereIn('type', ['invoice', 'credit_note'])->whereNotIn('status', ['draft', 'void'])->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->get();
        foreach ($docs as $d) {
            $sign = $d->type === 'credit_note' ? -1 : 1;
            $lines = $d->tax_lines ?: ($d->tax_amount > 0 ? [['name' => 'VAT', 'rate' => (float) $d->tax_rate, 'amount' => (float) $d->tax_amount]] : []);
            $net = (float) ($d->tax_mode === 'exclusive' ? $d->total - $d->tax_amount : $d->subtotal);
            foreach ($lines as $l) {
                $k = $d->currency . '|' . $l['name'] . '|' . $l['rate'];
                $out[$k] ??= ['currency' => $d->currency, 'name' => $l['name'], 'rate' => (float) $l['rate'], 'taxable' => 0.0, 'tax' => 0.0, 'documents' => 0];
                $out[$k]['taxable'] += $sign * $net;
                $out[$k]['tax'] += $sign * (float) $l['amount'];
                $out[$k]['documents']++;
            }
        }
        foreach ($out as &$r) {
            $r['taxable'] = round($r['taxable'], 2);
            $r['tax'] = round($r['tax'], 2);
        }

        return array_values($out);
    }

    /** One client's account: what was billed, credited and paid, with a running balance per currency. */
    public function statement(string $client): array
    {
        $docs = Invoice::whereIn('type', ['invoice', 'credit_note'])->whereNotIn('status', ['draft', 'void'])
            ->where(fn ($q) => $q->where('client_email', $client)->orWhere('client_name', $client))->orderBy('issue_date')->orderBy('id')->get();
        $events = [];
        foreach ($docs as $d) {
            if ($d->type === 'invoice') {
                $events[] = ['date' => $d->issue_date, 'kind' => 'invoice', 'ref' => $d->invoice_number, 'cur' => $d->currency, 'debit' => (float) $d->total, 'credit' => 0.0, 'sort' => 1];
                foreach ($d->payments()->where('status', 'confirmed')->get() as $p) {
                    $events[] = ['date' => $p->paid_at, 'kind' => $p->amount < 0 ? 'refund' : 'payment', 'ref' => $d->invoice_number . ($p->reference ? ' · ' . $p->reference : ''), 'cur' => $d->currency, 'debit' => $p->amount < 0 ? abs((float) $p->amount) : 0.0, 'credit' => $p->amount > 0 ? (float) $p->amount : 0.0, 'sort' => 2];
                }
            } else {
                $events[] = ['date' => $d->issue_date, 'kind' => 'credit note', 'ref' => $d->invoice_number, 'cur' => $d->currency, 'debit' => 0.0, 'credit' => (float) $d->total, 'sort' => 1];
            }
        }
        usort($events, fn ($a, $b) => [$a['date']->timestamp, $a['sort']] <=> [$b['date']->timestamp, $b['sort']]);
        $run = [];
        foreach ($events as &$e) {
            $run[$e['cur']] = ($run[$e['cur']] ?? 0.0) + $e['debit'] - $e['credit'];
            $e['balance'] = round($run[$e['cur']], 2);
        }

        return ['events' => $events, 'balances' => array_map(fn ($v) => round($v, 2), $run)];
    }

    /** Clients with anything on the books, for the statement picker. */
    public function clients(): array
    {
        return Invoice::whereIn('type', ['invoice', 'credit_note'])->select('client_name', 'client_email')->distinct()->orderBy('client_name')->limit(500)->get()
            ->map(fn ($r) => ['key' => $r->client_email ?: $r->client_name, 'label' => $r->client_name . ($r->client_email ? ' · ' . $r->client_email : '')])->unique('key')->values()->all();
    }
}
