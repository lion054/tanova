<?php

namespace Modules\TourPay\Services;

use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Setting;
use Modules\Vendor\Services\VendorChannelDispatcher;

/**
 * Payment reminders, for vendors who turned them on (off unless they do).
 *
 * A reminder goes out once shortly before the due date, then again every few days while it is overdue, up to a
 * limit, and never twice in a day. It goes on the vendor's own channel (their e-mail, their WhatsApp), and only
 * for an invoice that has been sent and still has a balance.
 */
class Reminders
{
    public function __construct(private VendorChannelDispatcher $channels) {}

    /** What is due to be reminded today, for one vendor's settings. */
    public function due(Setting $s): \Illuminate\Support\Collection
    {
        $today = now()->startOfDay();

        return Invoice::withoutVendorScope()->where('vendor_id', $s->vendor_id)->where('type', 'invoice')->whereIn('status', ['sent', 'part_paid'])->whereNotNull('due_date')
            ->where('reminders_sent', '<', (int) $s->remind_max)->get()
            ->filter(function (Invoice $i) use ($s, $today) {
                if ($i->balance() <= 0 || ($i->last_reminded_at && $i->last_reminded_at->isSameDay(now()))) {
                    return false;
                }
                $due = $i->due_date->copy()->startOfDay();
                if ($due->gte($today)) {
                    // Not late yet: once, when the reminder window opens.
                    return (int) $i->reminders_sent === 0 && $today->gte($due->copy()->subDays((int) $s->remind_before_days));
                }
                $every = max(1, (int) $s->remind_overdue_every);

                return !$i->last_reminded_at || $i->last_reminded_at->copy()->startOfDay()->lte($today->copy()->subDays($every));
            })->values();
    }

    /** @return array{sent:int,skipped:int,failed:int} */
    public function run(bool $dry = false): array
    {
        $out = ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        foreach (Setting::withoutVendorScope()->where('remind_enabled', true)->get() as $s) {
            foreach ($this->due($s) as $inv) {
                if ($dry) {
                    $out['sent']++;
                    continue;
                }
                $r = $this->send($s, $inv);
                $out[$r === 'sent' ? 'sent' : ($r === 'failed' ? 'failed' : 'skipped')]++;
            }
        }

        return $out;
    }

    public function send(Setting $s, Invoice $inv): string
    {
        $company = optional(\App\User::find($inv->vendor_id))->business_name ?: optional(\App\User::find($inv->vendor_id))->name ?: 'us';
        $late = $inv->isOverdue();
        $amount = $inv->currency . ' ' . number_format($inv->balance(), 2);
        $subject = $late ? __('Overdue: invoice :n', ['n' => $inv->invoice_number]) : __('Reminder: invoice :n is due soon', ['n' => $inv->invoice_number]);
        $body = __('Hi :name,', ['name' => $inv->client_name]) . "\n\n"
            . ($late
                ? __('A friendly reminder that invoice :n from :c was due on :d and :a is still unpaid.', ['n' => $inv->invoice_number, 'c' => $company, 'd' => $inv->due_date->format('d M Y'), 'a' => $amount])
                : __('A friendly reminder that invoice :n from :c for :a is due on :d.', ['n' => $inv->invoice_number, 'c' => $company, 'a' => $amount, 'd' => $inv->due_date->format('d M Y')]))
            . "\n\n" . __('You can view and pay it here:') . "\n" . route('tourpay.pay', $inv->pay_token) . "\n\n" . __('If you have already paid, thank you, and please ignore this message.') . "\n" . $company;

        $result = $this->channels->send((int) $inv->vendor_id, $s->remind_channel ?: 'email', ['email' => $inv->client_email, 'phone' => $inv->client_phone], $subject, $body);
        if (($result['status'] ?? '') === 'sent') {
            $inv->forceFill(['reminders_sent' => (int) $inv->reminders_sent + 1, 'last_reminded_at' => now()])->saveQuietly();
        }

        return (string) ($result['status'] ?? 'failed');
    }
}
