<?php

namespace Modules\TourPay\Services\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Invoice;

/**
 * Selcom Checkout (Tanzania): mobile money (M-Pesa, Tigo Pesa, Airtel Money, Halopesa) and cards, through the vendor's own
 * Selcom account. Requests are signed with the vendor's API secret (HMAC-SHA256 over a timestamp and the fields sent).
 */
class SelcomGateway extends Gateway
{
    private function base(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? 'https://apigw.selcommobile.com'), '/');
    }

    /** The signed headers Selcom asks for: the key, a timestamp, and a digest over the timestamp and each field sent, in order. */
    private function headers(array $fields): array
    {
        $ts = now()->format('c');
        $signed = 'timestamp=' . $ts;
        foreach ($fields as $k => $v) {
            $signed .= '&' . $k . '=' . $v;
        }

        return [
            'Authorization' => 'SELCOM ' . base64_encode((string) ($this->config['api_key'] ?? '')), 'Timestamp' => $ts, 'Digest-Method' => 'HS256',
            'Digest' => base64_encode(hash_hmac('sha256', $signed, (string) ($this->config['api_secret'] ?? ''), true)), 'Signed-Fields' => implode(',', array_keys($fields)),
        ];
    }

    private function post(string $path, array $body)
    {
        return Http::withHeaders($this->headers($body))->acceptJson()->asJson()->timeout(25)->post($this->base() . $path, $body);
    }

    private function get(string $path, array $query)
    {
        return Http::withHeaders($this->headers($query))->acceptJson()->timeout(25)->get($this->base() . $path, $query);
    }

    /** A Tanzanian number as Selcom wants it: 255 followed by nine digits. */
    private function phone(?string $p): string
    {
        $d = preg_replace('/\D/', '', (string) $p);
        if (str_starts_with($d, '0')) {
            $d = '255' . substr($d, 1);
        }

        return strlen($d) >= 11 ? $d : '255000000000';
    }

    public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array
    {
        if (!self::supports('selcom', $invoice->currency)) {
            $this->fail('currency_unsupported', 'Selcom takes TZS. This invoice is in ' . strtoupper($invoice->currency) . '.');
        }
        $orderId = 'TP' . $invoice->id . strtoupper(bin2hex(random_bytes(4)));
        $body = [
            'vendor' => (string) ($this->config['vendor'] ?? ''), 'order_id' => $orderId,
            'buyer_email' => filter_var($invoice->client_email, FILTER_VALIDATE_EMAIL) ? $invoice->client_email : 'guest@invalid.example', 'buyer_name' => (string) ($invoice->client_name ?: 'Guest'),
            'buyer_phone' => $this->phone($invoice->client_phone), 'amount' => (int) round($amount), 'currency' => strtoupper($invoice->currency),
            'redirect_url' => base64_encode($returnUrl), 'cancel_url' => base64_encode($cancelUrl), 'webhook' => base64_encode(route('tourpay.notify', 'selcom')),
            'buyer_remarks' => 'Invoice ' . $invoice->invoice_number, 'merchant_remarks' => 'Invoice ' . $invoice->invoice_number, 'no_of_items' => 1,
        ];
        $r = $this->post('/v1/checkout/create-order-minimal', $body);
        $url = $r->json('data.0.payment_gateway_url');
        if (!$r->successful() || $r->json('resultcode') !== '000' || !$url) {
            $this->fail('gateway_error', $r->json('message') ?: 'Selcom could not start the payment.');
        }
        // Selcom hands the address back base64 encoded.
        $decoded = base64_decode((string) $url, true);

        return ['url' => $decoded !== false && str_starts_with($decoded, 'http') ? $decoded : (string) $url, 'reference' => $orderId];
    }

    public function check(string $reference): array
    {
        $r = $this->get('/v1/checkout/order-status', ['order_id' => $reference]);
        if (!$r->successful()) {
            $this->fail('gateway_error', 'Selcom did not answer.');
        }
        $row = $r->json('data.0') ?? [];
        $status = strtoupper((string) ($row['payment_status'] ?? ''));

        return [
            'paid' => $status === 'COMPLETED', 'failed' => in_array($status, ['CANCELLED', 'USERCANCELLED', 'REJECTED', 'EXPIRED'], true),
            'amount' => (float) ($row['amount'] ?? 0), 'currency' => strtoupper((string) ($row['currency'] ?? '')), 'method' => 'selcom',
        ];
    }

    public function referenceFrom(array $q): ?string
    {
        foreach (['order_id', 'orderId', 'transid'] as $k) {
            if (!empty($q[$k])) {
                return (string) $q[$k];
            }
        }

        return null;
    }

    public function test(): void
    {
        foreach (['vendor', 'api_key', 'api_secret'] as $f) {
            if (empty($this->config[$f])) {
                $this->fail('bad_keys', 'Enter the till number (vendor), API key and API secret from your Selcom account.');
            }
        }
        // Ask about an order that does not exist: a correct signature gets "not found", a wrong key gets refused.
        $r = $this->get('/v1/checkout/order-status', ['order_id' => 'TPCONNECTIONTEST']);
        if (in_array($r->status(), [401, 403], true) || in_array((string) $r->json('resultcode'), ['403', '401', '402'], true)) {
            $this->fail('bad_keys', 'Selcom did not accept that API key and secret.');
        }
    }
}
