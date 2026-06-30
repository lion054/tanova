<?php

namespace Modules\Vendor\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\ScheduledMessageLog;
use Modules\Vendor\Models\VendorOccasion;
use Modules\Vendor\Services\VendorChannelDispatcher;

/**
 * Phase 3 — daily dispatcher for lifecycle scheduled messages.
 *
 * Runs in CLI (no auth) so the BelongsToVendor global scope is a no-op; this
 * command therefore iterates EVERY vendor's active messages but always filters
 * bookings/occasions and stamps logs by that message's own vendor_id — never
 * crossing tenants. Idempotent: a (message, booking) pair is only sent once.
 *
 * Run: php artisan vendor:dispatch-scheduled-messages [--dry-run] [--date=Y-m-d]
 */
class DispatchScheduledMessages extends Command
{
    protected $signature   = 'vendor:dispatch-scheduled-messages {--dry-run} {--date=}';
    protected $description = 'Send due lifecycle scheduled messages for all vendors';

    public function handle(): int
    {
        $today  = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $dryRun = (bool) $this->option('dry-run');
        $sent   = 0;

        $messages = ScheduledMessage::where('active', true)->get();

        foreach ($messages as $message) {
            $recipients = $message->trigger === 'occasion'
                ? $this->occasionRecipients($message, $today)
                : $this->bookingRecipients($message, $today);

            foreach ($recipients as $r) {
                if (empty($r['email'])) {
                    continue;
                }

                // Idempotency — skip if already sent. Keyed on booking_id when present,
                // otherwise on the recipient (occasion messages have no booking_id, so
                // a null booking_id must NOT collapse all recipients into one).
                $identity = $r['booking_id']
                    ? ['booking_id' => $r['booking_id']]
                    : ['booking_id' => null, 'recipient' => $r['email']];

                $already = ScheduledMessageLog::where('scheduled_message_id', $message->id)
                    ->where($identity)
                    ->where('status', 'sent')
                    ->exists();

                if ($already) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would send '{$message->name}' → {$r['email']} (vendor {$message->vendor_id})");
                    $sent++;
                    continue;
                }

                $this->deliver($message, $r);
                $sent++;
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Processed {$sent} message(s).");

        return self::SUCCESS;
    }

    private function bookingRecipients(ScheduledMessage $message, Carbon $today): array
    {
        // booking_date + offset_days == today  ⇒  booking_date == today - offset_days
        $targetDate = $today->copy()->subDays($message->offset_days)->toDateString();

        return Booking::where('vendor_id', $message->vendor_id)
            ->whereNotIn('status', Booking::$notAcceptedStatus)
            ->whereDate($message->dateColumn(), $targetDate)
            ->get()
            ->map(fn ($b) => [
                'email'      => $b->email,
                'phone'      => $b->phone,
                'name'       => trim(($b->first_name ?? '') . ' ' . ($b->last_name ?? '')),
                'booking_id' => $b->id,
            ])
            ->all();
    }

    private function occasionRecipients(ScheduledMessage $message, Carbon $today): array
    {
        $target = $today->copy()->subDays($message->offset_days);

        return VendorOccasion::where('vendor_id', $message->vendor_id)
            ->whereMonth('occasion_date', $target->month)
            ->whereDay('occasion_date', $target->day)
            ->get()
            ->map(fn ($o) => [
                'email'      => $o->customer_email,
                'phone'      => $o->customer_phone,
                'name'       => $o->customer_name,
                'booking_id' => null,
            ])
            ->all();
    }

    private function deliver(ScheduledMessage $message, array $r): void
    {
        $body    = str_replace('{name}', $r['name'] ?: 'there', $message->body);
        $subject = $message->subject ?: $message->name;

        // Delegate to the vendor's own connected channel (email/whatsapp/telegram/…).
        $result = app(VendorChannelDispatcher::class)->send(
            (int) $message->vendor_id,
            $message->channel,
            $r,
            $subject,
            $body
        );

        $recipient = $result['to'] ?? ($r['email'] ?? $r['phone'] ?? null);

        // Upsert one log row per (message, booking-or-recipient) so repeated runs
        // update in place instead of accumulating skipped/failed rows.
        ScheduledMessageLog::updateOrCreate(
            [
                'scheduled_message_id' => $message->id,
                'booking_id'           => $r['booking_id'],
                'recipient'            => $recipient,
            ],
            [
                'vendor_id' => $message->vendor_id,
                'channel'   => $message->channel,
                'status'    => $result['status'],
                'error'     => $result['error'] ?? null,
                'sent_at'   => $result['status'] === 'sent' ? now() : null,
            ]
        );
    }
}
