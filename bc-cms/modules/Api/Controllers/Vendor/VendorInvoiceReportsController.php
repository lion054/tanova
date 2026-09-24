<?php

namespace Modules\Api\Controllers\Vendor;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TourPay\Services\Reports;

/** Who owes you and how late, what came in, the tax you collected, and one client's account. */
class VendorInvoiceReportsController extends VendorApiController
{
    public function __construct(private Reports $reports) {}

    public function receivables(): JsonResponse
    {
        $out = [];
        foreach ($this->reports->receivables() as $cur => $r) {
            $out[] = ['currency' => $cur, 'total' => $r['total'], 'buckets' => $r['buckets'], 'clients' => array_values(array_map(fn ($c) => ['name' => $c['name'], 'email' => $c['email'], 'invoices' => $c['invoices'], 'total' => round($c['total'], 2), 'buckets' => array_map(fn ($v) => round($v, 2), $c['buckets'])], $r['clients']))];
        }

        return $this->success($out);
    }

    public function revenue(Request $request): JsonResponse
    {
        [$from, $to] = $this->period($request);
        $rows = [];
        foreach ($this->reports->revenue($from, $to) as $ym => $byCur) {
            foreach ($byCur as $cur => $r) {
                $rows[] = ['month' => $ym, 'currency' => $cur, 'invoices' => $r['invoices'] ?? 0, 'invoiced' => $r['invoiced'] ?? 0.0, 'received' => $r['received'] ?? 0.0];
            }
        }

        return $this->success($rows);
    }

    public function tax(Request $request): JsonResponse
    {
        [$from, $to] = $this->period($request);

        return $this->success($this->reports->tax($from, $to));
    }

    public function statement(Request $request): JsonResponse
    {
        $request->validate(['client' => ['required', 'string', 'max:255']]);
        $s = $this->reports->statement((string) $request->query('client'));

        return $this->success([
            'events' => array_map(fn ($e) => ['date' => $e['date']->toDateString(), 'type' => $e['kind'], 'reference' => $e['ref'], 'currency' => $e['cur'], 'billed' => $e['debit'], 'paid_or_credited' => $e['credit'], 'balance' => $e['balance']], $s['events']),
            'balances' => (object) $s['balances'],
        ]);
    }

    private function period(Request $request): array
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfYear();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        return [$from, $to->lt($from) ? $from->copy()->endOfDay() : $to];
    }
}
