<?php

namespace Modules\TourPay\Commands;

use Illuminate\Console\Command;
use Modules\TourPay\Services\Reminders;

/** Payment reminders for the vendors who switched them on. `--dry-run` shows how many would go out. */
class SendReminders extends Command
{
    protected $signature = 'tourpay:remind {--dry-run}';
    protected $description = 'Send payment reminders for invoices that are due soon or overdue (only for vendors who enabled them)';

    public function handle(Reminders $reminders): int
    {
        $r = $reminders->run((bool) $this->option('dry-run'));
        $this->info(($this->option('dry-run') ? 'Would send ' : 'Sent ') . "{$r['sent']}, skipped {$r['skipped']}, failed {$r['failed']}.");

        return self::SUCCESS;
    }
}
