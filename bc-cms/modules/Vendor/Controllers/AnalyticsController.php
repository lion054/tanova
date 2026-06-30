<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingCheckin;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\VendorWaitlist;

/**
 * Phase 4 — Per-vendor analytics dashboard. Every query is filtered by the current
 * vendor (bookings explicitly; the operations models via BelongsToVendor), so no
 * vendor ever sees another's numbers.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = resolve_current_vendor_id();

        // Cache the per-vendor aggregation for 5 minutes — dashboards are read-heavy
        // and the underlying numbers don't need to be second-fresh. Key is per vendor
        // so no cross-tenant bleed. ?refresh=1 busts it on demand.
        if ($request->boolean('refresh')) {
            Cache::forget("vendor_analytics_{$vendorId}");
        }

        $payload = Cache::remember("vendor_analytics_{$vendorId}", now()->addMinutes(5),
            fn () => $this->compute($vendorId));

        return view('vendor.analytics.index', $payload + ['page_title' => __('Analytics')]);
    }

    private function compute(int $vendorId): array
    {
        $accepted = fn () => Booking::where('vendor_id', $vendorId)
            ->whereNotIn('status', Booking::$notAcceptedStatus);

        // Revenue trend — last 12 months.
        $since = now()->startOfMonth()->subMonths(11);
        $rows  = (clone $accepted())
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as bookings')
            )
            ->groupBy('ym')->orderBy('ym')->get()->keyBy('ym');

        $months = [];
        $revenue = [];
        $bookingsPerMonth = [];
        for ($i = 0; $i < 12; $i++) {
            $key = $since->copy()->addMonths($i)->format('Y-m');
            $months[] = $key;
            $revenue[] = round((float) ($rows[$key]->revenue ?? 0), 2);
            $bookingsPerMonth[] = (int) ($rows[$key]->bookings ?? 0);
        }

        // Status + source breakdowns.
        $statusBreakdown = Booking::where('vendor_id', $vendorId)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')->pluck('c', 'status')->toArray();

        $sourceBreakdown = (clone $accepted())
            ->select('object_model', DB::raw('COUNT(*) as c'))
            ->groupBy('object_model')->pluck('c', 'object_model')->toArray();

        // Marketplace (MCP) funnel — TanovaTrip is BelongsToVendor (auto-scoped).
        $mcpSessions = \Pro\Tanova\Models\TanovaTrip::where('source', 'mcp')->count();
        $mcpBooked   = \Pro\Tanova\Models\TanovaTrip::where('source', 'mcp')
            ->where('status', \Pro\Tanova\Models\TanovaTrip::STATUS_BOOKED)->count();

        return [
            'cards' => [
                'revenue_12m'  => array_sum($revenue),
                'bookings'     => (clone $accepted())->count(),
                'loyalty'      => LoyaltyAccount::count(),
                'no_shows'     => BookingCheckin::where('status', BookingCheckin::STATUS_NO_SHOW)->count(),
                'waitlist'     => VendorWaitlist::where('status', VendorWaitlist::STATUS_WAITING)->count(),
                'mcp_sessions' => $mcpSessions,
                'mcp_bookings' => $mcpBooked,
                'mcp_conversion' => $mcpSessions ? round($mcpBooked / $mcpSessions * 100, 1) : 0,
            ],
            'chart' => [
                'months'           => $months,
                'revenue'          => $revenue,
                'bookingsPerMonth' => $bookingsPerMonth,
                'status'           => $statusBreakdown,
                'source'           => $sourceBreakdown,
            ],
        ];
    }
}
