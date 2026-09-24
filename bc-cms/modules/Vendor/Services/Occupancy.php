<?php

namespace Modules\Vendor\Services;

use Carbon\Carbon;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourDate;

/**
 * How full the vendor's tours are over the coming days: seats sold against seats
 * available, on the days that are actually running (a day with bookings, or one
 * listed as a departure), for tours that have a capacity. A day nobody has booked
 * or listed is not counted, so it does not water the figure down. Tours with no
 * capacity set have nothing to be full of, and are left out.
 */
class Occupancy
{
    public function __construct(private TourSeats $seats) {}

    /**
     * @return array{sold:int,capacity:int,percent:?int,tours:array<int,array{title:string,sold:int,capacity:int,percent:int}>}
     */
    public function forVendor(int $vendorId, int $days = 30, ?Carbon $from = null): array
    {
        $from = ($from ?: now())->copy()->startOfDay();
        $to = $from->copy()->addDays($days - 1);
        $fromS = $from->toDateString();
        $toS = $to->toDateString();

        $tours = Tour::where('author_id', $vendorId)->where('status', 'publish')->get();
        $listed = TourDate::whereIn('target_id', $tours->pluck('id'))->where('active', true)
            ->whereDate('end_date', '>=', $fromS)->whereDate('start_date', '<=', $toS)->get()->groupBy('target_id');

        $out = [];
        $sold = $capacity = 0;
        foreach ($tours as $tour) {
            $taken = $this->seats->takenByDay((int) $tour->id, $fromS, $toS);

            $running = array_keys($taken);
            foreach ($listed->get($tour->id, collect()) as $row) {
                for ($d = Carbon::parse($row->start_date)->max($from)->startOfDay(); $d->lte(Carbon::parse($row->end_date)->min($to)); $d->addDay()) {
                    $running[] = $d->toDateString();
                }
            }

            $tSold = $tCap = 0;
            foreach (array_unique($running) as $day) {
                $cap = $this->seats->capacity($tour, $day);
                if ($cap === null) {
                    continue; // no limit set: nothing to measure against
                }
                $tCap += $cap;
                $tSold += min($cap, $taken[$day] ?? 0);
            }
            if ($tCap > 0) {
                $out[] = ['title' => (string) $tour->title, 'sold' => $tSold, 'capacity' => $tCap, 'percent' => (int) round($tSold / $tCap * 100)];
                $sold += $tSold;
                $capacity += $tCap;
            }
        }
        usort($out, fn ($a, $b) => [$b['percent'], $b['sold']] <=> [$a['percent'], $a['sold']]);

        return ['sold' => $sold, 'capacity' => $capacity, 'percent' => $capacity ? (int) round($sold / $capacity * 100) : null, 'tours' => array_slice($out, 0, 8)];
    }
}
