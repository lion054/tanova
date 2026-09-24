<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\VendorShelfPin;

/**
 * The Trending and Bestsellers shelves, ranked from what actually happened.
 *
 * A booking counts once it is not cancelled or waiting on payment. Bestsellers rank by
 * all-time bookings. Trending ranks by recent bookings, the last 7 days counting
 * double the rest of the last 30, so a tour that is picking up climbs past one that
 * sold well long ago. Ties go to the better review score. A tour with no bookings is
 * not ranked. The vendor's own pins go first, in the order they were pinned, whatever
 * the numbers say. Only published tours appear.
 */
class Shelves
{
    public const RECENT_DAYS = 30;
    public const HOT_DAYS = 7;

    /** @return \Illuminate\Support\Collection<int,array{tour:Tour,score:float,sales_total:int,sales_30d:int,sales_7d:int,pinned:bool,reason:string}> */
    public function shelf(string $shelf, int $vendorId, int $limit = 10)
    {
        $sales = $this->sales($vendorId);
        $pins = VendorShelfPin::withoutVendorScope()->where('vendor_id', $vendorId)->where('shelf', $shelf)->where('object_model', 'tour')
            ->orderBy('id')->pluck('id', 'object_id');

        $tours = Tour::where('author_id', $vendorId)->where('status', 'publish')
            ->whereIn('id', $sales->keys()->merge($pins->keys())->unique()->values())->get()->keyBy('id');

        $rows = $tours->map(function (Tour $t) use ($sales, $pins, $shelf) {
            $s = $sales->get($t->id, ['total' => 0, 'd30' => 0, 'd7' => 0]);
            $score = $shelf === 'bestseller' ? (float) $s['total'] : (float) ($s['d30'] + $s['d7']);

            return [
                'tour'        => $t,
                'score'       => $score,
                'sales_total' => (int) $s['total'],
                'sales_30d'   => (int) $s['d30'],
                'sales_7d'    => (int) $s['d7'],
                'pinned'      => $pins->has($t->id),
                'pin_order'   => $pins->get($t->id, PHP_INT_MAX),
                'reason'      => $this->reason($shelf, $pins->has($t->id), $s),
            ];
        })->filter(fn ($r) => $r['pinned'] || $r['score'] > 0);

        return $rows->sort(function ($a, $b) {
            return [$a['pin_order'], $b['score'], (float) $b['tour']->review_score, $a['tour']->id]
               <=> [$b['pin_order'], $a['score'], (float) $a['tour']->review_score, $b['tour']->id];
        })->take($limit)->values();
    }

    public function pin(string $shelf, int $vendorId, int $tourId, bool $on): void
    {
        $key = ['vendor_id' => $vendorId, 'shelf' => $shelf, 'object_model' => 'tour', 'object_id' => $tourId];
        if ($on) {
            VendorShelfPin::withoutVendorScope()->firstOrCreate($key);
        } else {
            VendorShelfPin::withoutVendorScope()->where($key)->delete();
        }
    }

    /** @return \Illuminate\Support\Collection<int,array{total:int,d30:int,d7:int}> keyed by tour id */
    private function sales(int $vendorId)
    {
        $since30 = now()->subDays(self::RECENT_DAYS);
        $since7 = now()->subDays(self::HOT_DAYS);

        return DB::table('bc_bookings')
            ->selectRaw('object_id, COUNT(*) as total, SUM(created_at >= ?) as d30, SUM(created_at >= ?) as d7', [$since30, $since7])
            ->where('vendor_id', $vendorId)->where('object_model', 'tour')
            ->whereNotIn('status', Booking::$notAcceptedStatus) // not draft, unpaid or cancelled->whereNull('deleted_at')
            ->groupBy('object_id')->get()
            ->mapWithKeys(fn ($r) => [(int) $r->object_id => ['total' => (int) $r->total, 'd30' => (int) $r->d30, 'd7' => (int) $r->d7]]);
    }

    private function reason(string $shelf, bool $pinned, array $s): string
    {
        if ($pinned) {
            return __('Picked by us');
        }
        if ($shelf === 'bestseller') {
            return trans_choice(':n trip booked|:n trips booked', $s['total'], ['n' => $s['total']]);
        }

        return $s['d7'] > 0
            ? trans_choice(':n booking this week|:n bookings this week', $s['d7'], ['n' => $s['d7']])
            : trans_choice(':n booking this month|:n bookings this month', $s['d30'], ['n' => $s['d30']]);
    }
}
