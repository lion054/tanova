<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Run by the one cron line `php artisan schedule:run` (every minute).
| (app/Console/Kernel.php is not used by this app, so nothing scheduled there would ever run.)
*/

// These predate this file and have NEVER run on production (see above). They send real messages to guests, expire
// plans and delete stale Tanova trips, so they stay off until someone switches them on on purpose:
//   SCHEDULE_LEGACY_TASKS=true   in .env, then `php artisan config:cache`.
if (filter_var(env('SCHEDULE_LEGACY_TASKS', false), FILTER_VALIDATE_BOOLEAN)) {
    Schedule::command(\App\Console\Commands\ScanUserPlanExpiredCommand::class)->daily()->withoutOverlapping();

    // Send due lifecycle scheduled messages for every vendor.
    Schedule::command(\Modules\Vendor\Commands\DispatchScheduledMessages::class)->dailyAt('08:00')->withoutOverlapping();

    // Delete unbooked Tanova trips older than the configured window.
    Schedule::call(function () {
        $hours = (int) (setting_item('tanova_window_hours') ?: 24);
        \Pro\Tanova\Models\TanovaTrip::where('status', \Pro\Tanova\Models\TanovaTrip::STATUS_CREATED)
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();
    })->hourly()->name('tanova.cleanup')->withoutOverlapping();
}

// Webhook deliveries that failed are tried again after 1 min, 5 min, 30 min, 2 h and 12 h.
Schedule::command(\Modules\Vendor\Commands\RetryWebhooks::class)->everyMinute()->withoutOverlapping();

// Online payments that finished after the guest closed the tab are found and recorded (only touches open attempts).
Schedule::command(\Modules\TourPay\Commands\ReconcilePayments::class)->everyTenMinutes()->withoutOverlapping();

// Payment reminders: only for vendors who turned them on in TourPay settings (off by default).
Schedule::command(\Modules\TourPay\Commands\SendReminders::class)->dailyAt('08:00')->withoutOverlapping();

// Idempotency keys are honoured for 30 days, then forgotten (the API guides say so).
Schedule::call(fn () => DB::table('bc_vendor_idempotency_keys')->where('created_at', '<', now()->subDays(30))->delete())
    ->daily()->name('prune-idempotency-keys')->withoutOverlapping();

// Proof for /health that cron is really calling the scheduler.
Schedule::call(fn () => \App\Support\Health::beat())->everyMinute()->name('health-heartbeat');
