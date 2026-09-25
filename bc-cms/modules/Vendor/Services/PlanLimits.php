<?php

namespace Modules\Vendor\Services;

use App\User;
use Modules\Booking\Models\Service;

/**
 * What a business's plan allows it to create. One rule, used by the API and by the portal's own create screens, so the two can never disagree.
 *
 * Only creating listings is limited. Bookings, invoices, payments and customers are never switched off by a plan: a lapsed plan must not
 * stop a business from serving guests who have already paid it.
 */
class PlanLimits
{
    /** Listing types the plans control (the post_type keys of the plan's meta). */
    public const TYPES = ['tour', 'hotel', 'space', 'car', 'boat', 'event', 'flight'];

    /**
     * @return array{status:int,code:string,message:string}|null null when the business may create another listing of this type
     */
    public static function check(?User $vendor, string $type): ?array
    {
        if (!$vendor) {
            return ['status' => 401, 'code' => 'unauthenticated', 'message' => 'No vendor context.'];
        }
        // Plans can be switched off for the whole platform.
        if (!is_enable_plan()) {
            return null;
        }
        // vendor_plan_enable accounts for the grace period.
        if (!$vendor->vendor_plan_enable) {
            return ['status' => 402, 'code' => 'subscription_required', 'message' => 'An active subscription is required to create listings.'];
        }
        $planData = $vendor->vendorPlanData;
        // A plan with no rule for this type puts no limit on it.
        if (empty($planData) || !isset($planData[$type])) {
            return null;
        }
        $meta = $planData[$type];
        if (empty($meta['enable'])) {
            return ['status' => 403, 'code' => 'plan_service_disabled', 'message' => "Your plan does not include {$type} listings."];
        }
        $max = (int) ($meta['maximum_create'] ?? 0);
        if ($max > 0 && Service::where('author_id', $vendor->id)->where('object_model', $type)->count() >= $max) {
            return ['status' => 403, 'code' => 'plan_limit_reached', 'message' => "Your plan allows a maximum of {$max} {$type} listings. Upgrade your plan to add more."];
        }

        return null;
    }

    /**
     * Where a business stands, for the notice at the top of its portal: 'none', 'expired', 'expiring' (within 14 days) or 'ok'.
     * @return array{state:string,days:?int,ends:?string,plan:?string}
     */
    public static function state(User $vendor): array
    {
        $ends = $vendor->vendor_plan_expires_at ? \Carbon\Carbon::parse($vendor->vendor_plan_expires_at) : null;
        $plan = $vendor->vendor_plan_id ? \Modules\Vendor\Models\VendorPlan::find($vendor->vendor_plan_id)?->name : null;
        if (!$vendor->vendor_plan_id || !$ends) {
            return ['state' => 'none', 'days' => null, 'ends' => null, 'plan' => $plan];
        }
        if (!$vendor->vendor_plan_enable) {
            return ['state' => 'expired', 'days' => (int) $ends->diffInDays(now()), 'ends' => $ends->toDateString(), 'plan' => $plan];
        }
        $left = (int) now()->diffInDays($ends, false);

        return ['state' => $left <= 14 ? 'expiring' : 'ok', 'days' => $left, 'ends' => $ends->toDateString(), 'plan' => $plan];
    }
}
