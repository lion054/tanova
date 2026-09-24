<?php

namespace Modules\TourPay\Services;

use Modules\TourPay\Models\Attempt;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\Gateways\Gateway;
use Modules\TourPay\Services\Gateways\GatewayException;

/**
 * A guest pays an invoice online, through the vendor's own gateway account.
 *
 * Starting sends the guest to the gateway; settling asks the gateway what happened. Nothing is recorded because the
 * guest's browser says so: only because the gateway confirms it, and once (the gateway's id is the payment's key), so
 * coming back twice, or the background check finding it too, never counts it twice.
 */
class PayOnline
{
    public function __construct(private InvoiceBook $book) {}

    /** What the guest can pay right now: the whole balance, and the next instalment when there is a schedule. */
    public function amounts(Invoice $inv): array
    {
        $out = ['balance' => max(0.0, $inv->balance()), 'next' => null];
        if ($next = $this->book->nextDue($inv)) {
            $out['next'] = ['label' => $next['label'], 'amount' => min($next['remaining'], $out['balance']), 'due_date' => $next['due_date']];
        }

        return $out;
    }

    /** @return string where to send the guest */
    public function begin(Invoice $inv, string $gateway, string $choice, string $returnUrl, string $cancelUrl): string
    {
        if ($inv->type !== 'invoice' || in_array($inv->status, ['paid', 'void', 'draft'], true) || $inv->balance() <= 0) {
            throw new GatewayException('not_payable', __('This invoice is not open for payment.'));
        }
        $settings = Setting::forVendor((int) $inv->vendor_id);
        if (!isset($settings->enabledGateways($inv->currency)[$gateway])) {
            throw new GatewayException('gateway_off', __('That payment method is not available for this invoice.'));
        }
        $amounts = $this->amounts($inv);
        $amount = $choice === 'next' && $amounts['next'] ? $amounts['next']['amount'] : $amounts['balance'];
        if ($amount <= 0) {
            throw new GatewayException('not_payable', __('Nothing is due.'));
        }

        $gw = Gateway::make($gateway, $settings->gateway($gateway));
        // Some gateways need a one-off registration (Pesapal's payment notification address): keep what they hand back.
        if ($extra = $gw->prepare(route('tourpay.notify', $gateway))) {
            $saved = (array) $settings->gateways;
            $saved[$gateway] = array_merge((array) ($saved[$gateway] ?? []), $extra);
            $settings->update(['gateways' => $saved]);
            $gw = Gateway::make($gateway, $settings->fresh()->gateway($gateway));
        }
        $started = $gw->start($inv, round($amount, 2), $returnUrl, $cancelUrl);
        Attempt::create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'gateway' => $gateway, 'reference' => $started['reference'], 'amount' => round($amount, 2), 'currency' => strtoupper($inv->currency), 'status' => 'started']);

        return $started['url'];
    }

    /**
     * Ask the gateway about an attempt and record the payment if it went through.
     * @return string paid|failed|pending
     */
    public function settle(Attempt $a): string
    {
        $a->forceFill(['checked_at' => now()])->save();
        if ($a->status === 'paid') {
            return 'paid';
        }
        $inv = Invoice::withoutVendorScope()->find($a->invoice_id);
        if (!$inv) {
            return 'failed';
        }
        $result = Gateway::make($a->gateway, Setting::forVendor((int) $a->vendor_id)->gateway($a->gateway))->check($a->reference);

        if ($result['paid']) {
            // What the gateway says it took, in the invoice's own currency. Anything else is held for a human, never guessed at.
            if ($result['currency'] !== '' && strtoupper($result['currency']) !== strtoupper($inv->currency)) {
                $a->update(['status' => 'failed']);
                \Log::warning('tourpay_currency_mismatch', ['attempt' => $a->id, 'invoice' => $inv->id, 'got' => $result['currency']]);

                return 'failed';
            }
            $take = min((float) $result['amount'], max(0.0, $inv->balance()));
            if ($take > 0) {
                $note = $take + 0.001 < (float) $result['amount'] ? __('The gateway took :a but only :b was owed: refund the difference.', ['a' => number_format($result['amount'], 2), 'b' => number_format($take, 2)]) : null;
                $this->book->recordPayment($inv->fresh(), $take, $a->gateway, now()->toDateString(), $a->reference, $note, 'gateway', null, $a->reference, true);
            }
            $a->update(['status' => 'paid']);

            return 'paid';
        }
        if ($result['failed']) {
            $a->update(['status' => 'failed']);

            return 'failed';
        }

        return 'pending';
    }

    /** The background check: look again at attempts that were started but never came back. */
    public function reconcile(int $limit = 100): int
    {
        Attempt::withoutVendorScope()->where('status', 'started')->where('created_at', '<', now()->subDays(3))->update(['status' => 'expired']);
        $n = 0;
        foreach (Attempt::withoutVendorScope()->where('status', 'started')->where('created_at', '<', now()->subMinutes(2))->where(fn ($q) => $q->whereNull('checked_at')->orWhere('checked_at', '<', now()->subMinutes(9)))->orderBy('id')->limit($limit)->get() as $a) {
            try {
                $this->settle($a);
                $n++;
            } catch (\Throwable $e) {
                \Log::warning('tourpay_reconcile_failed', ['attempt' => $a->id, 'error' => $e->getMessage()]);
            }
        }

        return $n;
    }
}
