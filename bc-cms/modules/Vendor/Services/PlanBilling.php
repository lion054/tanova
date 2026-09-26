<?php

namespace Modules\Vendor\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Vendor\Models\VendorPlan;
use Modules\Vendor\Models\VendorPlanOrder;
use Modules\Vendor\Models\VendorSubscription;

/**
 * How a company pays the platform for its plan. A company asks (an order, with a reference to quote); the platform team confirms when the
 * money is in, and only then does the subscription start or extend. Nothing here charges a card, so a payment can never go missing:
 * an unpaid order simply stays pending.
 *
 * Rules: a plan change starts at once (paid time is not refunded); a renewal of the same plan adds to the end of the current period;
 * an extra OS bought mid-period is charged for the days left; a trial's extras are free until it converts (the first order includes them).
 */
class PlanBilling
{
    public static function planPrice(VendorPlan $plan, string $cycle): float
    {
        return $cycle === 'yearly' ? (float) ($plan->price_annual ?: $plan->price * 12) : (float) $plan->price;
    }

    public static function addonPrice(VendorPlan $plan, string $cycle): float
    {
        return $cycle === 'yearly' ? (float) ($plan->addon_price_annual ?: $plan->addon_price * 12) : (float) $plan->addon_price;
    }

    public static function onTrial(User $vendor): bool
    {
        $sub = VendorSubscription::activeForVendor($vendor->id);

        return $sub && $sub->payment_gateway === 'trial';
    }

    /** What the plan costs for this company now: the plan, plus the extras it keeps (a plan that covers every OS needs none). */
    public static function quote(User $vendor, VendorPlan $plan, string $cycle): float
    {
        $extras = $plan->coversAllOs() ? 0 : count(CompanyOs::extras($vendor));

        return round(self::planPrice($plan, $cycle) + $extras * self::addonPrice($plan, $cycle), 2);
    }

    /** @return VendorPlanOrder|string the order, or what is wrong with the request */
    public static function orderPlan(User $vendor, VendorPlan $plan, string $cycle, array $os)
    {
        if ($plan->status !== 'publish' || !$plan->is_public) {
            return __('That plan is not available.');
        }
        if ($cycle === 'yearly' && !$plan->price_annual) {
            $cycle = 'monthly';
        }
        $os = array_values(array_unique(array_filter($os)));
        $extras = CompanyOs::extras($vendor);
        $base = array_values(array_diff($os, $extras));
        if (!$plan->coversAllOs()) {
            if (count($base) < 1) {
                return __('Choose what your company offers.');
            }
            if (count($base) > (int) $plan->os_limit) {
                return __(':plan covers :n OS. Pick fewer, or add extra OS afterwards.', ['plan' => $plan->name, 'n' => $plan->os_limit]);
            }
        }
        foreach ($os as $k) {
            if (!isset(CompanyOs::all()[$k])) {
                return __('Unknown OS: :k', ['k' => $k]);
            }
        }
        if (VendorPlanOrder::where('vendor_id', $vendor->id)->where('status', VendorPlanOrder::PENDING)->where('kind', 'plan')->exists()) {
            return __('You already have an order waiting for payment. Pay it or cancel it first.');
        }

        return self::create($vendor, $plan, 'plan', $cycle, $os, self::quote($vendor, $plan, $cycle));
    }

    /** @return VendorPlanOrder|string|null the order to pay, a message when it cannot be done, null when it was added at no charge (trial) */
    public static function orderAddon(User $vendor, string $os, string $cycle = 'monthly')
    {
        $plan = CompanyOs::plan($vendor);
        if (!$plan || !isset(CompanyOs::all()[$os])) {
            return __('Choose a plan first.');
        }
        if ($plan->coversAllOs() || in_array($os, CompanyOs::chosen($vendor), true)) {
            return __('You already have that OS.');
        }
        if (!$plan->addon_price) {
            return __('Your plan cannot take extra OS: move to a bigger plan.');
        }
        if (self::onTrial($vendor)) {
            CompanyOs::addExtra($vendor, $os);   // free on trial; the first order includes it

            return null;
        }
        $sub = VendorSubscription::activeForVendor($vendor->id);
        if (!$sub) {
            return __('Renew your plan first, then add an OS.');
        }
        $cycle = $sub->billing_cycle === 'yearly' ? 'yearly' : 'monthly';
        $periodDays = $cycle === 'yearly' ? 365 : 30;
        $daysLeft = max(1, (int) ceil(now()->diffInDays($sub->ends_at, false)));
        $amount = round(self::addonPrice($plan, $cycle) * min($daysLeft, $periodDays) / $periodDays, 2);
        if (VendorPlanOrder::where('vendor_id', $vendor->id)->where('status', VendorPlanOrder::PENDING)->where('kind', 'addon')->whereJsonContains('os_keys', $os)->exists()) {
            return __('That OS is already waiting for payment.');
        }

        return self::create($vendor, $plan, 'addon', $cycle, [$os], $amount);
    }

    private static function create(User $vendor, VendorPlan $plan, string $kind, string $cycle, array $os, float $amount): VendorPlanOrder
    {
        $order = VendorPlanOrder::create(['vendor_id' => $vendor->id, 'plan_id' => $plan->id, 'billing_cycle' => $cycle, 'os_keys' => $os, 'kind' => $kind, 'amount' => $amount,
            'currency' => strtoupper((string) setting_item('currency_main', 'usd')), 'reference' => 'TP-' . strtoupper(Str::random(7)), 'status' => VendorPlanOrder::PENDING]);
        self::tellPlatform($order);

        return $order;
    }

    public static function cancel(VendorPlanOrder $order): void
    {
        if ($order->isPending()) {
            $order->status = VendorPlanOrder::CANCELLED;
            $order->save();
        }
    }

    /** The platform team confirms the money arrived: the plan starts (or extends), or the OS is added. */
    public static function confirm(VendorPlanOrder $order, ?User $by = null, ?string $method = null, ?string $note = null): void
    {
        if (!$order->isPending()) {
            return;
        }
        $vendor = $order->vendor;
        $plan = $order->plan;
        DB::transaction(function () use ($order, $vendor, $plan, $by, $method, $note) {
            if ($order->kind === 'addon') {
                CompanyOs::addExtra($vendor, (string) ($order->os_keys[0] ?? ''));
            } else {
                $active = VendorSubscription::activeForVendor($vendor->id);
                $renewal = $active && $active->payment_gateway !== 'trial' && (int) $active->plan_id === (int) $plan->id;
                $starts = $renewal ? $active->ends_at->copy() : now();
                $ends = $order->billing_cycle === 'yearly' ? $starts->copy()->addYear() : $starts->copy()->addMonth();
                if ($active && !$renewal) {
                    VendorSubscription::where('vendor_id', $vendor->id)->where('status', VendorSubscription::STATUS_ACTIVE)->update(['status' => VendorSubscription::STATUS_CANCELLED]);
                }
                VendorSubscription::create(['vendor_id' => $vendor->id, 'plan_id' => $plan->id, 'billing_cycle' => $order->billing_cycle, 'amount_paid' => $order->amount, 'payment_gateway' => $method ?: 'manual',
                    'transaction_id' => $order->reference, 'status' => VendorSubscription::STATUS_ACTIVE, 'starts_at' => $starts, 'ends_at' => $ends, 'notes' => $note, 'created_by' => $by?->id]);
                $vendor->vendor_plan_id = $plan->id;
                $vendor->vendor_plan_expires_at = $ends;
                $vendor->save();
                if ($plan->coversAllOs()) {
                    DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->where('is_addon', 1)->delete();
                } elseif (!empty($order->os_keys)) {
                    CompanyOs::choose($vendor, (array) $order->os_keys, $plan);
                }
            }
            $order->status = VendorPlanOrder::PAID;
            $order->paid_at = now();
            $order->confirmed_by = $by?->id;
            $order->payment_method = $method;
            $order->payment_note = $note;
            $order->save();
        });
        self::tell($vendor->email, __('Your payment was received'), __("Thank you. We received :amount for order :ref. :what\n\nSign in and open Plan & billing to see your plan.", [
            'amount' => self::money($order->amount, $order->currency), 'ref' => $order->reference,
            'what' => $order->kind === 'addon' ? __('The extra OS is now on your account.') : __(':plan is active on your account.', ['plan' => $plan->name])]));
    }

    public static function money(float $amount, string $currency = 'USD'): string
    {
        return ($currency === 'USD' ? '$' : $currency . ' ') . number_format($amount, 2);
    }

    /** Tells the people who run the platform that a company is waiting to pay. */
    private static function tellPlatform(VendorPlanOrder $order): void
    {
        $emails = User::where('role_id', DB::table('core_roles')->where('code', 'administrator')->value('id'))->whereNotNull('email')->limit(5)->pluck('email')->all();
        $what = $order->kind === 'addon' ? __('an extra OS (:os)', ['os' => CompanyOs::names((array) $order->os_keys)]) : __('the :plan plan (:cycle)', ['plan' => $order->plan->name, 'cycle' => $order->billing_cycle]);
        foreach ($emails as $to) {
            self::tell($to, __('New plan order :ref', ['ref' => $order->reference]), __(':company asked for :what, :amount. Confirm it under Vendor Plans > Plan orders once the money is in.', [
                'company' => $order->vendor->business_name ?: $order->vendor->email, 'what' => $what, 'amount' => self::money($order->amount, $order->currency)]));
        }
    }

    private static function tell(?string $to, string $subject, string $body): void
    {
        if (!$to) {
            return;
        }
        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
        } catch (\Throwable $e) {
            \Log::warning('PlanBilling mail: ' . $e->getMessage());
        }
    }
}
