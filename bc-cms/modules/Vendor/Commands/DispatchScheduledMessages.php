<?php

namespace Modules\Vendor\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingGuest;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\ScheduledMessageLog;
use Modules\Vendor\Models\VendorOccasion;
use Modules\Vendor\Services\BookingPayments;
use Modules\Vendor\Services\GuestForm;
use Modules\Vendor\Services\OccasionSync;
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

        // Birthdays read off customers and guest forms are kept in Occasions first.
        if (!$dryRun) {
            foreach ($messages->where('trigger', 'occasion')->pluck('vendor_id')->unique() as $vendorId) {
                app(OccasionSync::class)->run((int) $vendorId);
            }
        }

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
                    // An occasion comes round every year, so it is sent once a year.
                    ->when($message->trigger === 'occasion', fn ($q) => $q->whereYear('sent_at', $today->year))
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
        // booking_date + offset == today  =>  booking_date == today - offset
        $targetDate = $today->copy()->subDays($message->offset_days)->toDateString();

        $bookings = Booking::where('vendor_id', $message->vendor_id)
            ->whereNotIn('status', Booking::$notAcceptedStatus)
            ->whereDate($message->dateColumn(), $targetDate)
            ->get();

        $payments = app(BookingPayments::class);
        $out = [];
        foreach ($bookings as $b) {
            $balance = $payments->balance($b);
            if ($message->trigger === 'payment_due' && $balance <= 0) {
                continue;
            }
            if ($message->trigger === 'guest_form'
                && BookingGuest::withoutVendorScope()->where('booking_id', $b->id)->where('source', 'customer')->exists()) {
                continue; // the customer has already filled it in
            }

            $points = 0;
            if ($message->trigger === 'loyalty_offer' && $b->email) {
                $points = (int) LoyaltyAccount::withoutVendorScope()->where('vendor_id', $b->vendor_id)
                    ->whereRaw('LOWER(customer_email) = ?', [strtolower($b->email)])->value('points');
            }

            $first = trim((string) $b->first_name);
            $out[] = [
                'email'      => $b->email,
                'phone'      => $b->phone,
                'name'       => trim($first . ' ' . ($b->last_name ?? '')),
                'booking_id' => $b->id,
                'vars'       => [
                    '{name}'            => $first ?: 'there',
                    '{reference}'       => $b->code ? strtoupper(substr($b->code, 0, 8)) : (string) $b->id,
                    '{trip}'            => optional($b->service)->title ?: 'your trip',
                    '{date}'            => $b->start_date ? Carbon::parse($b->start_date)->format('D j M Y') : '',
                    '{guest_form_link}' => $message->trigger === 'guest_form' || str_contains($message->body, '{guest_form_link}') ? app(GuestForm::class)->urlFor($b) : '',
                    '{balance}'         => '$' . number_format($balance, 2),
                    '{points}'          => (string) $points,
                ],
            ];
        }

        return $out;
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
                'vars'       => ['{name}' => explode(' ', trim((string) $o->customer_name))[0] ?: 'there'],
            ])
            ->all();
    }

    private function deliver(ScheduledMessage $message, array $r): void
    {
        $vars    = ($r['vars'] ?? []) + ['{name}' => $r['name'] ?: 'there'];
        $body    = ScheduledMessage::render($message->body, $vars);
        $subject = ScheduledMessage::render($message->subject ?: $message->name, $vars);

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
