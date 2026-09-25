<?php

namespace Modules\Vendor\Services;

use App\User;
use Illuminate\Support\Str;
use Modules\Vendor\Models\VendorTeam;

/** Who is company staff, which company, and what they may open (config/staff_access.php). */
class StaffAccess
{
    /** The active team record of a staff member, or null (owners, platform admins and customers have none). */
    public static function membership(?User $user): ?VendorTeam
    {
        if (!$user) {
            return null;
        }
        // Looked up once per request (and per person): it is asked by the middleware, the sidebar and the header.
        static $memo = [];
        $key = $user->id . ':' . spl_object_id(request());

        return array_key_exists($key, $memo) ? $memo[$key] : $memo[$key] = VendorTeam::where('member_id', $user->id)->where('status', VendorTeam::STATUS_PUBLISH)->first();
    }

    /** 'always', 'personal', 'owner_only', a module key, or null when the path is in none of the lists. */
    public static function classify(string $path): ?string
    {
        $path = trim($path, '/');
        $is = fn (array $patterns) => collect($patterns)->contains(fn ($p) => Str::is($p, $path));
        if ($is(config('staff_access.always', []))) { return 'always'; }
        if ($is(config('staff_access.personal', []))) { return 'personal'; }
        if ($is(config('staff_access.owner_only', []))) { return 'owner_only'; }
        foreach (config('staff_access.modules', []) as $key => $m) {
            if ($is($m['patterns'])) { return $key; }
        }

        return null;
    }

    /** May a staff member with these modules open this path? Deny by default. */
    public static function allows(array $modules, string $path): bool
    {
        $c = self::classify($path);

        return in_array($c, ['always', 'personal'], true) || ($c !== null && $c !== 'owner_only' && in_array($c, $modules, true));
    }

    /** The modules an owner can hand out: key => label. */
    public static function modules(): array
    {
        return collect(config('staff_access.modules'))->map(fn ($m) => $m['label'])->all();
    }
}
