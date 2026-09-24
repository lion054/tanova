<?php

namespace Modules\TourPay\Services\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Invoice;

/** Paystack with the vendor's own secret key. */
class PaystackGateway extends Gateway
{
    private function http()
    {
        return Http::withToken((string) ($this->config['secret_key'] ?? ''))->timeout(20)->acceptJson();
    }

    public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array
    {
        $email = filter_var($invoice->client_email, FILTER_VALIDATE_EMAIL) ? $invoice->client_email : 'guest+' . $invoice->invoice_number . '@invalid.example';
        $r = $this->http()->post('https://api.paystack.co/transaction/initialize', [
            'email' => $email, 'amount' => self::minorUnits($amount, $invoice->currency), 'currency' => strtoupper($invoice->currency),
            'callback_url' => $returnUrl, 'metadata' => ['invoice' => $invoice->invoice_number, 'cancel_action' => $cancelUrl],
        ]);
        if (!$r->successful() || empty($r->json('data.authorization_url'))) {
            $this->fail('gateway_error', $r->json('message') ?: 'Paystack could not start the payment.');
        }

        return ['url' => $r->json('data.authorization_url'), 'reference' => $r->json('data.reference')];
    }

    public function check(string $reference): array
    {
        $r = $this->http()->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));
        if (!$r->successful()) {
            return ['paid' => false, 'failed' => $r->status() === 404, 'amount' => 0.0, 'currency' => '', 'method' => 'paystack'];
        }
        $cur = strtoupper((string) $r->json('data.currency'));

        return [
            'paid' => $r->json('data.status') === 'success', 'failed' => in_array($r->json('data.status'), ['failed', 'abandoned'], true),
            'amount' => self::fromMinor((int) $r->json('data.amount'), $cur), 'currency' => $cur, 'method' => 'paystack',
        ];
    }

    public function referenceFrom(array $q): ?string
    {
        $ref = $q['reference'] ?? ($q['trxref'] ?? null);

        return $ref !== null ? (string) $ref : null;
    }

    public function test(): void
    {
        $r = $this->http()->get('https://api.paystack.co/balance');
        if (!$r->successful()) {
            $this->fail('bad_keys', $r->status() === 401 ? 'Paystack did not accept that secret key.' : 'Paystack did not answer.');
        }
    }
}
