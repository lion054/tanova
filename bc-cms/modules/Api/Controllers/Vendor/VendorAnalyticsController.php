<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\VendorApiKey;

class VendorAnalyticsController extends Controller
{
    /**
     * High-level summary: bookings, revenue, services counts.
     */
    public function summary(): JsonResponse
    {
        $vendorId     = VendorContext::id();
        $thisMonth    = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd   = now()->subMonth()->endOfMonth();

        // Single query — all six metrics in one DB round-trip
        $row = DB::table('bc_bookings')
            ->where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*)                                                                      AS total_bookings,
                SUM(CASE WHEN created_at >= ?                                  THEN 1 ELSE 0 END) AS bookings_this_month,
                SUM(CASE WHEN status IN ('draft','pending')                     THEN 1 ELSE 0 END) AS pending_bookings,
                SUM(CASE WHEN status = 'completed'                              THEN total ELSE 0 END) AS total_revenue,
                SUM(CASE WHEN status = 'completed' AND created_at >= ?         THEN total ELSE 0 END) AS revenue_this_month,
                SUM(CASE WHEN status = 'completed' AND created_at BETWEEN ? AND ? THEN total ELSE 0 END) AS revenue_last_month
            ", [$thisMonth, $thisMonth, $lastMonthStart, $lastMonthEnd])
            ->first();

        return response()->json([
            'data' => [
                'total_bookings'      => (int)   ($row->total_bookings    ?? 0),
                'bookings_this_month' => (int)   ($row->bookings_this_month ?? 0),
                'pending_bookings'    => (int)   ($row->pending_bookings  ?? 0),
                'total_revenue'       => round((float) ($row->total_revenue      ?? 0), 2),
                'revenue_this_month'  => round((float) ($row->revenue_this_month ?? 0), 2),
                'revenue_last_month'  => round((float) ($row->revenue_last_month ?? 0), 2),
            ],
        ]);
    }

    /**
     * Revenue breakdown by day/month for a given period.
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7d,30d,90d,1y',
        ]);

        $days = match ($request->input('period', '30d')) {
            '7d'  => 7,
            '90d' => 90,
            '1y'  => 365,
            default => 30,
        };

        $grouped = Booking::forVendor()
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as bookings'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $grouped]);
    }

    /**
     * API usage breakdown by endpoint for the current month.
     */
    public function apiUsage(Request $request): JsonResponse
    {
        $keys = VendorApiKey::where('vendor_id', VendorContext::id())->pluck('id');

        $usage = DB::table('bc_vendor_api_usage')
            ->whereIn('vendor_api_key_id', $keys)
            ->where('created_at', '>=', now()->startOfMonth())
            ->select(
                'endpoint',
                'method',
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('AVG(response_time_ms) as avg_ms'),
                DB::raw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors'),
            )
            ->groupBy('endpoint', 'method')
            ->orderByDesc('total_requests')
            ->limit(50)
            ->get();

        return response()->json(['data' => $usage]);
    }
}
