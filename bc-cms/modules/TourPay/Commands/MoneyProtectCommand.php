<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\LedgerProtection;

class MoneyProtectCommand extends Command
{
    protected $signature = 'money:protect';
    protected $description = 'Install the database triggers that stop anyone changing or deleting a money ledger row (safe to run again)';

    public function handle(): int
    {
        $r = LedgerProtection::install();
        $r['ok'] ? $this->info($r['message']) : $this->error("Could not install them: {$r['message']}\nWhile MySQL binary logging is on, the server needs log_bin_trust_function_creators=1 (see docs/TOURPAY_INVOICING_PLAN.md).");

        return $r['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
