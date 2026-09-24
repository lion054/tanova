<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\MoneyBackfill;

class MoneyBackfillCommand extends Command
{
    protected $signature = 'money:backfill';
    protected $description = 'Load money recorded before the ledger existed into it (safe to run again)';

    public function handle(MoneyBackfill $backfill): int
    {
        foreach ($backfill->run() as $what => $n) {
            $this->line(str_pad($what, 22) . $n);
        }

        return self::SUCCESS;
    }
}
