<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Vendor\Models\VendorWebhook;
use Modules\Vendor\Services\WebhookDeliverer;

/**
 * Sends one webhook event. It records the outcome and schedules any retry itself (see WebhookDeliverer), so it
 * never throws: whatever the customer's endpoint does, the request that caused the event is unaffected.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private readonly int    $webhookId,
        private readonly string $event,
        private readonly array  $payload,
    ) {}

    public function handle(WebhookDeliverer $deliverer): void
    {
        $webhook = VendorWebhook::find($this->webhookId);
        if (!$webhook || !$webhook->active) {
            return;
        }
        $deliverer->deliverNew($webhook, $this->event, $this->payload);
    }
}
