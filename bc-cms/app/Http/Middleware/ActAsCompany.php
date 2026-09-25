<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vendor\Services\StaffAccess;

/**
 * Company staff work as their company. For the business pages a staff member is allowed to open, the request runs as the company
 * (so every list, report, number and record is the company's, exactly as for the owner), while the real person is remembered as the
 * actor for the audit trail. Their own account pages (profile, password, two-factor) and everything else stay theirs.
 *
 * Deny by default: a page in no list, an owner-only page, or a module the owner did not tick, is refused with a reason.
 * Someone who is not staff (an owner, a platform admin, a customer) passes through untouched.
 */
class ActAsCompany
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $team = $user ? StaffAccess::membership($user) : null;
        if (!$team) {
            return $next($request);
        }

        $path = $request->path();
        $class = StaffAccess::classify($path);
        $isPortalPath = preg_match('#^(user|vendor)(/|$)#', trim($path, '/')) === 1;

        // Staff never enter the platform's admin area (their account has no platform permission), and other pages are not theirs to judge here.
        if (!$isPortalPath) {
            return $next($request);
        }
        if (!StaffAccess::allows((array) $team->permissions, $path)) {
            $why = $class === null || $class === 'owner_only'
                ? __('That page is for the company owner. You are working in :c.', ['c' => $this->companyName($team)])
                : __('Your account does not include that part of :c. Ask the owner if you need it.', ['c' => $this->companyName($team)]);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $why], 403);
            }

            return redirect('/user/dashboard')->with('area_notice', $why);
        }

        if ($class !== 'personal') {
            $owner = $team->vendor;
            if (!$owner) {
                abort(403);
            }
            $request->attributes->set('staff_actor', $user);
            $request->attributes->set('staff_team', $team);
            Auth::setUser($owner);   // from here the request is the company's; the actor is remembered above
        }

        return $next($request);
    }

    private function companyName($team): string
    {
        $v = $team->vendor;

        return (string) ($v->business_name ?: $v->name ?: __('your company'));
    }
}
