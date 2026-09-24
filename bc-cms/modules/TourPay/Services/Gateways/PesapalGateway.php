<?php

namespace Modules\TourPay\Services\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Invoice;

/** Pesapal 3.0 (Kenya, Uganda, Tanzania, Rwanda, Zambia, Malawi and more): M-Pesa, Airtel Money, MTN, cards, with the vendor's own consumer key and secret. */
class PesapalGateway extends Gateway
{
    private function base(): string
    {
        return ($this->config['mode'] ?? 'live') === 'sandbox' ? 'https://cybqa.pesapal.com/pesapalv3/api' : 'https://pay.pesapal.com/v3/api';
    }

    /** A token is good for five minutes: ask for one each time, never keep it. */
    private function token(): string
    {
        $r = Http::acceptJson()->timeout(20)->post($this->base() . '/Auth/RequestToken', ['consumer_key' => (string) ($this->config['consumer_key'] ?? ''), 'consumer_secret' => (string) ($this->config['consumer_secret'] ?? '')]);
        if (!$r->successful() || empty($r->json('token'))) {
            $this->fail('bad_keys', 'Pesapal did not accept that consumer key and secret (check live or sandbox too).');
        }

        return $r->json('token');
    }

    /** Pesapal wants to be told where to report payments (an "IPN"): register ours once, keep its id. */
    public function prepare(string $notifyUrl): array
    {
        if (!empty($this->config['ipn_id']) && ($this->config['ipn_url'] ?? '') === $notifyUrl) {
            return [];
        }
        $r = Http::withToken($this->token())->acceptJson()->timeout(20)->post($this->base() . '/URLSetup/RegisterIPN', ['url' => $notifyUrl, 'ipn_notification_type' => 'GET']);
        if (!$r->successful() || empty($r->json('ipn_id'))) {
            $this->fail('gateway_error', 'Pesapal would not register our payment notification address.');
        }
        $this->config['ipn_id'] = $r->json('ipn_id');
        $this->config['ipn_url'] = $notifyUrl;

        return ['ipn_id' => $r->json('ipn_id'), 'ipn_url' => $notifyUrl];
    }

    public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array
    {
        if (empty($this->config['ipn_id'])) {
            $this->fail('gateway_error', 'Pesapal is not fully set up yet. Press "Test saved keys" in settings.');
        }
        $name = preg_split('/\s+/', trim((string) $invoice->client_name), 2);
        $body = [
            'id' => substr($invoice->invoice_number . '-' . bin2hex(random_bytes(3)), 0, 50), 'currency' => strtoupper($invoice->currency), 'amount' => round($amount, 2),
            'description' => substr('Invoice ' . $invoice->invoice_number, 0, 100), 'callback_url' => $returnUrl, 'cancellation_url' => $cancelUrl, 'notification_id' => $this->config['ipn_id'],
            'billing_address' => array_filter(['email_address' => filter_var($invoice->client_email, FILTER_VALIDATE_EMAIL) ? $invoice->client_email : null, 'phone_number' => $invoice->client_phone ?: null, 'first_name' => $name[0] ?? null, 'last_name' => $name[1] ?? null]),
        ];
        if (empty($body['billing_address'])) {
            $body['billing_address'] = ['email_address' => 'guest@invalid.example'];
        }
        $r = Http::withToken($this->token())->acceptJson()->timeout(25)->post($this->base() . '/Transactions/SubmitOrderRequest', $body);
        if (!$r->successful() || empty($r->json('redirect_url')) || empty($r->json('order_tracking_id'))) {
            $this->fail('gateway_error', $r->json('error.message') ?: 'Pesapal could not start the payment.');
        }

        return ['url' => $r->json('redirect_url'), 'reference' => $r->json('order_tracking_id')];
    }

    public function check(string $reference): array
    {
        $r = Http::withToken($this->token())->acceptJson()->timeout(20)->get($this->base() . '/Transactions/GetTransactionStatus', ['orderTrackingId' => $reference]);
        if (!$r->successful()) {
            $this->fail('gateway_error', 'Pesapal did not answer.');
        }
        $code = (int) $r->json('status_code');

        return ['paid' => $code === 1, 'failed' => in_array($code, [2, 3], true), 'amount' => (float) $r->json('amount'), 'currency' => strtoupper((string) $r->json('currency')), 'method' => 'pesapal'];
    }

    public function referenceFrom(array $q): ?string
    {
        return isset($q['OrderTrackingId']) ? (string) $q['OrderTrackingId'] : null;
    }

    public function test(): void
    {
        $this->token();
    }
}
