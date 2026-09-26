<?php

namespace Modules\Vendor\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorPlan;
use Modules\Vendor\Models\VendorSubscription;
use Modules\Vendor\Services\CompanyOs;
use Modules\Vendor\Services\Onboarding;

/**
 * Gives the companies already on the portal everything (the plan that covers every OS) until a date, at no charge. Used when plans
 * were rebuilt around Tanova OS, so nobody who was here first loses anything. Companies on a trial are left alone.
 */
class GrantFoundingPlan extends Command
{
    protected $signature = 'vendors:grant-founders {--until=2028-05-31 : last day} {--ids= : only these company ids, comma separated} {--dry-run : list who would get it, change nothing}';
    protected $description = 'Give existing companies the all-OS plan until a date';

    public function handle(): int
    {
        $until = \Carbon\Carbon::parse($this->option('until'))->endOfDay();
        $plan = VendorPlan::where('status', 'publish')->where('os_limit', 0)->orderByDesc('price')->first();
        if (!$plan) {
            $this->error('No plan covers every OS.');

            return self::FAILURE;
        }
        $query = User::where('role_id', Onboarding::vendorRoleId())->whereNotNull('vendor_plan_id');
        if ($this->option('ids')) {
            $query->whereIn('id', array_map('intval', explode(',', $this->option('ids'))));
        }
        $n = 0;
        foreach ($query->get() as $u) {
            $active = VendorSubscription::activeForVendor($u->id);
            if ($active && $active->payment_gateway === 'trial') {
                $this->line("#{$u->id} {$u->email}: on a trial, skipped");
                continue;
            }
            if ((int) $u->vendor_plan_id === (int) $plan->id && $u->vendor_plan_expires_at && \Carbon\Carbon::parse($u->vendor_plan_expires_at)->gte($until)) {
                $this->line("#{$u->id} {$u->email}: already has {$plan->name} beyond that date, left as it is");
                continue;   // never shorten what someone already has
            }
            $this->info("#{$u->id} " . ($u->business_name ?: $u->email) . ": {$plan->name} until " . $until->toDateString());
            $n++;
            if ($this->option('dry-run')) {
                continue;
            }
            DB::transaction(function () use ($u, $plan, $until) {
                VendorSubscription::where('vendor_id', $u->id)->where('status', VendorSubscription::STATUS_ACTIVE)->update(['status' => VendorSubscription::STATUS_CANCELLED]);
                VendorSubscription::create(['vendor_id' => $u->id, 'plan_id' => $plan->id, 'billing_cycle' => 'yearly', 'amount_paid' => 0, 'payment_gateway' => 'manual', 'transaction_id' => 'founders-' . $until->year . '-' . $until->format('m'),
                    'status' => VendorSubscription::STATUS_ACTIVE, 'starts_at' => now(), 'ends_at' => $until, 'notes' => 'Founding company: everything included until ' . $until->format('F Y') . '.']);
                $u->vendor_plan_id = $plan->id;
                $u->vendor_plan_expires_at = $until;
                $u->save();
                DB::table('vendor_company_os')->where('vendor_id', $u->id)->where('is_addon', 1)->delete();
            });
        }
        $this->line($this->option('dry-run') ? "{$n} would be updated (dry run)." : "{$n} updated.");

        return self::SUCCESS;
    }
}
