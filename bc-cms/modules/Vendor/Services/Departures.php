<?php

namespace Modules\Vendor\Services;

use App\Support\ListQuery;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Departures: the days a tour runs, with seats. The board (all tours, with seat counts), scheduling a stretch of
 * days, changing or removing one, and a tour's usual capacity. The portal's Departures screen and the API share it.
 */
class Departures
{
    public const MAX_ROWS = 2000;

    public function __construct(private TourSeats $seats) {}

    /**
     * Departures from [$from] to [$to], with what is taken and left on each.
     *
     * @return Collection<int,object> {id,tour_id,title,start,end,capacity,taken,held,left,price,base_price,active,status}
     */
    public function board(int $vendorId, Carbon $from, Carbon $to, string $q = '', int $tourId = 0, string $state = ''): Collection
    {
        $rows = DB::table('bc_tour_dates as d')
            ->join('bc_tours as t', 't.id', '=', 'd.target_id')
            ->where('t.author_id', $vendorId)->whereNull('t.deleted_at')
            ->whereDate('d.end_date', '>=', $from->toDateString())->whereDate('d.start_date', '<=', $to->toDateString())
            ->when($q !== '', function ($w) use ($q) {
                foreach (ListQuery::words($q) as $word) {
                    $w->where('t.title', 'like', ListQuery::like($word));
                }
            })
            ->when($tourId > 0, fn ($w) => $w->where('d.target_id', $tourId))
            ->orderBy('d.start_date')->orderBy('t.title')->limit(1500)
            ->get(['d.id', 'd.target_id', 'd.start_date', 'd.end_date', 'd.price', 'd.max_guests', 'd.active', 't.title', 't.max_people', 't.price as base_price']);

        // Seats taken: one query per tour, not two per departure.
        $takenByTour = [];
        foreach ($rows->groupBy('target_id') as $tid => $group) {
            $takenByTour[$tid] = $this->seats->takenByDay((int) $tid, $group->min(fn ($r) => substr($r->start_date, 0, 10)), $group->max(fn ($r) => substr($r->start_date, 0, 10)));
        }

        return $rows->map(function ($r) use ($takenByTour) {
            $day = Carbon::parse($r->start_date)->toDateString();
            $capacity = (int) $r->max_guests > 0 ? (int) $r->max_guests : ((int) $r->max_people > 0 ? (int) $r->max_people : null);
            $taken = (int) ($takenByTour[$r->target_id][$day] ?? 0);
            $left = $capacity === null ? null : max(0, $capacity - $taken);

            return (object) [
                'id' => $r->id, 'tour_id' => $r->target_id, 'title' => trim((string) $r->title),
                'start' => Carbon::parse($r->start_date), 'end' => Carbon::parse($r->end_date),
                'capacity' => $capacity, 'taken' => $taken, 'held' => 0, 'left' => $left,
                'price' => $r->price, 'base_price' => $r->base_price, 'active' => (bool) $r->active,
                'status' => $this->status((bool) $r->active, $capacity, $left),
            ];
        })->when($state !== '', fn ($c) => $c->where('status', $state))->values();
    }

    /** Fill in seats held by unpaid bookings (a query each, so only for the rows on show). */
    public function withHeld(Collection $page): Collection
    {
        return $page->each(fn ($d) => $d->held = $this->seats->held((int) $d->tour_id, $d->start->toDateString()));
    }

    public function status(bool $active, ?int $capacity, ?int $left): string
    {
        return !$active ? 'closed' : ($left === 0 ? 'full' : ($capacity !== null && $left <= max(2, (int) ceil($capacity * 0.2)) ? 'filling' : 'open'));
    }

    /**
     * Adds departures for one or more of the vendor's tours: one day, or a stretch on chosen weekdays.
     * A day that already has a departure is updated, not doubled.
     *
     * @param int[] $tourIds
     * @param int[] $weekdays 0 (Sunday) to 6
     * @return array{made:int,changed:int,first:string,last:string}
     * @throws DepartureRuleException
     */
    public function schedule(int $vendorId, array $tourIds, Carbon $from, ?Carbon $to, array $weekdays, int $capacity, ?float $price, ?int $by = null): array
    {
        $owned = DB::table('bc_tours')->where('author_id', $vendorId)->whereNull('deleted_at')->whereIn('id', $tourIds)->pluck('id')->all();
        if (!$owned) {
            throw new DepartureRuleException('no_tours', __('Pick at least one of your tours.'), 404);
        }
        $from = $from->copy()->startOfDay();
        $to = $to ? $to->copy()->startOfDay() : $from->copy();
        $days = collect(CarbonPeriod::create($from, $to))
            ->filter(fn (Carbon $d) => !$weekdays || in_array($d->dayOfWeek, $weekdays))
            ->map(fn (Carbon $d) => $d->copy())->values();
        if ($days->isEmpty()) {
            throw new DepartureRuleException('no_days', __('No days fall on the weekdays you chose.'));
        }
        if ($days->count() * count($owned) > self::MAX_ROWS) {
            throw new DepartureRuleException('too_many', __('That is more than :n departures at once. Use a shorter stretch.', ['n' => self::MAX_ROWS]));
        }

        $made = $changed = 0;
        DB::transaction(function () use ($owned, $days, $capacity, $price, $by, &$made, &$changed) {
            foreach ($owned as $tourId) {
                foreach ($days as $day) {
                    $stamp = $day->format('Y-m-d 00:00:00');
                    $row = DB::table('bc_tour_dates')->where('target_id', $tourId)->where('start_date', $stamp)->where('end_date', $stamp)->first();
                    $values = ['max_guests' => $capacity, 'price' => $price, 'active' => 1, 'updated_at' => now(), 'update_user' => $by];
                    if ($row) {
                        DB::table('bc_tour_dates')->where('id', $row->id)->update($values);
                        $changed++;
                    } else {
                        DB::table('bc_tour_dates')->insert($values + ['target_id' => $tourId, 'start_date' => $stamp, 'end_date' => $stamp, 'create_user' => $by, 'created_at' => now()]);
                        $made++;
                    }
                }
            }
        });

        return ['made' => $made, 'changed' => $changed, 'first' => $days->first()->toDateString(), 'last' => $days->last()->toDateString()];
    }

    /** The vendor's own departure row, or null. */
    public function find(int $vendorId, int $id): ?object
    {
        return DB::table('bc_tour_dates as d')->join('bc_tours as t', 't.id', '=', 'd.target_id')
            ->where('d.id', $id)->where('t.author_id', $vendorId)->first(['d.*']);
    }

    /** @throws DepartureRuleException never fewer seats than people already have */
    public function change(object $row, int $capacity, ?float $price, bool $active, ?int $by = null): void
    {
        $taken = $this->seats->taken((int) $row->target_id, Carbon::parse($row->start_date)->toDateString());
        if ($capacity < $taken) {
            throw new DepartureRuleException('below_booked', __(':n people have already booked that day, so it cannot hold fewer than :n.', ['n' => $taken]));
        }
        DB::table('bc_tour_dates')->where('id', $row->id)->update(['max_guests' => $capacity, 'price' => $price, 'active' => $active ? 1 : 0, 'updated_at' => now(), 'update_user' => $by]);
    }

    /** @throws DepartureRuleException a day people have booked is closed, not deleted */
    public function remove(object $row): void
    {
        if ($this->seats->taken((int) $row->target_id, Carbon::parse($row->start_date)->toDateString()) > 0) {
            throw new DepartureRuleException('has_bookings', __('People have booked that day. Close it instead of deleting it.'));
        }
        DB::table('bc_tour_dates')->where('id', $row->id)->delete();
    }

    /** A tour's usual capacity (null: no limit) and whether it runs any day or only on its departures. */
    public function usualCapacity(int $vendorId, int $tourId, ?int $capacity, bool $onlyListed): bool
    {
        $n = DB::table('bc_tours')->where('id', $tourId)->where('author_id', $vendorId)->update([
            'max_people' => $capacity === null || $capacity === 0 ? null : $capacity, 'default_state' => $onlyListed ? 0 : 1, 'updated_at' => now(),
        ]);

        return $n > 0;
    }
}
