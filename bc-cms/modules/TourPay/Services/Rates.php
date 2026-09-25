<?php

namespace Modules\TourPay\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Setting;

/**
 * Exchange rates for the few places money in one currency must be expressed in another (a booking paid through an invoice in a
 * different currency; the business's reporting currency; payouts). Invoices, payments and the ledger always keep their own currency.
 *
 * Order of trust: the business's own rates in TourPay settings (they set them), then the daily rates of the free
 * open.er-api.com feed (kept per day in bc_fx_rates, so an old conversion can be explained), else nothing: a conversion is never guessed.
 * Rates By Exchange Rate API, https://www.exchangerate-api.com
 */
class Rates
{
    public const URL = 'https://open.er-api.com/v6/latest/USD';
    private const FRESH_HOURS = 26;

    /** Pulls today's rates and stores them. Returns false when the feed cannot be reached, leaving the last known rates in use. */
    public function refresh(): bool
    {
        try {
            $r = Http::timeout(10)->acceptJson()->get(self::URL);
            $j = $r->json();
            $rates = is_array($j['rates'] ?? null) ? $j['rates'] : [];
            if (!$r->ok() || ($j['result'] ?? '') !== 'success' || count($rates) < 50 || empty($rates['USD'])) {
                return false;
            }
            $rates = array_map('floatval', $rates);
            DB::table('bc_fx_rates')->updateOrInsert(['day' => now()->toDateString()], ['base' => 'USD', 'rates' => json_encode($rates), 'source' => 'open.er-api.com', 'fetched_at' => now()]);
            Cache::forget('fx.latest');

            return true;
        } catch (\Throwable $e) {
            \Log::warning('Exchange rates: could not refresh (' . $e->getMessage() . ')');

            return false;
        }
    }

    /**
     * What [$amount] of [$from] is worth in [$to], at the rate for [$at] (else the latest).
     * @return array{amount:float,rate:float,source:string}|null null when no rate is known
     */
    public function convert(float $amount, string $from, string $to, ?Carbon $at = null, ?int $vendorId = null): ?array
    {
        $from = strtoupper($from); $to = strtoupper($to);
        if ($from === $to) {
            return ['amount' => round($amount, 2), 'rate' => 1.0, 'source' => 'same'];
        }
        $rate = null; $source = null;
        if ($vendorId && ($r = $this->vendorRate($vendorId, $from, $to)) !== null) {
            [$rate, $source] = [$r, 'vendor'];
        } elseif (($r = $this->apiRate($from, $to, $at)) !== null) {
            [$rate, $source] = [$r, 'api'];
        }

        return $rate === null ? null : ['amount' => round($amount * $rate, 2), 'rate' => round($rate, 8), 'source' => $source];
    }

    /** Units of [$to] per 1 [$from], from the business's own rates (each is the value of 1 unit in their base currency). */
    private function vendorRate(int $vendorId, string $from, string $to): ?float
    {
        try {
            $s = Setting::withoutVendorScope()->where('vendor_id', $vendorId)->first();
        } catch (\Throwable $e) {
            return null;
        }
        $base = $s ? strtoupper((string) $s->base_currency) : '';
        if ($base === '') {
            return null;
        }
        $rates = array_change_key_case((array) ($s->rates ?? []), CASE_UPPER);
        $inBase = fn (string $c) => $c === $base ? 1.0 : (float) ($rates[$c] ?? 0);
        [$f, $t] = [$inBase($from), $inBase($to)];

        return $f > 0 && $t > 0 ? $f / $t : null;
    }

    private function apiRate(string $from, string $to, ?Carbon $at): ?float
    {
        $rates = $this->ratesFor($at);
        if (!$rates || empty($rates[$from]) || empty($rates[$to])) {
            return null;
        }

        return (float) $rates[$to] / (float) $rates[$from];
    }

    /** The stored rates for the day (or the nearest earlier one). Fetches once when there are none, or none from the last day. */
    private function ratesFor(?Carbon $at): ?array
    {
        $day = ($at ?? now())->toDateString();
        $row = DB::table('bc_fx_rates')->where('day', '<=', $day)->orderByDesc('day')->first() ?: DB::table('bc_fx_rates')->orderBy('day')->first();
        $stale = !$row || Carbon::parse($row->fetched_at)->lt(now()->subHours(self::FRESH_HOURS));
        // At most one attempt an hour, so an outage of the feed does not slow every payment down.
        if ($stale && $day >= now()->subDay()->toDateString() && Cache::add('fx.attempt', 1, 3600) && $this->refresh()) {
            $row = DB::table('bc_fx_rates')->where('day', '<=', $day)->orderByDesc('day')->first() ?: $row;
        }

        return $row ? json_decode($row->rates, true) : null;
    }
}
