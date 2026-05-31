<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Vendor\Models\VendorWebhook;
use Modules\Vendor\Models\VendorWebhookDelivery;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        private readonly int    $webhookId,
        private readonly string $event,
        private readonly array  $payload,
    ) {}

    public function handle(): void
    {
        $webhook = VendorWebhook::find($this->webhookId);

        if (!$webhook || !$webhook->active) {
            return;
        }

        $body      = json_encode($this->payload);
        $signature = $webhook->sign($body);

        $delivery = VendorWebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event'      => $this->event,
            'payload'    => $this->payload,
            'attempts'   => $this->attempts(), // attempts() returns 1 on first try
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'            => 'application/json',
                    'X-Tsoka-Event'           => $this->event,
                    'X-Tsoka-Signature-256'   => $signature,
                    'X-Tsoka-Delivery'        => (string) $delivery->id,
                ])
                ->post($webhook->url, $this->payload);

            $delivery->update([
                'status_code'  => $response->status(),
                'response_body'=> substr($response->body(), 0, 1000),
                'success'      => $response->successful(),
                'delivered_at' => now(),
            ]);

            $webhook->updateQuietly(['last_triggered_at' => now()]);

            if (!$response->successful()) {
                Log::warning('webhook_delivery_failed', [
                    'webhook_id'  => $webhook->id,
                    'event'       => $this->event,
                    'status_code' => $response->status(),
                    'attempt'     => $this->attempts(),
                ]);
                $this->fail("Endpoint returned {$response->status()}");
            }
        } catch (\Throwable $e) {
            $delivery->update([
                'response_body' => $e->getMessage(),
                'success'       => false,
            ]);

            Log::error('webhook_delivery_error', [
                'webhook_id' => $webhook->id,
                'event'      => $this->event,
                'error'      => $e->getMessage(),
            ]);

            throw $e; // triggers queue retry
        }
    }
}
