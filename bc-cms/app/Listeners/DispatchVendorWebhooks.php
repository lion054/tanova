<?php

namespace App\Listeners;

use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Vendor\Services\WebhookEvents;

class DispatchVendorWebhooks
{
    // Map Booking status values to webhook event names
    private const STATUS_EVENT_MAP = [
        'confirmed'  => 'booking.confirmed',
        'cancelled'  => 'booking.cancelled',
        'completed'  => 'booking.completed',
        'paid'       => 'booking.paid',
        'processing' => 'booking.created',
    ];

    public function handle(BookingUpdatedEvent $event): void
    {
        $booking = $event->booking;

        if (!$booking->vendor_id) {
            return;
        }

        $webhookEvent = self::STATUS_EVENT_MAP[$booking->status] ?? null;

        if (!$webhookEvent) {
            return;
        }

        $object = [
            'code'         => $booking->code,
            'status'       => $booking->status,
            'object_model' => $booking->object_model,
            'object_id'    => $booking->object_id,
            'total'        => $booking->total,
            'currency'     => $booking->currency ?? 'USD',
            'customer'     => [
                'name'  => $booking->first_name . ' ' . $booking->last_name,
                'email' => $booking->email,
                'phone' => $booking->phone,
            ],
            'check_in'     => $booking->start_date,
            'check_out'    => $booking->end_date,
            'created_at'   => $booking->created_at?->toIso8601String(),
            'updated_at'   => $booking->updated_at?->toIso8601String(),
        ];

        // The envelope is new; the original `event` and `booking` keys are kept for receivers already written.
        WebhookEvents::emit((int) $booking->vendor_id, $webhookEvent, $object, ['event' => $webhookEvent, 'booking' => $object]);
    }
}
