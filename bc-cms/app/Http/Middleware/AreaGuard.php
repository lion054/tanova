<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Keeps each kind of account in its own part of the portal, and says why when it is turned away (config/areas.php holds the map).
 *
 *  - a vendor or customer opening a staff page goes back to their own home, with a note
 *  - staff without a vendor account opening a vendor page goes to the admin area, with a note
 *  - a customer opening a vendor page goes to their profile, with a note about "Become a vendor"
 * Every destination is one the person is allowed to open, so nobody is sent in a circle. Requests that expect JSON get a 403 instead.
 */
class AreaGuard
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);   // signing in is FrontendGuard's and the routes' job
        }
        $area = self::areaOf($request->path());
        if ($area === null || $area === 'account') {
            return $next($request);
        }

        $has = fn (string $permission) => $user->hasPermission($permission);
        if ($area === 'staff' && !$has(config('areas.staff.permission'))) {
            return $this->turnAway($request, ($has('dashboard_vendor_access') || \Modules\Vendor\Services\StaffAccess::membership($user)) ? '/user/dashboard' : '/user/profile',
                __('That area is for the platform team. You are in your own area.'));
        }
        if ($area === 'vendor' && !$has(config('areas.vendor.permission'))) {
            return $has('dashboard_access')
                ? $this->turnAway($request, '/admin', __('That page belongs to the vendor area, and your account has no vendor access. You are in the admin area.'))
                // Not /user/upgrade-vendor: that address is the action that files the request, so it must be a deliberate click, never a redirect.
                : $this->turnAway($request, '/user/profile', __('That page is for vendor accounts. Use "Become a vendor" in the menu to apply.'));
        }

        // Inside the staff area: sections that need one more permission than being staff.
        if ($area === 'staff' && ($need = self::permissionFor($request->path())) && !$has($need)) {
            return $this->turnAway($request, '/admin', __('Your role does not include that section. Ask an administrator if you need it.'));
        }

        return $next($request);
    }

    /** The extra permission a staff path needs, if config/areas.php names one. */
    public static function permissionFor(string $path): ?string
    {
        $path = trim($path, '/');
        foreach (config('areas.staff.permissions', []) as $pattern => $permission) {
            if (\Illuminate\Support\Str::is($pattern, $path)) {
                return $permission;
            }
        }

        return null;
    }

    /** 'staff', 'vendor', 'account', or null when the path is outside the portal areas (public pages, the API). */
    public static function areaOf(string $path): ?string
    {
        $path = trim($path, '/');
        $matches = fn (array $patterns) => collect($patterns)->contains(fn ($p) => \Illuminate\Support\Str::is($p, $path));

        if ($matches(config('areas.staff.open', []))) {
            return null;
        }
        foreach (['staff', 'vendor', 'account'] as $area) {
            if ($matches(config("areas.{$area}.patterns", []))) {
                return $area;
            }
        }

        return null;
    }

    private function turnAway(Request $request, string $to, string $why)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $why], 403);
        }

        return redirect($to)->with('area_notice', $why);
    }
}
