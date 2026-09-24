<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Vendor\Models\VendorWebhook;
use Modules\Vendor\Models\VendorWebhookDelivery;

/**
 * Sends one webhook delivery and records what happened. It never throws: a dead endpoint is a fact to record and
 * retry, not an error for the request that caused the event.
 *
 * Retries: after a failed attempt the next is scheduled 1 minute, 5 minutes, 30 minutes, 2 hours and 12 hours
 * later; after the sixth attempt it stays failed. `webhooks:retry` (every minute) picks up what is due.
 */
class WebhookDeliverer
{
    public const BACKOFF_MINUTES = [1, 5, 30, 120, 720];
    public const MAX_ATTEMPTS = 6;

    /** Replaces DNS lookup (host => list of IPs), for tests. */
    public static ?\Closure $resolver = null;

    /** Send [$delivery] now. */
    public function send(VendorWebhook $hook, VendorWebhookDelivery $delivery): VendorWebhookDelivery
    {
        $body = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $attempts = (int) $delivery->attempts + 1;
        $started = microtime(true);
        $update = ['attempts' => $attempts, 'next_attempt_at' => null];

        try {
            if (!self::urlIsSafe($hook->url)) {
                throw new \RuntimeException('The address is not allowed (it must be a public https address).');
            }
            $r = Http::timeout(10)->connectTimeout(5)->withoutRedirecting()->withHeaders([
                'Content-Type'          => 'application/json',
                'User-Agent'            => 'Tsoka-Webhooks/1.0',
                'X-Tsoka-Event'         => (string) $delivery->event,
                'X-Tsoka-Signature-256' => $hook->sign($body),
                'X-Tsoka-Delivery'      => (string) $delivery->id,
                'X-Tsoka-Event-Id'      => (string) ($delivery->event_id ?: ($delivery->payload['id'] ?? '')),
            ])->withBody($body, 'application/json')->post($hook->url);

            $update += ['status_code' => $r->status(), 'response_body' => substr($r->body(), 0, 1000), 'success' => $r->successful(), 'delivered_at' => now()];
        } catch (\Throwable $e) {
            $update += ['status_code' => null, 'response_body' => substr($e->getMessage(), 0, 1000), 'success' => false, 'delivered_at' => null];
            Log::warning('webhook_delivery_error', ['webhook_id' => $hook->id, 'event' => $delivery->event, 'error' => $e->getMessage()]);
        }
        $update['duration_ms'] = (int) round((microtime(true) - $started) * 1000);
        if (!$update['success'] && $attempts < self::MAX_ATTEMPTS) {
            $update['next_attempt_at'] = now()->addMinutes(self::BACKOFF_MINUTES[$attempts - 1] ?? 720);
        }
        $delivery->update($update);
        if ($update['success']) {
            $hook->updateQuietly(['last_triggered_at' => now()]);
        }

        return $delivery->fresh();
    }

    /** Record a new event for a webhook and send it. */
    public function deliverNew(VendorWebhook $hook, string $event, array $payload): VendorWebhookDelivery
    {
        $d = VendorWebhookDelivery::create(['webhook_id' => $hook->id, 'event' => $event, 'event_id' => $payload['id'] ?? null, 'payload' => $payload, 'attempts' => 0, 'success' => false]);

        return $this->send($hook, $d);
    }

    /** Send what is due for retry. @return int deliveries attempted */
    public function retryDue(int $limit = 200): int
    {
        $due = VendorWebhookDelivery::where('success', false)->whereNotNull('next_attempt_at')->where('next_attempt_at', '<=', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)->orderBy('next_attempt_at')->limit($limit)->get();
        $n = 0;
        foreach ($due as $d) {
            $hook = VendorWebhook::find($d->webhook_id);
            if (!$hook || !$hook->active) {
                $d->update(['next_attempt_at' => null]);
                continue;
            }
            $this->send($hook, $d);
            $n++;
        }

        return $n;
    }

    /**
     * A public https address only. Refuses http, plain IPs in private / loopback / link-local ranges (including the
     * cloud metadata address), and names that resolve to one, so a webhook can never be pointed at our own network.
     */
    public static function urlIsSafe(string $url): bool
    {
        $p = parse_url($url);
        if (!$p || strtolower($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['pass'])) {
            return false;
        }
        $host = trim($p['host'], '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (self::$resolver
            ? (self::$resolver)($host)
            : array_merge(array_column(@dns_get_record($host, DNS_A) ?: [], 'ip'), array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')));
        if (!$ips) {
            return false;
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }
}
