<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vendor\Services\PlanLimits;

/**
 * The portal's "add a listing" screens follow the same plan rules as the API (see PlanLimits): a business without an active plan, or past
 * what its plan includes, is sent to its subscription page and told why. Only creating is stopped: editing what already exists, bookings,
 * invoices and everything else stay open.
 */
class EnforcePlanOnCreate
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        // New listing: the create form (GET .../create) or saving with id 0 (POST .../store/0).
        if ($user && $user->hasPermission('dashboard_vendor_access') && preg_match('#^user/(' . implode('|', PlanLimits::TYPES) . ')/(create|store/0)$#', $request->path(), $m)) {
            if ($breach = PlanLimits::check($user, $m[1])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $breach['message'], 'code' => $breach['code']], $breach['status']);
                }

                return redirect('/vendor/subscription')->with('area_notice', $breach['message'] . ' ' . __('Contact the platform team to change your plan.'));
            }
        }

        return $next($request);
    }
}
