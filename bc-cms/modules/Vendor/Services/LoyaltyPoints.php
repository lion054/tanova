<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\LoyaltyRule;
use Modules\Vendor\Models\LoyaltyTier;
use Modules\Vendor\Models\LoyaltyTransaction;

/**
 * Loyalty points for a completed trip.
 *
 * A guest earns one point for every [spend_per_point] they spent (rounded down),
 * multiplied by the earn multiplier of the tier they are in when the trip
 * completes. It happens once per booking, however many times completion is
 * reported. Points are worked out from what was paid, not the price, so a trip
 * that was refunded earns on what stayed paid; a booking with nothing paid earns
 * nothing. The tier follows the running balance.
 */
class LoyaltyPoints
{
    /**
     * @return LoyaltyTransaction|null the points given, or null when none were (no
     *                                 e-mail to credit, the programme is off, nothing
     *                                 paid, or this booking was already credited)
     */
    public function awardForBooking(Booking $booking): ?LoyaltyTransaction
    {
        $email = strtolower(trim((string) $booking->email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $rule = LoyaltyRule::withoutVendorScope()->where('vendor_id', $booking->vendor_id)->first();
        $enabled = $rule ? (bool) $rule->enabled : true;
        $per = $rule ? (float) $rule->spend_per_point : 10.0;
        if (!$enabled || $per <= 0) {
            return null;
        }

        $spent = (float) $booking->paid;
        if ($spent <= 0) {
            return null;
        }

        return DB::transaction(function () use ($booking, $email, $per, $spent) {
            $already = LoyaltyTransaction::withoutVendorScope()
                ->where('vendor_id', $booking->vendor_id)->where('booking_id', $booking->id)->where('type', 'earn')->exists();
            if ($already) {
                return null;
            }

            $account = LoyaltyAccount::withoutVendorScope()
                ->where('vendor_id', $booking->vendor_id)->where('customer_email', $email)->first();
            $tier = $account && $account->tier_id ? LoyaltyTier::withoutVendorScope()->find($account->tier_id) : null;
            $multiplier = $tier ? (float) $tier->earn_multiplier : 1.0;

            $points = (int) floor(floor($spent / $per) * $multiplier);
            if ($points <= 0) {
                return null; // no account is opened for a trip too small to earn
            }

            $account ??= LoyaltyAccount::withoutVendorScope()->create([
                'vendor_id' => $booking->vendor_id, 'customer_email' => $email,
                'customer_name' => trim($booking->first_name . ' ' . $booking->last_name) ?: null, 'points' => 0,
            ]);

            $tx = LoyaltyTransaction::withoutVendorScope()->create([
                'vendor_id'  => $booking->vendor_id,
                'account_id' => $account->id,
                'points'     => $points,
                'type'       => 'earn',
                'reason'     => __('Trip :code completed', ['code' => $booking->code ? strtoupper(substr($booking->code, 0, 8)) : $booking->id]),
                'booking_id' => $booking->id,
            ]);

            $account->points = $account->points + $points;
            $account->tier_id = optional(
                LoyaltyTier::withoutVendorScope()->where('vendor_id', $booking->vendor_id)
                    ->where('min_points', '<=', $account->points)->orderByDesc('min_points')->first()
            )->id;
            $account->save();
            $this->announce($account, $points, (string) $booking->code);

            return $tx;
        });
    }

    /**
     * Gives or takes points by hand for a guest, by e-mail. The balance never goes below zero and the
     * tier follows the new balance. The same call serves the portal screen and the API.
     */
    public function adjust(int $vendorId, string $email, ?string $name, int $points, ?string $reason): LoyaltyAccount
    {
        $email = strtolower(trim($email));

        return DB::transaction(function () use ($vendorId, $email, $name, $points, $reason) {
            $account = LoyaltyAccount::withoutVendorScope()->firstOrCreate(
                ['vendor_id' => $vendorId, 'customer_email' => $email],
                ['customer_name' => $name ?: null, 'points' => 0]
            );
            LoyaltyTransaction::withoutVendorScope()->create([
                'vendor_id' => $vendorId, 'account_id' => $account->id, 'points' => $points,
                'type' => $points >= 0 ? 'earn' : 'redeem', 'reason' => $reason,
            ]);
            $account->points = max(0, $account->points + $points);
            $account->tier_id = $this->tierFor($vendorId, (int) $account->points)?->id;
            $account->save();
            if ($points > 0) {
                $this->announce($account, $points, null);
            }

            return $account;
        });
    }

    private function announce(LoyaltyAccount $a, int $added, ?string $bookingCode): void
    {
        WebhookEvents::emit((int) $a->vendor_id, 'loyalty.points_earned', [
            'email' => $a->customer_email, 'name' => $a->customer_name, 'points_added' => $added, 'balance' => (int) $a->points,
            'tier' => $a->tier_id ? optional(LoyaltyTier::withoutVendorScope()->find($a->tier_id))->name : null, 'booking_code' => $bookingCode,
        ]);
    }

    /** The highest tier whose threshold the balance reaches, or null. */
    public function tierFor(int $vendorId, int $points): ?LoyaltyTier
    {
        return LoyaltyTier::withoutVendorScope()->where('vendor_id', $vendorId)->where('min_points', '<=', $points)->orderByDesc('min_points')->first();
    }

    /** After tiers change: put every member of this vendor in the tier their balance now earns. */
    public function retier(int $vendorId): void
    {
        foreach (LoyaltyAccount::withoutVendorScope()->where('vendor_id', $vendorId)->get() as $a) {
            $id = $this->tierFor($vendorId, (int) $a->points)?->id;
            if ($a->tier_id !== $id) {
                $a->tier_id = $id;
                $a->save();
            }
        }
    }

    /**
     * What these guests have done with the vendor: trips, money spent, last trip.
     *
     * @param string[] $emails lower-case
     * @return \Illuminate\Support\Collection keyed by e-mail, each {trips, spent, last_trip}
     */
    public function stats(int $vendorId, array $emails)
    {
        return Booking::where('vendor_id', $vendorId)
            ->whereIn(DB::raw('LOWER(email)'), $emails ?: [''])
            ->whereNotIn('status', Booking::$notAcceptedStatus)
            ->selectRaw('LOWER(email) as e, COUNT(*) as trips, SUM(paid) as spent, MAX(start_date) as last_trip')
            ->groupBy(DB::raw('LOWER(email)'))->get()->keyBy('e');
    }
}
