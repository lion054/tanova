<?php

namespace Modules\Vendor\Commands;

use Illuminate\Console\Command;
use Modules\Vendor\Services\WebhookDeliverer;

/** Sends webhook deliveries that failed and are due for another try (1 min, 5 min, 30 min, 2 h, 12 h apart). */
class RetryWebhooks extends Command
{
    protected $signature = 'webhooks:retry';
    protected $description = 'Retry failed webhook deliveries that are due';

    public function handle(WebhookDeliverer $d): int
    {
        $n = $d->retryDue();
        $this->info("Retried {$n} delivery(ies).");

        return self::SUCCESS;
    }
}
