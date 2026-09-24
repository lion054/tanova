<?php

namespace Modules\Vendor\Services;

use App\Jobs\DeliverWebhook;
use Illuminate\Support\Str;
use Modules\Vendor\Models\VendorWebhook;

/**
 * The webhook events, and sending them.
 *
 * Every event has the same envelope: { id, type, created, api_version, data: { object } }. The id is stable
 * (a retry or a redelivery carries the same one), so a receiver can ignore what it has already handled.
 * The older booking events keep their original top-level `event` and `booking` keys as well.
 */
class WebhookEvents
{
    /** type => [what it means, the kind of object in data.object] */
    public const CATALOGUE = [
        'booking.created'              => ['A booking was made and is being processed.', 'BookingWebhook'],
        'booking.paid'                 => ['A booking was paid in full.', 'BookingWebhook'],
        'booking.confirmed'            => ['A booking was confirmed.', 'BookingWebhook'],
        'booking.cancelled'            => ['A booking was cancelled.', 'BookingWebhook'],
        'booking.completed'            => ['A trip was completed (this is when loyalty points are earned).', 'BookingWebhook'],
        'booking.payment_received'     => ['Money was recorded against a booking, by you or by the guest through PayPal.', 'BookingPaymentWebhook'],
        'booking.refund_recorded'      => ['A refund was recorded against a booking.', 'BookingPaymentWebhook'],
        'booking.travellers_submitted' => ['A guest filled in who is travelling, through the traveller-details form.', 'BookingTravellersWebhook'],
        'waitlist.joined'              => ['Someone joined the waitlist, from your app or added by you.', 'WaitlistWebhook'],
        'waitlist.notified'            => ['A waiting guest was told a seat opened.', 'WaitlistWebhook'],
        'waitlist.converted'           => ['A waiting guest booked the tour they were waiting for.', 'WaitlistWebhook'],
        'customer.created'             => ['A customer record was created.', 'CustomerWebhook'],
        'loyalty.points_earned'        => ['A guest earned points for a completed trip.', 'LoyaltyWebhook'],
        'invoice.created'              => ['An invoice was created.', 'InvoiceWebhook'],
        'invoice.paid'                 => ['An invoice was paid in full.', 'InvoiceWebhook'],
        'invoice.voided'               => ['An invoice was voided.', 'InvoiceWebhook'],
    ];

    /** Every event type, and "*" for all of them. */
    public static function types(): array
    {
        return array_merge(array_keys(self::CATALOGUE), ['*']);
    }

    /** The body sent for an event. [$legacy] is merged in unchanged, for events that predate the envelope. */
    public static function envelope(string $type, array $object, array $legacy = [], ?string $id = null): array
    {
        return $legacy + [
            'id'          => $id ?: 'evt_' . Str::lower(Str::random(24)),
            'type'        => $type,
            'created'     => time(),
            'api_version' => \App\Http\Middleware\ApiVersion::CURRENT,
            'data'        => ['object' => $object],
        ] + ['event' => $type];
    }

    /**
     * Tells every webhook of this vendor that listens for [$type]. Sent after the response, so a slow
     * endpoint never slows the request that caused the event, and a failure never breaks it.
     */
    public static function emit(int $vendorId, string $type, array $object, array $legacy = []): int
    {
        if ($vendorId <= 0 || !isset(self::CATALOGUE[$type])) {
            return 0;
        }
        // Telling webhooks is never allowed to break the thing that happened.
        try {
            $hooks = VendorWebhook::where('vendor_id', $vendorId)->where('active', true)
                ->where(fn ($q) => $q->whereJsonContains('events', $type)->orWhereJsonContains('events', '*'))->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('webhook_lookup_failed: ' . $e->getMessage());

            return 0;
        }
        if ($hooks->isEmpty()) {
            return 0;
        }
        $body = self::envelope($type, $object, $legacy);
        foreach ($hooks as $h) {
            try {
                DeliverWebhook::dispatchAfterResponse($h->id, $type, $body);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('webhook_dispatch_failed: ' . $e->getMessage());
            }
        }

        return $hooks->count();
    }
}
