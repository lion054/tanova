<?php

namespace Modules\Vendor\Services;

use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingComm;

/**
 * Where a booking can go next, and doing it: the same moves the vendor API allows,
 * plus confirming one that is still waiting for payment (an accepted quote, or a
 * bank transfer the vendor has seen). Every move is written into the booking's
 * timeline, with the reason when there is one, and tells the customer.
 */
class BookingStatusFlow
{
    /** status => where it may go */
    private const NEXT = [
        Booking::DRAFT           => [Booking::CANCELLED],
        Booking::UNPAID          => [Booking::CONFIRMED, Booking::CANCELLED],
        Booking::PROCESSING      => [Booking::CONFIRMED, Booking::CANCELLED],
        Booking::PARTIAL_PAYMENT => [Booking::CONFIRMED, Booking::CANCELLED],
        Booking::PAID            => [Booking::CONFIRMED, Booking::COMPLETED, Booking::CANCELLED],
        Booking::CONFIRMED       => [Booking::COMPLETED, Booking::CANCELLED],
    ];

    /** @return string[] */
    public function allowedFrom(string $status): array
    {
        return self::NEXT[$status] ?? [];
    }

    public function can(Booking $booking, string $to): bool
    {
        return in_array($to, $this->allowedFrom((string) $booking->status), true);
    }

    /**
     * @throws \InvalidArgumentException when the move is not allowed
     */
    public function move(Booking $booking, string $to, ?string $reason = null, ?int $by = null): Booking
    {
        if (!$this->can($booking, $to)) {
            throw new \InvalidArgumentException("A {$booking->status} booking cannot become {$to}.");
        }

        $from = $booking->status;
        $booking->status = $to;
        $booking->save();

        BookingComm::create([
            'booking_id' => $booking->id,
            'channel'    => 'note',
            'direction'  => 'internal',
            'subject'    => __('Status: :from → :to', ['from' => $from, 'to' => $to]),
            'body'       => $reason ?: __('Changed by the vendor.'),
            'created_by' => $by,
        ]);

        // The customer and the vendor are told, and anything listening (commission,
        // webhooks, loyalty) hears about it.
        try {
            $booking->sendStatusUpdatedEmails();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Booking status e-mail failed: ' . $e->getMessage());
        }
        event(new BookingUpdatedEvent($booking));

        // A completed trip earns the guest loyalty points.
        if ($to === Booking::COMPLETED) {
            try {
                app(LoyaltyPoints::class)->awardForBooking($booking);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Loyalty points failed: ' . $e->getMessage());
            }
        }

        // Seats given back are offered to whoever is waiting; a guest who books what they
        // waited for stops waiting.
        try {
            if ($to === Booking::CANCELLED) {
                app(WaitlistNotifier::class)->afterCancelled($booking);
            } elseif ($to === Booking::CONFIRMED) {
                app(WaitlistNotifier::class)->markBooked($booking);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Waitlist update failed: ' . $e->getMessage());
        }

        return $booking;
    }
}
