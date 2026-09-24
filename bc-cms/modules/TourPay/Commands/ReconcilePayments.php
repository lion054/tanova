<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\PayOnline;

/** Finds online payments that finished after the guest closed the tab, by asking the gateway about every attempt still open. */
class ReconcilePayments extends Command
{
    protected $signature = 'tourpay:reconcile';
    protected $description = 'Check open online payment attempts with their gateways and record the ones that were paid';

    public function handle(PayOnline $online): int
    {
        $n = $online->reconcile();
        $this->info("Checked {$n} open payment attempt(s).");

        return self::SUCCESS;
    }
}
