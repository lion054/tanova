<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Services\TourSeats;

/**
 * GET /api/v/tours/{id}/departures?from=2026-11-01&to=2026-11-30
 *
 * The days a tour can be booked, with how many seats each has left. A tour that runs
 * any day lists every day in the range (up to its usual capacity); a tour set to run
 * only on its own departures lists just those. A day with a departure that is closed
 * shows as closed. Seats left is null where the vendor set no limit.
 */
class VendorDepartureController extends Controller
{
    use ApiResponse;

    private const MAX_DAYS = 120;

    public function index(Request $request, int $id, TourSeats $seats): JsonResponse
    {
        $tour = Tour::forVendor()->find($id);
        if (!$tour) {
            return $this->notFound('Tour');
        }

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($data['from'] ?? today())->startOfDay();
        $from = $from->lt(today()) ? today() : $from;
        $to = Carbon::parse($data['to'] ?? $from->copy()->addDays(30))->startOfDay();
        if ($from->diffInDays($to) > self::MAX_DAYS) {
            $to = $from->copy()->addDays(self::MAX_DAYS);
        }

        $rows = DB::table('bc_tour_dates')->where('target_id', $tour->id)
            ->whereDate('end_date', '>=', $from->toDateString())->whereDate('start_date', '<=', $to->toDateString())
            ->orderBy('id')->get();
        $taken = $seats->takenByDay((int) $tour->id, $from->toDateString(), $to->toDateString());
        $usual = (int) $tour->max_people > 0 ? (int) $tour->max_people : null;
        $anyDay = (bool) $tour->default_state;

        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $iso = $day->toDateString();
            // The day's own row, else a range covering it.
            $row = $rows->first(fn ($r) => substr($r->start_date, 0, 10) === $iso && substr($r->end_date, 0, 10) === $iso)
                ?? $rows->first(fn ($r) => substr($r->start_date, 0, 10) <= $iso && substr($r->end_date, 0, 10) >= $iso);

            if ($row) {
                $open = (bool) $row->active;
                $capacity = (int) $row->max_guests > 0 ? (int) $row->max_guests : $usual;
                $price = $row->price !== null ? (float) $row->price : null;
            } elseif ($anyDay) {
                $open = true;
                $capacity = $usual;
                $price = null;
            } else {
                continue;   // runs only on its own departures, and this is not one
            }

            $gone = $taken[$iso] ?? 0;
            $left = $capacity === null ? null : max(0, $capacity - $gone);
            $status = !$open ? 'closed' : ($left === 0 ? 'full' : ($capacity !== null && $left <= max(2, (int) ceil($capacity * 0.2)) ? 'filling' : 'open'));

            $days[] = [
                'date'       => $iso,
                'status'     => $status,
                'capacity'   => $capacity,
                'seats_left' => $open ? $left : 0,
                'price'      => $price,
            ];
        }

        return $this->success([
            'tour_id'  => (int) $tour->id,
            'mode'     => $anyDay ? 'any_day' : 'listed_days',
            'capacity' => $usual,
            'days'     => $days,
        ]);
    }
}
