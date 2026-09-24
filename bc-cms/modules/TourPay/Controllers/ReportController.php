<?php
namespace Modules\TourPay\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\FrontendController;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\Reports;

/** TourPay reports: receivables, revenue, tax and client statements, on screen and as CSV. */
class ReportController extends FrontendController
{
    public function __construct(private Reports $reports)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $tab = in_array($request->query('tab'), ['receivables', 'revenue', 'tax', 'profit', 'statement'], true) ? $request->query('tab') : 'receivables';
        $data = ['tab' => $tab, 'from' => $from, 'to' => $to, 'buckets' => Reports::BUCKETS, 'client' => '', 'page_title' => __('TourPay reports'), 'settings' => Setting::forVendor((int) resolve_current_vendor_id())];
        $data += match ($tab) {
            'revenue'    => ['revenue' => $this->reports->revenue($from, $to)],
            'tax'        => ['tax' => $this->reports->tax($from, $to)],
            'profit'     => ['profit' => app(\Modules\TourPay\Services\Profit::class)->withBills()],
            'statement'  => ['clients' => $this->reports->clients(), 'client' => (string) $request->query('client', ''), 'statement' => $request->filled('client') ? $this->reports->statement((string) $request->query('client')) : null],
            default      => ['receivables' => $this->reports->receivables()],
        };

        return view('TourPay::frontend.reports', $data);
    }

    public function csv(Request $request, string $report)
    {
        [$from, $to] = $this->period($request);
        $rows = [];
        switch ($report) {
            case 'receivables':
                $rows[] = array_merge(['Currency', 'Client', 'E-mail', 'Invoices'], array_values(Reports::BUCKETS), ['Total']);
                foreach ($this->reports->receivables() as $cur => $r) {
                    foreach ($r['clients'] as $c) {
                        $rows[] = array_merge([$cur, $c['name'], $c['email'], $c['invoices']], array_map(fn ($v) => number_format($v, 2, '.', ''), array_values($c['buckets'])), [number_format($c['total'], 2, '.', '')]);
                    }
                }
                break;
            case 'revenue':
                $rows[] = ['Month', 'Currency', 'Invoices', 'Invoiced', 'Received'];
                foreach ($this->reports->revenue($from, $to) as $ym => $byCur) {
                    foreach ($byCur as $cur => $r) {
                        $rows[] = [$ym, $cur, $r['invoices'] ?? 0, number_format($r['invoiced'] ?? 0, 2, '.', ''), number_format($r['received'] ?? 0, 2, '.', '')];
                    }
                }
                break;
            case 'tax':
                $rows[] = ['Currency', 'Tax', 'Rate %', 'Taxable amount', 'Tax', 'Documents'];
                foreach ($this->reports->tax($from, $to) as $r) {
                    $rows[] = [$r['currency'], $r['name'], $r['rate'], number_format($r['taxable'], 2, '.', ''), number_format($r['tax'], 2, '.', ''), $r['documents']];
                }
                break;
            case 'profit':
                $rows[] = ['Booking', 'Guest', 'Currency', 'Revenue', 'Supplier costs', 'Profit', 'Margin %'];
                foreach (app(\Modules\TourPay\Services\Profit::class)->withBills() as $p) {
                    $rows[] = [$p['booking']->code ?: '#' . $p['booking']->id, trim($p['booking']->first_name . ' ' . $p['booking']->last_name), $p['mixed'] ? 'mixed' : $p['currency'], number_format($p['revenue'], 2, '.', ''), number_format($p['cost'], 2, '.', ''), number_format($p['profit'], 2, '.', ''), $p['margin'] ?? ''];
                }
                break;
            case 'statement':
                abort_unless($request->filled('client'), 404);
                $rows[] = ['Date', 'Type', 'Reference', 'Currency', 'Debit', 'Credit', 'Balance'];
                foreach ($this->reports->statement((string) $request->query('client'))['events'] as $e) {
                    $rows[] = [$e['date']->toDateString(), $e['kind'], $e['ref'], $e['cur'], number_format($e['debit'], 2, '.', ''), number_format($e['credit'], 2, '.', ''), number_format($e['balance'], 2, '.', '')];
                }
                break;
            default:
                abort(404);
        }

        return response()->streamDownload(function () use ($rows) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF");   // so Excel reads it as UTF-8
            foreach ($rows as $r) {
                // A cell that starts like a formula is text, not a formula.
                fputcsv($h, array_map(fn ($c) => is_string($c) && preg_match('/^[=+\-@\t\r]/', $c) && !is_numeric($c) ? "'" . $c : $c, $r));
            }
            fclose($h);
        }, 'tourpay-' . $report . '-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function period(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfYear();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        return [$from, $to->lt($from) ? $from->copy()->endOfDay() : $to];
    }
}
