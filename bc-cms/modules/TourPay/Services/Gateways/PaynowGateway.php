<?php

namespace Modules\TourPay\Services\Gateways;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Invoice;

/**
 * Paynow (Zimbabwe): EcoCash, OneMoney, InnBucks, Visa and Mastercard, with the vendor's own integration id and key.
 * Every message is signed: SHA512 of the values, in order, plus the integration key, in capitals. We sign what we send and
 * check what comes back, and we look a payment up by its poll URL, never by what the browser says.
 */
class PaynowGateway extends Gateway
{
    private const INITIATE = 'https://www.paynow.co.zw/interface/initiatetransaction';

    /** SHA512 of the values in order, then the key. */
    private function hash(array $values): string
    {
        return strtoupper(hash('sha512', implode('', $values) . (string) ($this->config['integration_key'] ?? '')));
    }

    /** Paynow answers in URL-encoded form fields. */
    private function parse(string $body): array
    {
        parse_str($body, $out);

        return array_change_key_case($out, CASE_LOWER);
    }

    /** A message from Paynow is only believed if its hash matches (the values in the order received, without the hash). */
    private function trusted(array $msg): bool
    {
        if (empty($msg['hash'])) {
            return false;
        }
        $values = [];
        foreach ($msg as $k => $v) {
            if ($k !== 'hash') {
                $values[] = $v;
            }
        }

        return hash_equals($this->hash($values), strtoupper((string) $msg['hash']));
    }

    public function start(Invoice $invoice, float $amount, string $returnUrl, string $cancelUrl): array
    {
        if (!self::supports('paynow', $invoice->currency)) {
            $this->fail('currency_unsupported', 'Paynow takes USD or ZWG. This invoice is in ' . strtoupper($invoice->currency) . '.');
        }
        $fields = [
            'id' => (string) ($this->config['integration_id'] ?? ''),
            'reference' => $invoice->invoice_number . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
            'amount' => number_format($amount, 2, '.', ''),
            'additionalinfo' => 'Invoice ' . $invoice->invoice_number,
            'returnurl' => $returnUrl,
            'resulturl' => route('tourpay.notify', 'paynow'),
            'status' => 'Message',
        ];
        if (filter_var($invoice->client_email, FILTER_VALIDATE_EMAIL)) {
            $fields['authemail'] = $invoice->client_email;
        }
        // The hash covers the values in the order they are sent.
        $fields['hash'] = $this->hash(array_values($fields));

        $r = Http::asForm()->timeout(25)->post(self::INITIATE, $fields);
        $msg = $this->parse($r->body());
        if (strtolower((string) ($msg['status'] ?? '')) !== 'ok' || empty($msg['browserurl']) || empty($msg['pollurl'])) {
            $this->fail('gateway_error', $msg['error'] ?? 'Paynow could not start the payment.');
        }
        if (!$this->trusted($msg)) {
            $this->fail('bad_signature', 'Paynow answered with a signature that does not match the integration key. Check the key.');
        }

        // The poll URL is our only handle on the payment afterwards, so it is the reference.
        return ['url' => $msg['browserurl'], 'reference' => $msg['pollurl']];
    }

    public function check(string $reference): array
    {
        $r = Http::asForm()->timeout(25)->post($reference);
        $msg = $this->parse($r->body());
        if (!$r->successful() || !$this->trusted($msg)) {
            return ['paid' => false, 'failed' => false, 'amount' => 0.0, 'currency' => '', 'method' => 'paynow'];
        }
        $status = strtolower((string) ($msg['status'] ?? ''));

        return [
            'paid' => in_array($status, ['paid', 'awaiting delivery', 'delivered'], true),
            'failed' => in_array($status, ['cancelled', 'disputed', 'refunded'], true),
            'amount' => (float) ($msg['amount'] ?? 0), 'currency' => '', 'method' => 'paynow',
        ];
    }

    /** Paynow's own message to us carries the poll URL; the guest's browser comes back with nothing (the latest open attempt is used then). */
    public function referenceFrom(array $q): ?string
    {
        return !empty($q['pollurl']) ? (string) $q['pollurl'] : null;
    }

    public function test(): void
    {
        if (empty($this->config['integration_id']) || empty($this->config['integration_key'])) {
            $this->fail('bad_keys', 'Enter the integration ID and key from your Paynow account.');
        }
        // Start (and never pay) a one-cent payment: Paynow checks the id and the signature, and tells us if either is wrong.
        $fields = ['id' => (string) $this->config['integration_id'], 'reference' => 'TP-TEST-' . substr(bin2hex(random_bytes(3)), 0, 6), 'amount' => '0.01', 'additionalinfo' => 'Connection test', 'returnurl' => url('/'), 'resulturl' => url('/'), 'status' => 'Message'];
        $fields['hash'] = $this->hash(array_values($fields));
        $msg = $this->parse(Http::asForm()->timeout(20)->post(self::INITIATE, $fields)->body());
        if (strtolower((string) ($msg['status'] ?? '')) !== 'ok') {
            $this->fail('bad_keys', 'Paynow said: ' . ($msg['error'] ?? 'it did not accept the integration ID and key.'));
        }
        if (!$this->trusted($msg)) {
            $this->fail('bad_keys', 'Paynow answered, but its signature does not match your integration key.');
        }
    }
}
