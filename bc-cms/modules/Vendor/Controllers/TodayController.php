<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingCheckin;
use Modules\Vendor\Models\VendorWaitlist;

/**
 * Phase 2 — "Today" daily operations snapshot for the current vendor:
 * arrivals, departures, in-house guests and quick counts. Read-only dashboard.
 */
class TodayController extends Controller
{
    public function index(Request $request)
    {
        $date     = $request->input('date', now()->toDateString());
        $vendorId = resolve_current_vendor_id();

        $base = fn () => Booking::where('vendor_id', $vendorId)
            ->whereNotIn('status', Booking::$notAcceptedStatus);

        $arrivals   = (clone $base())->whereDate('start_date', $date)->orderBy('start_date')->get();
        $departures = (clone $base())->whereDate('end_date', $date)->orderBy('end_date')->get();
        $inHouse    = (clone $base())
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $checkins = BookingCheckin::whereIn('booking_id', $arrivals->pluck('id'))
            ->get()
            ->keyBy('booking_id');

        return view('vendor.today.index', [
            'date'       => $date,
            'arrivals'   => $arrivals,
            'departures' => $departures,
            'inHouse'    => $inHouse,
            'checkins'   => $checkins,
            'stats'      => [
                'arrivals'   => $arrivals->count(),
                'departures' => $departures->count(),
                'in_house'   => $inHouse->count(),
                'waiting'    => VendorWaitlist::where('status', VendorWaitlist::STATUS_WAITING)->count(),
            ],
            'page_title' => __('Today'),
        ]);
    }
}
