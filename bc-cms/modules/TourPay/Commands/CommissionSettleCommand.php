<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\Commission;

class CommissionSettleCommand extends Command
{
    protected $signature = 'commission:settle {vendor_id} {currency} {amount} {--note=}';
    protected $description = 'Record commission a business paid (or was excused) outside the platform, in one currency';

    public function handle(Commission $commission): int
    {
        $vendor = (int) $this->argument('vendor_id');
        $cur = strtoupper((string) $this->argument('currency'));
        $owed = $commission->owed($vendor)[$cur] ?? 0.0;
        $amount = (float) $this->argument('amount');
        if ($amount <= 0 || $amount > $owed + 0.005) {
            $this->error("Owed in {$cur}: " . number_format($owed, 2) . '. The amount must be more than zero and no more than that.');

            return self::FAILURE;
        }
        $commission->settleManually($vendor, $cur, $amount, $this->option('note') ?: null);
        $this->info("Settled {$amount} {$cur}. Still owed: " . number_format(max(0, $owed - $amount), 2));

        return self::SUCCESS;
    }
}
