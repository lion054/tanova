<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;

/**
 * The link a customer opens to tell the vendor who is travelling. It carries a long
 * random token, is tied to one booking, and shows nothing but that booking's trip.
 */
class GuestForm
{
    private const META = 'guest_token';

    /** The booking's link token, made on first use. */
    public function tokenFor(Booking $booking): string
    {
        $existing = (string) $booking->getMeta(self::META);
        if ($existing !== '') {
            return $existing;
        }

        return $this->regenerate($booking);
    }

    /** A new link; the old one stops working. */
    public function regenerate(Booking $booking): string
    {
        DB::table('bc_booking_meta')->where(['booking_id' => $booking->id, 'name' => self::META])->delete();
        $token = Str::random(40);
        $booking->addMeta(self::META, $token);

        return $token;
    }

    public function urlFor(Booking $booking): string
    {
        return url('guest-form/' . $this->tokenFor($booking));
    }

    /** The booking behind a link, or null for a token that isn't one. */
    public function bookingFor(string $token): ?Booking
    {
        if (!preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            return null;
        }
        $id = DB::table('bc_booking_meta')->where(['name' => self::META, 'val' => $token])->value('booking_id');

        return $id ? Booking::find($id) : null;
    }
}
