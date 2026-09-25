<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\Rates;

class FxRefreshCommand extends Command
{
    protected $signature = 'fx:refresh';
    protected $description = "Fetch today's exchange rates (free open.er-api.com feed)";

    public function handle(Rates $rates): int
    {
        if ($rates->refresh()) {
            $this->info('Rates updated.');

            return self::SUCCESS;
        }
        $this->error('Could not reach the rates feed; the last known rates stay in use.');

        return self::FAILURE;
    }
}
