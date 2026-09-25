<?php

namespace Modules\Vendor\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingLedger;
use Modules\TourPay\Services\Ledger;
use Modules\TourPay\Services\ScheduleSync;
use Modules\Vendor\Models\BookingPaymentPlan;

/**
 * What a booking owes, when, and what has actually been paid or refunded.
 *
 * `bookings.paid` is the running total the gateways and the rest of the portal
 * already use (PayPal adds to it), so payments and refunds recorded here move it
 * too; the ledger is the itemised history behind it, and the plan is the
 * schedule of what is due. Status follows the money the way the gateways do it:
 * fully paid is PAID, part paid is PARTIAL_PAYMENT.
 */
class BookingPayments
{
    /** What is still to pay: the total less what has been paid. Never negative. */
    public function balance(Booking $booking): float
    {
        return max(0.0, round((float) $booking->total - (float) $booking->paid, 2));
    }

    /**
     * Replaces the unpaid part of the schedule.
     *
     * modes:
     *  - full:    everything due on [$dueBefore] days before the start (or now)
     *  - deposit: [$percent]% now, the rest [$balanceDays] days before the start
     *  - split:   [$parts] equal instalments a month apart, the last no later than
     *             [$balanceDays] days before the start
     * Paid rows are left alone and count towards the total; only what is still
     * owed is scheduled, and the last row takes any rounding.
     *
     * @return BookingPaymentPlan[]
     */
    public function buildPlan(Booking $booking, string $mode, array $opts = []): array
    {
        $owed = $this->balance($booking);
        BookingPaymentPlan::where('booking_id', $booking->id)->where('status', '!=', 'paid')->delete();
        if ($owed <= 0) {
            app(ScheduleSync::class)->bookingToInvoice($booking);

            return [];
        }

        $today = Carbon::today();
        $start = $booking->start_date ? Carbon::parse($booking->start_date)->startOfDay() : null;
        $balanceDays = max(0, (int) ($opts['balance_days'] ?? 14));
        $lastDue = $start ? $start->copy()->subDays($balanceDays) : $today->copy()->addDays(30);
        if ($lastDue->lt($today)) {
            $lastDue = $today->copy();
        }

        $rows = match ($mode) {
            'full' => [[__('Full payment'), $owed, $lastDue]],
            'deposit' => $this->deposit($owed, (float) ($opts['percent'] ?? 30), $today, $lastDue),
            'split' => $this->split($owed, max(2, min(12, (int) ($opts['parts'] ?? 3))), $today, $lastDue),
            default => throw new InvalidArgumentException("Unknown payment plan: {$mode}"),
        };

        $sort = (int) BookingPaymentPlan::where('booking_id', $booking->id)->max('sort_order');
        $made = [];
        foreach ($rows as [$label, $amount, $due]) {
            $made[] = BookingPaymentPlan::create([
                'vendor_id'  => $booking->vendor_id,   // explicit: no logged-in business is needed (API, jobs)
                'booking_id' => $booking->id,
                'label'      => $label,
                'amount'     => $amount,
                'due_date'   => $due->toDateString(),
                'status'     => 'pending',
                'sort_order' => ++$sort,
            ]);
        }

        app(ScheduleSync::class)->bookingToInvoice($booking);   // the booking's invoice, if it has one, is on the same schedule

        return $made;
    }

    private function deposit(float $owed, float $percent, Carbon $today, Carbon $lastDue): array
    {
        $percent = max(1, min(99, $percent));
        $deposit = round($owed * $percent / 100, 2);

        return [
            [__('Deposit (:p%)', ['p' => rtrim(rtrim(number_format($percent, 1), '0'), '.')]), $deposit, $today],
            [__('Balance'), round($owed - $deposit, 2), $lastDue],
        ];
    }

    private function split(float $owed, int $parts, Carbon $today, Carbon $lastDue): array
    {
        $each = round($owed / $parts, 2);
        $rows = [];
        $span = max(0, $today->diffInDays($lastDue));
        for ($i = 1; $i <= $parts; $i++) {
            $due = $i === 1 ? $today->copy() : ($i === $parts ? $lastDue->copy() : $today->copy()->addDays((int) round($span * ($i - 1) / ($parts - 1))));
            $amount = $i === $parts ? round($owed - $each * ($parts - 1), 2) : $each;
            $rows[] = [__('Instalment :i of :n', ['i' => $i, 'n' => $parts]), $amount, $due];
        }

        return $rows;
    }

    /**
     * Records money received. It goes towards the schedule (the row given, else the
     * earliest unpaid ones), moves `paid`, and the booking's status follows.
     */
    public function recordPayment(Booking $booking, float $amount, string $method = 'other', ?string $reference = null, ?string $note = null, ?int $planId = null, ?int $by = null): BookingLedger
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('A payment has to be more than nothing.');
        }

        return DB::transaction(function () use ($booking, $amount, $method, $reference, $note, $planId, $by) {
            // Lock the booking so two payments arriving together are counted one after the other, never both against the same old total.
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);

            // A double click or a retried request: the same payment a moment ago is the one already recorded.
            if ($dupe = $this->justRecorded($booking, 'payment', $amount, $method, $reference)) {
                return $dupe;
            }

            $entry = BookingLedger::create([
                'vendor_id'   => $booking->vendor_id,   // explicit: a background job or the API has no logged-in business to stamp it from
                'booking_id'  => $booking->id,
                'type'        => 'payment',
                'amount'      => round($amount, 2),
                'method'      => $method,
                'reference'   => $reference,
                'note'        => $note,
                'plan_id'     => $planId,
                'occurred_at' => now(),
                'created_by'  => $by,
            ]);
            \Modules\TourPay\Services\LedgerHooks::mirrorBookingLedger($entry, $booking);
            $booking->refresh();
            $this->settlePlan($booking, $amount, $planId);
            $this->announce($booking, 'booking.payment_received', $entry);

            return $entry;
        }, 3);
    }

    /**
     * Records money given back. It cannot be more than has been paid. Refunding
     * everything that was paid on a booking that has not been completed cancels it,
     * when [$cancel] says so.
     */
    public function recordRefund(Booking $booking, float $amount, string $method = 'other', ?string $reference = null, ?string $note = null, ?int $by = null, bool $cancel = false): BookingLedger
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('A refund has to be more than nothing.');
        }

        return DB::transaction(function () use ($booking, $amount, $method, $reference, $note, $by, $cancel) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);
            $paid = app(Ledger::class)->bookingPaid((int) $booking->id);
            if ($amount > $paid + 0.005) {
                throw new InvalidArgumentException('That is more than has been paid.');
            }
            if ($dupe = $this->justRecorded($booking, 'refund', $amount, $method, $reference)) {
                return $dupe;
            }

            $entry = BookingLedger::create([
                'vendor_id'   => $booking->vendor_id,
                'booking_id'  => $booking->id,
                'type'        => 'refund',
                'amount'      => round($amount, 2),
                'method'      => $method,
                'reference'   => $reference,
                'note'        => $note,
                'occurred_at' => now(),
                'created_by'  => $by,
            ]);
            \Modules\TourPay\Services\LedgerHooks::mirrorBookingLedger($entry, $booking);
            $booking->refresh();
            if ($cancel && (float) $booking->paid <= 0 && $booking->status !== Booking::COMPLETED) {
                $booking->status = Booking::CANCELLED;
                $booking->save();
            }
            $this->announce($booking, 'booking.refund_recorded', $entry);

            return $entry;
        }, 3);
    }

    /** The identical entry recorded in the last minute, if any (only when a reference makes it identifiable). */
    private function justRecorded(Booking $booking, string $type, float $amount, string $method, ?string $reference): ?BookingLedger
    {
        if ($reference === null || trim($reference) === '') {
            return null;
        }

        return BookingLedger::where('booking_id', $booking->id)->where('type', $type)->where('amount', round($amount, 2))->where('method', $method)->where('reference', $reference)
            ->where('occurred_at', '>=', now()->subMinute())->orderByDesc('id')->first();
    }

    private function announce(Booking $booking, string $type, BookingLedger $entry): void
    {
        WebhookEvents::emit((int) $booking->vendor_id, $type, [
            'code' => $booking->code, 'status' => $booking->status, 'amount' => (float) $entry->amount, 'method' => $entry->method, 'reference' => $entry->reference,
            'paid' => (float) $booking->paid, 'balance' => $this->balance($booking), 'currency' => $booking->currency ?: 'USD', 'occurred_at' => optional($entry->occurred_at)->toIso8601String(),
        ]);
    }

    /** Marks unpaid rows paid, in order, as far as [$amount] reaches (or just the one given). */
    public function settlePlan(Booking $booking, float $amount, ?int $planId): void
    {
        $rows = BookingPaymentPlan::where('booking_id', $booking->id)->where('status', 'pending')
            ->when($planId, fn ($q) => $q->where('id', $planId))
            ->orderBy('sort_order')->get();

        $left = round($amount, 2);
        foreach ($rows as $row) {
            if ($left + 0.005 < (float) $row->amount) {
                break;
            }
            $row->update(['status' => 'paid', 'paid_at' => now()]);
            $left = round($left - (float) $row->amount, 2);
        }
    }

    /** Lets the booking's status follow its paid amount (paid, part paid, back to unpaid after a refund). */
    public function follow(Booking $booking, bool $allowDowngrade = false): void
    {
        $total = (float) $booking->total;
        if ((float) $booking->paid >= $total && $total > 0) {
            $booking->markAsPaid();
            return;
        }
        if ((float) $booking->paid > 0) {
            if (!in_array($booking->status, [Booking::CONFIRMED, Booking::COMPLETED], true)) {
                $booking->status = Booking::PARTIAL_PAYMENT;
            }
        } elseif ($allowDowngrade && in_array($booking->status, [Booking::PAID, Booking::PARTIAL_PAYMENT], true)) {
            $booking->status = Booking::UNPAID;
        }
        $booking->save();
    }
}
