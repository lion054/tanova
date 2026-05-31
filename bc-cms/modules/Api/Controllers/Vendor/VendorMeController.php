<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Modules\Vendor\Models\VendorApiKey;
use Modules\Vendor\Models\VendorSubscription;

class VendorMeController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v/me
     * Returns authenticated vendor identity, subscription, and API key usage.
     */
    public function __invoke(): JsonResponse
    {
        $vendor = VendorContext::get();

        $subscription = VendorSubscription::activeForVendor($vendor->id);

        $keyUsage = VendorApiKey::where('vendor_id', $vendor->id)
            ->where('active', true)
            ->get()
            ->map(fn($k) => [
                'id'              => $k->id,
                'name'            => $k->name,
                'rate_limit'      => $k->rate_limit,
                'used_this_month' => $k->monthlyUsageCount(),
                'last_used_at'    => $k->last_used_at,
            ]);

        return $this->success([
            'vendor' => [
                'id'         => $vendor->id,
                'name'       => $vendor->name,
                'email'      => $vendor->email,
                'plan'       => $vendor->vendorPlan?->name,
                'plan_expires_at' => $vendor->vendor_plan_expires_at,
            ],
            'subscription' => $subscription ? [
                'status'     => $subscription->status,
                'ends_at'    => $subscription->ends_at,
                'plan'       => $subscription->plan?->name,
                'billing_cycle' => $subscription->billing_cycle,
            ] : null,
            'api_keys' => $keyUsage,
        ]);
    }
}
