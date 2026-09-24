<?php

namespace Modules\Api\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\Reports;

/** How your invoices are numbered and what they start with, reminders, and your rates. Gateway keys are never read or written here: those are portal-only. */
class VendorInvoiceSettingsController extends VendorApiController
{
    public function show(): JsonResponse
    {
        return $this->success($this->shape(Setting::forVendor($this->vendorId())));
    }

    public function update(Request $request): JsonResponse
    {
        $d = $request->validate([
            'invoice_prefix' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]*$/'], 'quote_prefix' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]*$/'],
            'default_currency' => ['nullable', 'string', 'size:3'], 'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'], 'default_tax_mode' => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'default_due_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'default_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'default_terms' => ['nullable', 'string', 'max:3000'], 'default_notes' => ['nullable', 'string', 'max:3000'],
            'banking_details' => ['nullable', 'array'], 'banking_details.*' => ['nullable', 'string', 'max:200'], 'template' => ['nullable', 'integer', 'between:1,5'],
            'base_currency' => ['nullable', 'string', 'size:3'], 'rates' => ['nullable', 'array'], 'rates.*' => ['numeric', 'gt:0'],
            'send_receipts' => ['sometimes', 'boolean'], 'bank_enabled' => ['sometimes', 'boolean'],
            'remind_enabled' => ['sometimes', 'boolean'], 'remind_before_days' => ['nullable', 'integer', 'min:0', 'max:60'], 'remind_overdue_every' => ['nullable', 'integer', 'min:1', 'max:60'],
            'remind_max' => ['nullable', 'integer', 'min:1', 'max:20'], 'remind_channel' => ['nullable', Rule::in(['email', 'whatsapp'])],
        ]);
        foreach (['default_currency', 'base_currency'] as $k) {
            if (!empty($d[$k])) { $d[$k] = strtoupper($d[$k]); }
        }
        if (isset($d['rates'])) {
            $d['rates'] = collect($d['rates'])->mapWithKeys(fn ($v, $k) => [strtoupper((string) $k) => (float) $v])->all() ?: null;
        }
        if (isset($d['banking_details'])) {
            $d['banking_details'] = array_filter($d['banking_details'], fn ($v) => $v !== null && $v !== '') ?: null;
        }
        $s = Setting::forVendor($this->vendorId());
        $s->update($d);

        return $this->success($this->shape($s->fresh()));
    }

    private function shape(Setting $s): array
    {
        return [
            'invoice_prefix' => $s->prefixFor('invoice'), 'quote_prefix' => $s->prefixFor('quotation'), 'default_currency' => $s->default_currency, 'default_tax_rate' => $s->default_tax_rate !== null ? (float) $s->default_tax_rate : null,
            'default_tax_mode' => $s->default_tax_mode ?: 'inclusive', 'default_due_days' => $s->default_due_days, 'default_valid_days' => $s->default_valid_days,
            'default_terms' => $s->default_terms, 'default_notes' => $s->default_notes, 'banking_details' => $s->banking_details ?: (object) [], 'template' => $s->template ?: 1,
            'base_currency' => $s->base_currency, 'rates' => $s->rates ?: (object) [],
            'send_receipts' => (bool) $s->send_receipts, 'bank_enabled' => (bool) $s->bank_enabled,
            'online_payment_methods' => array_keys($s->enabledGateways()),
            'reminders' => ['enabled' => (bool) $s->remind_enabled, 'before_days' => (int) $s->remind_before_days, 'overdue_every_days' => (int) $s->remind_overdue_every, 'max' => (int) $s->remind_max, 'channel' => $s->remind_channel ?: 'email'],
        ];
    }
}
