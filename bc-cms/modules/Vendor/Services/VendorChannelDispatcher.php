<?php

namespace Modules\Vendor\Services;

use App\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Phase 3 — sends a message on a vendor's own channel using the credentials the
 * vendor configured in the Integrations module (stored, encrypted, on their user
 * record). Each call is scoped to one vendor; no shared sender is ever used.
 *
 * Returns ['status' => sent|failed|skipped, 'error' => ?string, 'to' => ?string].
 * "skipped" means the vendor hasn't connected that channel, or we have no usable
 * recipient id for it (e.g. no Telegram chat id / Messenger PSID for the customer).
 */
class VendorChannelDispatcher
{
    public function send(int $vendorId, string $channel, array $recipient, string $subject, string $body): array
    {
        if (\App\Services\VendorContext::active() && \App\Services\VendorContext::isTest()) {
            return ['status' => 'skipped', 'error' => 'test_mode', 'to' => $recipient['email'] ?? ($recipient['phone'] ?? null)];
        }

        $vendor = User::find($vendorId);
        if (! $vendor) {
            return ['status' => 'failed', 'error' => 'vendor_not_found', 'to' => null];
        }

        try {
            return match ($channel) {
                'email'    => $this->email($recipient['email'] ?? null, $subject, $body),
                'whatsapp' => $this->whatsapp($vendor, $recipient['phone'] ?? null, $body),
                'telegram' => $this->telegram($vendor, $recipient['telegram_chat_id'] ?? null, $body),
                default    => $this->unsupported($channel, $recipient),
            };
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'error' => $e->getMessage(), 'to' => $recipient['email'] ?? null];
        }
    }

    private function email(?string $to, string $subject, string $body): array
    {
        if (! $to) {
            return ['status' => 'skipped', 'error' => 'no_email', 'to' => null];
        }
        Mail::raw($body, fn ($m) => $m->to($to)->subject($subject ?: 'Message'));

        return ['status' => 'sent', 'error' => null, 'to' => $to];
    }

    private function whatsapp(User $vendor, ?string $phone, string $body): array
    {
        if (empty($vendor->whatsapp_enabled) || empty($vendor->whatsapp_access_token) || empty($vendor->whatsapp_phone_number_id)) {
            return ['status' => 'skipped', 'error' => 'whatsapp_not_connected', 'to' => $phone];
        }
        if (! $phone) {
            return ['status' => 'skipped', 'error' => 'no_phone', 'to' => null];
        }

        $token = Crypt::decryptString($vendor->whatsapp_access_token);
        $resp = Http::withToken($token)->post(
            "https://graph.facebook.com/v18.0/{$vendor->whatsapp_phone_number_id}/messages",
            [
                'messaging_product' => 'whatsapp',
                'to'                => preg_replace('/[^0-9]/', '', $phone),
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]
        );

        return $resp->successful()
            ? ['status' => 'sent', 'error' => null, 'to' => $phone]
            : ['status' => 'failed', 'error' => 'whatsapp_http_' . $resp->status(), 'to' => $phone];
    }

    private function telegram(User $vendor, ?string $chatId, string $body): array
    {
        if (empty($vendor->telegram_enabled) || empty($vendor->telegram_bot_token)) {
            return ['status' => 'skipped', 'error' => 'telegram_not_connected', 'to' => $chatId];
        }
        if (! $chatId) {
            // We don't capture per-customer Telegram chat ids yet.
            return ['status' => 'skipped', 'error' => 'no_telegram_chat_id', 'to' => null];
        }

        $token = Crypt::decryptString($vendor->telegram_bot_token);
        $resp = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => $body,
        ]);

        return $resp->successful()
            ? ['status' => 'sent', 'error' => null, 'to' => $chatId]
            : ['status' => 'failed', 'error' => 'telegram_http_' . $resp->status(), 'to' => $chatId];
    }

    private function unsupported(string $channel, array $recipient): array
    {
        // e.g. sms / facebook — no per-customer addressable id captured yet.
        Log::info('vendor_channel_unsupported', ['channel' => $channel]);

        return ['status' => 'skipped', 'error' => 'channel_unsupported:' . $channel, 'to' => $recipient['phone'] ?? null];
    }
}
