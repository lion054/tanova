<?php

namespace Modules\Vendor\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Modules\Vendor\Events\NewVendorRegistered;
use Modules\Vendor\Models\VendorPlan;
use Modules\Vendor\Models\VendorRequest;
use Modules\Vendor\Models\VendorSubscription;

/**
 * Signing up on the portal creates a vendor company: the person becomes its owner, and from there adds their own employees as staff
 * (Vendor > Team). A new company starts on a free trial of a plan so it can add listings straight away.
 *
 * Settings (all optional):
 *   vendor_signup_requires_approval  1 = a new company waits for the platform team's approval (it can sign in, but is not a vendor yet)
 *   vendor_signup_plan_id            the plan the trial is on when the person did not choose one (default: the cheapest published plan)
 *   vendor_signup_trial_days         length of the trial (default 30; 0 = no trial, a plan is assigned by hand)
 */
class Onboarding
{
    public static function requiresApproval(): bool
    {
        return (bool) setting_item('vendor_signup_requires_approval');
    }

    /** The id of the vendor role (the setting, else the role called vendor). */
    public static function vendorRoleId(): int
    {
        return (int) (setting_item('vendor_role') ?: DB::table('core_roles')->where('code', 'vendor')->value('id'));
    }

    /**
     * Turns a newly created account into a company owner (or, when approval is required, files the request).
     * @return array{approved:bool,trial:?VendorSubscription}
     */
    public function registerCompany(User $user, ?int $planId = null, array $os = []): array
    {
        $approved = !self::requiresApproval();
        $request = new VendorRequest(['role_request' => self::vendorRoleId(), 'status' => $approved ? 'approved' : 'pending', 'approved_time' => $approved ? now() : null]);
        $user->vendorRequest()->save($request);

        $trial = null;
        if ($approved) {
            $user->assignRole(self::vendorRoleId());
            $trial = $this->startTrial($user, $planId, $os);
        } else {
            $user->assignRole((int) DB::table('core_roles')->where('code', 'customer')->value('id'));
        }
        try {
            event(new NewVendorRegistered($user, $request));   // tells the platform team a company has signed up
        } catch (\Throwable $e) {
            \Log::warning('NewVendorRegistered: ' . $e->getMessage());
        }

        return ['approved' => $approved, 'trial' => $trial];
    }

    /** A free trial on the configured (or cheapest published) plan; records it as a subscription and sets the company's plan fields. */
    public function startTrial(User $user, ?int $planId = null, array $os = []): ?VendorSubscription
    {
        $days = (int) (setting_item('vendor_signup_trial_days', 30) ?: 0);
        if ($days <= 0) {
            return null;
        }
        $plan = $planId ? VendorPlan::offered()->firstWhere('id', $planId) : null;
        $plan ??= ($id = (int) setting_item('vendor_signup_plan_id')) ? VendorPlan::find($id) : null;
        $plan ??= VendorPlan::offered()->first();
        if (!$plan) {
            return null;
        }
        $ends = now()->addDays($days);
        $sub = VendorSubscription::create(['vendor_id' => $user->id, 'plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'amount_paid' => 0, 'payment_gateway' => 'trial', 'status' => VendorSubscription::STATUS_ACTIVE,
            'starts_at' => now(), 'ends_at' => $ends, 'notes' => "Free {$days}-day trial from sign-up."]);
        $user->vendor_plan_id = $plan->id;
        $user->vendor_plan_expires_at = $ends;
        $user->save();
        if ($os) {
            CompanyOs::choose($user, $os, $plan);
        }

        return $sub;
    }

    /** Whether a plan and OS choice made at sign-up hangs together. @return string|null what is wrong, or null when it is fine */
    public static function checkChoice(?int $planId, array $os): ?string
    {
        $plan = $planId ? VendorPlan::offered()->firstWhere('id', $planId) : null;
        if (!$plan) {
            return __('Choose a plan.');
        }
        $os = array_values(array_unique($os));
        foreach ($os as $k) {
            if (!isset(CompanyOs::all()[$k])) {
                return __('Unknown OS: :k', ['k' => $k]);
            }
        }
        if ($plan->coversAllOs()) {
            return null;
        }
        if (count($os) < 1) {
            return __('Choose what your company offers.');
        }
        if (count($os) > (int) $plan->os_limit) {
            return __(':plan covers :n OS. You picked :m: choose fewer, or pick a bigger plan.', ['plan' => $plan->name, 'n' => $plan->os_limit, 'm' => count($os)]);
        }
        if (count($os) < (int) $plan->os_limit && (int) $plan->os_limit === 1) {
            return __('Choose what your company offers.');
        }

        return null;
    }
}
