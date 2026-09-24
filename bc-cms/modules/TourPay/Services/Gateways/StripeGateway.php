<?php

namespace Modules\TourPay\Services\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Invoice;

/** Stripe Checkout with the vendor's own secret key. */
class StripeGateway extends Gateway
{
    private function http()
    {
        return Http::withToken((string) ($this->config['secret_key'] ?? ''))->timeout(20)->asForm();
    }

    public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array
    {
        $sep = str_contains($returnUrl, '?') ? '&' : '?';
        $r = $this->http()->post('https://api.stripe.com/v1/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $returnUrl . $sep . 'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'customer_email' => $invoice->client_email ?: null,
            'client_reference_id' => $invoice->invoice_number,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => ['currency' => strtolower($invoice->currency), 'unit_amount' => self::minorUnits($amount, $invoice->currency), 'product_data' => ['name' => 'Invoice ' . $invoice->invoice_number . ($invoice->title ? ' · ' . $invoice->title : '')]],
            ]],
            'metadata' => ['invoice' => $invoice->invoice_number],
        ]);
        if (!$r->successful() || empty($r->json('url'))) {
            $this->fail('gateway_error', $r->json('error.message') ?: 'Stripe could not start the payment.');
        }

        return ['url' => $r->json('url'), 'reference' => $r->json('id')];
    }

    public function check(string $reference): array
    {
        $r = $this->http()->get('https://api.stripe.com/v1/checkout/sessions/' . urlencode($reference));
        if (!$r->successful()) {
            $this->fail('gateway_error', $r->json('error.message') ?: 'Stripe did not answer.');
        }
        $cur = strtoupper((string) $r->json('currency'));

        return [
            'paid' => $r->json('payment_status') === 'paid', 'failed' => $r->json('status') === 'expired',
            'amount' => self::fromMinor((int) $r->json('amount_total'), $cur), 'currency' => $cur, 'method' => 'stripe',
        ];
    }

    public function referenceFrom(array $q): ?string
    {
        return isset($q['session_id']) ? (string) $q['session_id'] : null;
    }

    public function test(): void
    {
        $r = Http::withToken((string) ($this->config['secret_key'] ?? ''))->timeout(15)->get('https://api.stripe.com/v1/balance');
        if (!$r->successful()) {
            $this->fail('bad_keys', $r->status() === 401 ? 'Stripe did not accept that secret key.' : ($r->json('error.message') ?: 'Stripe did not answer.'));
        }
    }
}
