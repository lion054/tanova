<?php

namespace App\Console;

use App\Console\Commands\ScanUserPlanExpiredCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pro\Tanova\Models\TanovaTrip;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command(ScanUserPlanExpiredCommand::class)->daily()->withoutOverlapping();

        // Phase 3 — send due lifecycle scheduled messages for all vendors.
        $schedule->command(\Modules\Vendor\Commands\DispatchScheduledMessages::class)
            ->dailyAt('08:00')->withoutOverlapping();

        // Delete unbooked Tanova trips older than the configured window
        $schedule->call(function () {
            $hours = (int) (setting_item('tanova_window_hours') ?: 24);
            TanovaTrip::where('status', TanovaTrip::STATUS_CREATED)
                ->where('created_at', '<', now()->subHours($hours))
                ->delete();
        })->hourly()->name('tanova.cleanup')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
