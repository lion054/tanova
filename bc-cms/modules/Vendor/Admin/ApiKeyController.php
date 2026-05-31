<?php

namespace Modules\Vendor\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AdminController;
use Modules\Vendor\Models\VendorApiKey;
use Modules\Vendor\Models\VendorApiUsage;

class ApiKeyController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('vendor.admin.api-keys.index'));
    }

    /** All vendors' API keys with usage summary */
    public function index(Request $request)
    {
        $this->checkPermission('vendor_manage_others');

        $query = VendorApiKey::with('vendor')
            ->withCount(['usage as monthly_requests' => function ($q) {
                $q->where('created_at', '>=', now()->startOfMonth());
            }])
            ->orderByDesc('last_used_at');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }
        if ($request->filled('active')) {
            $query->where('active', (bool) $request->input('active'));
        }

        $rows = $query->paginate(30);

        $totalRequests = DB::table('bc_vendor_api_usage')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $errorRate = DB::table('bc_vendor_api_usage')
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status_code', '>=', 400)
            ->count();

        return view('Vendor::admin.api-keys.index', [
            'rows'           => $rows,
            'total_requests' => $totalRequests,
            'error_rate'     => $totalRequests > 0 ? round($errorRate / $totalRequests * 100, 1) : 0,
            'breadcrumbs'    => [
                ['title' => 'Vendor', 'url' => ''],
                ['title' => 'API Keys', 'url' => route('vendor.admin.api-keys.index')],
            ],
            'page_title'     => 'Vendor API Keys',
        ]);
    }

    /** Per-vendor key detail + usage chart data */
    public function show(Request $request, int $id)
    {
        $this->checkPermission('vendor_manage_others');

        $key = VendorApiKey::with('vendor')->findOrFail($id);

        $usageByDay = DB::table('bc_vendor_api_usage')
            ->where('vendor_api_key_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, AVG(response_time_ms) as avg_ms, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topEndpoints = DB::table('bc_vendor_api_usage')
            ->where('vendor_api_key_id', $id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('endpoint, method, COUNT(*) as total, AVG(response_time_ms) as avg_ms')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('Vendor::admin.api-keys.show', [
            'key'           => $key,
            'usage_by_day'  => $usageByDay,
            'top_endpoints' => $topEndpoints,
            'monthly_count' => $key->monthlyUsageCount(),
            'breadcrumbs'   => [
                ['title' => 'API Keys', 'url' => route('vendor.admin.api-keys.index')],
                ['title' => $key->name, 'url' => ''],
            ],
            'page_title'    => 'API Key: ' . $key->name,
        ]);
    }

    /** Revoke a key from admin side */
    public function revoke(int $id)
    {
        $this->checkPermission('vendor_manage_others');

        VendorApiKey::findOrFail($id)->update(['active' => false]);

        return redirect()->route('vendor.admin.api-keys.index')
            ->with('success', 'API key revoked.');
    }
}
