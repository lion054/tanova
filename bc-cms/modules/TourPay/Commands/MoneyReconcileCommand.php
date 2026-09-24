<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\MoneyReconcile;

class MoneyReconcileCommand extends Command
{
    protected $signature = 'money:reconcile {--fix : write missing ledger rows, reverse rows whose source is gone, refresh stored totals}';
    protected $description = 'Check that bookings, invoices, bills and payouts agree with the money ledger';

    public function handle(MoneyReconcile $reconcile): int
    {
        $r = $reconcile->run((bool) $this->option('fix'));
        if ($r['fixed']) {
            $this->info("Repaired {$r['fixed']} item(s).");
        }
        if ($r['ok']) {
            $this->info('Everything agrees with the ledger.');

            return self::SUCCESS;
        }
        foreach ($r['problems'] as $type => $n) {
            $this->error("{$type}: {$n}");
            foreach ($r['samples'][$type] ?? [] as $s) {
                $this->line('    ' . $s);
            }
        }

        return self::FAILURE;
    }
}
