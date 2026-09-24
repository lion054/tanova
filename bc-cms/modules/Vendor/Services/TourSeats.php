<?php

namespace Modules\Vendor\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourDate;

/**
 * How many seats a tour has on a day, how many are gone, and how many are left.
 *
 * Capacity is the departure's own (a bc_tour_dates row covering the day, with a
 * capacity) or else the tour's usual capacity (max_people). Neither means the
 * vendor has set no limit: [remaining] is then null, "no limit", and callers decide
 * what that means for them.
 *
 * Seats are gone when a booking is paid, in progress or confirmed. A booking that
 * has not been paid yet holds its seats for a short while (the hold), so two people
 * cannot both be told the last seat is theirs; once the hold runs out its seats are
 * free again without anyone having to cancel it. A cancelled booking holds nothing.
 */
class TourSeats
{
    public const DEFAULT_HOLD_MINUTES = 30;

    public function holdMinutes(): int
    {
        $set = (int) setting_item('seat_hold_minutes', self::DEFAULT_HOLD_MINUTES);

        return $set > 0 ? $set : self::DEFAULT_HOLD_MINUTES;
    }

    /** The departure row for [$date], the day's own row first, else any range covering it. */
    public function departure(int $tourId, string $date): ?TourDate
    {
        $day = Carbon::parse($date)->toDateString();

        return TourDate::query()
            ->where('target_id', $tourId)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->orderByRaw('(DATE(start_date) = ? AND DATE(end_date) = ?) DESC', [$day, $day])
            ->orderByDesc('id')
            ->first();
    }

    /** Seats on the day, or null for no limit. */
    public function capacity(Tour $tour, string $date): ?int
    {
        $row = $this->departure((int) $tour->id, $date);
        if ($row && $row->active && (int) $row->max_guests > 0) {
            return (int) $row->max_guests;
        }

        return (int) $tour->max_people > 0 ? (int) $tour->max_people : null;
    }

    /** Guests booked on the day: paid, in progress or confirmed, plus unpaid ones still holding their seats. */
    public function taken(int $tourId, string $date, ?int $exceptBookingId = null): int
    {
        return (int) $this->takenQuery($tourId, $date, $exceptBookingId)->sum('total_guests');
    }

    /** Only the seats held by unpaid bookings that have not yet run out. */
    public function held(int $tourId, string $date, ?int $exceptBookingId = null): int
    {
        return (int) $this->takenQuery($tourId, $date, $exceptBookingId)
            ->whereIn('status', Booking::$notAcceptedStatus)->sum('total_guests');
    }

    /** Seats left on the day (never below zero), or null for no limit. */
    public function remaining(Tour $tour, string $date, ?int $exceptBookingId = null): ?int
    {
        $capacity = $this->capacity($tour, $date);
        if ($capacity === null) {
            return null;
        }

        return max(0, $capacity - $this->taken((int) $tour->id, $date, $exceptBookingId));
    }

    /**
     * Guests booked on each day from [$from] to [$to] (inclusive), by the same rules
     * as [taken], in one query. Days with nobody booked are absent.
     *
     * @return array<string,int> date (Y-m-d) => guests
     */
    public function takenByDay(int $tourId, string $from, string $to): array
    {
        $holdFrom = now()->subMinutes($this->holdMinutes());
        $holding = array_values(array_diff(Booking::$notAcceptedStatus, ['cancelled']));

        return DB::table('bc_bookings')
            ->selectRaw('DATE(start_date) as day, SUM(total_guests) as guests')
            ->where('object_model', 'tour')->where('object_id', $tourId)
            ->whereDate('start_date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('start_date', '<=', Carbon::parse($to)->toDateString())
            ->whereNull('deleted_at')
            ->where(function ($q) use ($holding, $holdFrom) {
                $q->whereNotIn('status', array_merge(['cancelled'], $holding))
                    ->orWhere(fn ($h) => $h->whereIn('status', $holding)->where('updated_at', '>=', $holdFrom));
            })
            ->groupBy('day')->pluck('guests', 'day')
            ->map(fn ($g) => (int) $g)->all();
    }

    private function takenQuery(int $tourId, string $date, ?int $exceptBookingId)
    {
        $day = Carbon::parse($date)->toDateString();
        $holdFrom = now()->subMinutes($this->holdMinutes());
        // Statuses that hold nothing: cancelled, and draft/unpaid are handled by the hold below.
        $notCounted = ['cancelled'];
        $holding = array_values(array_diff(Booking::$notAcceptedStatus, $notCounted));

        return DB::table('bc_bookings')
            ->where('object_model', 'tour')
            ->where('object_id', $tourId)
            ->whereDate('start_date', $day)
            ->whereNull('deleted_at')
            ->when($exceptBookingId, fn ($q) => $q->where('id', '!=', $exceptBookingId))
            ->where(function ($q) use ($holding, $notCounted, $holdFrom) {
                // accepted: anything that is not cancelled, draft or unpaid
                $q->whereNotIn('status', array_merge($notCounted, $holding))
                    // held: unpaid or draft, still inside the hold
                    ->orWhere(function ($h) use ($holding, $holdFrom) {
                        $h->whereIn('status', $holding)->where('updated_at', '>=', $holdFrom);
                    });
            });
    }
}
