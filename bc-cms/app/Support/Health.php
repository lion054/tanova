<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What a monitor should ask: is the database answering, is there disk left, is the scheduler still ticking.
 * Only pass/fail and coarse numbers are ever returned; nothing about the configuration leaks.
 */
class Health
{
    public const HEARTBEAT = 'health.scheduler.beat';

    public static function beat(): void
    {
        Cache::put(self::HEARTBEAT, now()->timestamp, 3600);
    }

    /** @return array{ok:bool,checks:array<string,array{ok:bool,detail:?string}>} */
    public static function run(): array
    {
        $checks = [];

        try { DB::select('select 1'); $checks['database'] = ['ok' => true, 'detail' => null]; }
        catch (\Throwable $e) { $checks['database'] = ['ok' => false, 'detail' => 'not answering']; }

        $free = @disk_free_space(base_path()); $total = @disk_total_space(base_path());
        $pct = $free && $total ? round($free / $total * 100) : null;
        $checks['disk'] = ['ok' => $pct === null || $pct >= 10, 'detail' => $pct === null ? null : "{$pct}% free"];

        // The scheduler writes a beat every minute; a stopped cron shows up here within a few minutes.
        $beat = (int) Cache::get(self::HEARTBEAT, 0);
        $age = $beat ? now()->timestamp - $beat : null;
        $checks['scheduler'] = ['ok' => $age !== null && $age < 300, 'detail' => $age === null ? 'no heartbeat yet' : "last tick {$age}s ago"];

        // The nightly money check: bookings, invoices, bills and payouts agree with the ledger. Red if it found a difference, or has not run for two days.
        $m = Cache::get(\Modules\TourPay\Services\MoneyReconcile::CACHE_KEY);
        if (!$m) {
            $checks['money'] = ['ok' => true, 'detail' => 'not run yet'];
        } elseif (!$m['ok']) {
            $checks['money'] = ['ok' => false, 'detail' => array_sum($m['problems']) . ' difference(s) from the ledger'];
        } elseif (\Carbon\Carbon::parse($m['ran_at'])->diffInHours(now()) > 48) {
            $checks['money'] = ['ok' => false, 'detail' => 'check has not run for over 48 hours'];
        } else {
            $checks['money'] = ['ok' => true, 'detail' => 'agrees with the ledger'];
        }

        return ['ok' => !in_array(false, array_column($checks, 'ok'), true), 'checks' => $checks];
    }
}
