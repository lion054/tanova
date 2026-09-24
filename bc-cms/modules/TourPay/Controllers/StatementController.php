<?php
namespace Modules\TourPay\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\FrontendController;
use Modules\TourPay\Services\AccountStatement;

/** Finance > Statement: invoice payments in, platform payouts, and expenses, in one running account. */
class StatementController extends FrontendController
{
    public function __construct(private AccountStatement $statement)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        [$from, $to, $kind, $cur] = $this->filters($request);
        $data = $this->statement->build($from, $to, $kind);
        $data['entries'] = $cur ? array_values(array_filter($data['entries'], fn ($e) => $e['cur'] === $cur)) : $data['entries'];

        return view('TourPay::frontend.statement', $data + ['from' => $from, 'to' => $to, 'kind' => $kind, 'cur' => $cur, 'kinds' => AccountStatement::KINDS, 'page_title' => __('Statement')]);
    }

    public function csv(Request $request)
    {
        [$from, $to, $kind, $cur] = $this->filters($request);
        $data = $this->statement->build($from, $to, $kind);
        $rows = [['Date', 'Type', 'Reference', 'Name', 'Method', 'Note', 'Currency', 'Money in', 'Money out', 'Balance']];
        foreach ($data['opening'] as $c => $v) {
            if (!$cur || $cur === $c) { $rows[] = [$from->toDateString(), 'Opening balance', '', '', '', '', $c, '', '', number_format($v, 2, '.', '')]; }
        }
        foreach ($data['entries'] as $e) {
            if ($cur && $e['cur'] !== $cur) { continue; }
            $rows[] = [$e['date']->toDateString(), AccountStatement::KINDS[$e['kind']], $e['ref'], $e['who'], $e['method'], $e['note'], $e['cur'],
                $e['in'] ? number_format($e['in'], 2, '.', '') : '', $e['out'] ? number_format($e['out'], 2, '.', '') : '', number_format($e['balance'], 2, '.', '')];
        }

        return response()->streamDownload(function () use ($rows) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF");
            foreach ($rows as $r) {
                // A cell that starts like a formula is text, not a formula.
                fputcsv($h, array_map(fn ($c) => is_string($c) && preg_match('/^[=+\-@\t\r]/', $c) && !is_numeric($c) ? "'" . $c : $c, $r));
            }
            fclose($h);
        }, 'statement-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        $kind = array_key_exists((string) $request->query('kind'), AccountStatement::KINDS) ? (string) $request->query('kind') : null;
        $cur = preg_match('/^[A-Z]{3}$/', (string) $request->query('cur')) ? (string) $request->query('cur') : null;

        return [$from, $to->lt($from) ? $from->copy()->endOfDay() : $to, $kind, $cur];
    }
}
