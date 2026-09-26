<?php

namespace Modules\Vendor\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/** Tells a company its trial or plan is about to end (7, 3 and 1 day before) and when it has ended. Each notice is sent once. */
class PlanReminders extends Command
{
    protected $signature = 'vendors:plan-reminders';
    protected $description = 'E-mail companies whose trial or plan is ending';

    public function handle(): int
    {
        if (!is_enable_plan()) {
            return self::SUCCESS;
        }
        $sent = 0;
        foreach (User::whereNotNull('vendor_plan_id')->whereNotNull('vendor_plan_expires_at')->whereNotNull('email')->get() as $u) {
            $ends = \Carbon\Carbon::parse($u->vendor_plan_expires_at);
            $left = (int) now()->startOfDay()->diffInDays($ends->copy()->startOfDay(), false);
            $slot = in_array($left, [7, 3, 1], true) ? "d{$left}" : ($left === -1 ? 'ended' : null);   // "ended" goes out the day after
            if (!$slot) {
                continue;
            }
            $key = "plan-reminder:{$u->id}:{$ends->toDateString()}:{$slot}";
            if (!Cache::add($key, 1, now()->addDays(10))) {
                continue;
            }
            $plan = \Modules\Vendor\Models\VendorPlan::find($u->vendor_plan_id)?->name;
            $trial = \Modules\Vendor\Models\VendorSubscription::where('vendor_id', $u->id)->where('status', 'active')->where('payment_gateway', 'trial')->exists();
            $subject = $slot === 'ended' ? __('Your :p plan has ended', ['p' => $plan]) : __('Your :p :k ends in :n day(s)', ['p' => $plan, 'k' => $trial ? __('free trial') : __('plan'), 'n' => $left]);
            $body = $slot === 'ended'
                ? __("Your plan has ended. Your data, bookings and invoices are safe, but you cannot add listings until you renew.\n\nRenew under Plan & billing: :u", ['u' => url('/vendor/subscription')])
                : __("You have :n day(s) left. To keep adding listings without a break, choose or renew a plan under Plan & billing: :u", ['n' => $left, 'u' => url('/vendor/subscription')]);
            try {
                Mail::raw($body, fn ($m) => $m->to($u->email)->subject($subject));
                $sent++;
            } catch (\Throwable $e) {
                \Log::warning('plan reminder: ' . $e->getMessage());
            }
        }
        $this->info("{$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
