<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Collection;
use Modules\Booking\Models\Booking;

/** Who a campaign goes to: distinct e-mail addresses drawn only from this vendor's own bookings. */
class CampaignAudience
{
    public const AUDIENCES = ['all_customers' => 'Everyone who has booked', 'completed' => 'Guests who completed a trip', 'upcoming' => 'Guests with a trip coming up'];

    /** @return Collection<int,string> */
    public function emails(int $vendorId, string $audience): Collection
    {
        $q = Booking::where('vendor_id', $vendorId)->whereNotNull('email')->where('email', '!=', '');
        if ($audience === 'completed') {
            $q->where('status', Booking::COMPLETED);
        } elseif ($audience === 'upcoming') {
            $q->whereDate('start_date', '>=', now()->toDateString());
        }

        return $q->pluck('email')->map(fn ($e) => strtolower(trim($e)))->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values();
    }
}
