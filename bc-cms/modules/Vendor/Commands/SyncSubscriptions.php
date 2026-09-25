<?php

namespace Modules\Vendor\Commands;

use App\User;
use Illuminate\Console\Command;
use Modules\Vendor\Models\VendorSubscription;

/**
 * Keeps the subscription records honest: one that has run out is marked expired (nothing did that before, so an ended subscription still
 * read "Active" in the admin list), and a business's own plan fields (vendor_plan_id, vendor_plan_expires_at, which the portal checks) are
 * made to agree with its subscription. A plan on a business with no subscription record is reported; --fix writes the missing record.
 */
class SyncSubscriptions extends Command
{
    protected $signature = 'vendors:sync-subscriptions {--fix : write missing subscription records and correct disagreeing plan fields}';
    protected $description = 'Mark ended subscriptions as expired and check plan fields agree with subscriptions';

    public function handle(): int
    {
        $expired = VendorSubscription::where('status', VendorSubscription::STATUS_ACTIVE)->where('ends_at', '<=', now())->update(['status' => VendorSubscription::STATUS_EXPIRED]);
        $this->info("Marked {$expired} ended subscription(s) as expired.");

        $problems = 0;
        foreach (User::whereNotNull('vendor_plan_id')->orWhereNotNull('vendor_plan_expires_at')->get() as $u) {
            $active = VendorSubscription::activeForVendor($u->id);
            if (!$active) {
                $latest = VendorSubscription::where('vendor_id', $u->id)->latest('ends_at')->first();
                $ends = $u->vendor_plan_expires_at ? \Carbon\Carbon::parse($u->vendor_plan_expires_at) : null;
                if ($ends && $ends->isFuture() && (!$latest || $latest->ends_at->lt($ends))) {
                    $problems++;
                    $this->warn("#{$u->id} {$u->email}: plan {$u->vendor_plan_id} until " . $ends->toDateString() . ' has no subscription record');
                    if ($this->option('fix')) {
                        VendorSubscription::create(['vendor_id' => $u->id, 'plan_id' => $u->vendor_plan_id, 'billing_cycle' => 'yearly', 'amount_paid' => 0, 'payment_gateway' => 'manual', 'status' => VendorSubscription::STATUS_ACTIVE,
                            'starts_at' => now(), 'ends_at' => $ends, 'notes' => 'Record written by vendors:sync-subscriptions for a plan that had none.']);
                    }
                }
                continue;
            }
            if ((int) $u->vendor_plan_id !== (int) $active->plan_id || !$u->vendor_plan_expires_at || !\Carbon\Carbon::parse($u->vendor_plan_expires_at)->equalTo($active->ends_at)) {
                $problems++;
                $this->warn("#{$u->id} {$u->email}: plan fields disagree with the active subscription");
                if ($this->option('fix')) {
                    $u->vendor_plan_id = $active->plan_id;
                    $u->vendor_plan_expires_at = $active->ends_at;
                    $u->save();
                }
            }
        }
        $this->line($problems ? ($this->option('fix') ? "Fixed {$problems}." : "{$problems} to look at (run with --fix)") : 'Plans and subscriptions agree.');

        return self::SUCCESS;
    }
}
