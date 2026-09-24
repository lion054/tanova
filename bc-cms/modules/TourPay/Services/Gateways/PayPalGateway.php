<?php

namespace Modules\TourPay\Services\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Invoice;

/** PayPal Orders v2 with the vendor's own REST app (client id and secret). */
class PayPalGateway extends Gateway
{
    private function base(): string
    {
        return ($this->config['mode'] ?? 'live') === 'sandbox' ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function token(): string
    {
        $r = Http::withBasicAuth((string) ($this->config['client_id'] ?? ''), (string) ($this->config['client_secret'] ?? ''))->asForm()->timeout(20)
            ->post($this->base() . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        if (!$r->successful() || empty($r->json('access_token'))) {
            $this->fail('bad_keys', 'PayPal did not accept that client id and secret (check the live/sandbox mode too).');
        }

        return $r->json('access_token');
    }

    public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array
    {
        $r = Http::withToken($this->token())->timeout(20)->post($this->base() . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $invoice->invoice_number, 'description' => 'Invoice ' . $invoice->invoice_number,
                'amount' => ['currency_code' => strtoupper($invoice->currency), 'value' => number_format($amount, 2, '.', '')],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => ['return_url' => $returnUrl, 'cancel_url' => $cancelUrl, 'user_action' => 'PAY_NOW']]],
        ]);
        $link = collect($r->json('links') ?? [])->first(fn ($l) => in_array($l['rel'] ?? '', ['payer-action', 'approve'], true));
        if (!$r->successful() || !$link) {
            $this->fail('gateway_error', $r->json('message') ?: 'PayPal could not start the payment.');
        }

        return ['url' => $link['href'], 'reference' => $r->json('id')];
    }

    public function check(string $reference): array
    {
        $tok = $this->token();
        $r = Http::withToken($tok)->timeout(20)->get($this->base() . '/v2/checkout/orders/' . urlencode($reference));
        if (!$r->successful()) {
            $this->fail('gateway_error', 'PayPal did not answer.');
        }
        // The guest approved it: take the money now (a second call after that is harmless, PayPal answers with the capture).
        if ($r->json('status') === 'APPROVED') {
            $c = Http::withToken($tok)->timeout(20)->withBody('{}', 'application/json')->post($this->base() . '/v2/checkout/orders/' . urlencode($reference) . '/capture');
            if ($c->successful()) {
                $r = $c;
            }
        }
        $cap = $r->json('purchase_units.0.payments.captures.0');

        return [
            'paid' => $r->json('status') === 'COMPLETED' && ($cap['status'] ?? '') === 'COMPLETED', 'failed' => in_array($r->json('status'), ['VOIDED'], true),
            'amount' => (float) ($cap['amount']['value'] ?? 0), 'currency' => strtoupper((string) ($cap['amount']['currency_code'] ?? '')), 'method' => 'paypal',
        ];
    }

    public function referenceFrom(array $q): ?string
    {
        return isset($q['token']) ? (string) $q['token'] : null;
    }

    public function test(): void
    {
        $this->token();
    }
}
