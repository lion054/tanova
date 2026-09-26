<?php

namespace Modules\Vendor\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorPlan;

/**
 * Which Tanova OS a company operates, and how many its plan lets it operate. The plan says how many (os_limit, 0 = all); the company
 * chooses which; an extra OS is bought on top (or granted by the platform team) and marked is_addon.
 *
 * What a company sees and can create follows from this: the sidebar, the create screens and the API all ask effective().
 */
class CompanyOs
{
    /** @return array<string,array> every OS, keyed */
    public static function all(): array
    {
        return config('os_modules.os', []);
    }

    /** The OS a listing type belongs to (e.g. 'hotel' → 'stay'), or null for a type no OS covers. */
    public static function forType(string $type): ?string
    {
        foreach (self::all() as $key => $os) {
            if (in_array($type, $os['types'] ?? [], true)) {
                return $key;
            }
        }

        return null;
    }

    public static function plan(User $vendor): ?VendorPlan
    {
        return $vendor->vendor_plan_id ? VendorPlan::find($vendor->vendor_plan_id) : null;
    }

    /** @return string[] the OS keys stored for the company (base choice plus extras) */
    public static function chosen(User $vendor): array
    {
        return DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->orderBy('id')->pluck('os_key')->all();
    }

    public static function extras(User $vendor): array
    {
        return DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->where('is_addon', 1)->pluck('os_key')->all();
    }

    /** How many OS the company may choose as part of its plan (not counting extras); null = all of them. */
    public static function baseLimit(User $vendor): ?int
    {
        $plan = self::plan($vendor);
        if (!$plan || $plan->coversAllOs()) {
            return null;
        }

        return (int) $plan->os_limit;
    }

    /** @return string[] the OS the company can use now */
    public static function effective(User $vendor): array
    {
        if (!is_enable_plan()) {
            return array_keys(self::all());
        }
        $plan = self::plan($vendor);
        if (!$plan) {
            return [];
        }
        if ($plan->coversAllOs()) {
            return array_keys(self::all());
        }

        return array_values(array_intersect(array_keys(self::all()), self::chosen($vendor)));
    }

    public static function has(User $vendor, string $os): bool
    {
        return in_array($os, self::effective($vendor), true);
    }

    /** Whether the company may create this listing type: its OS is one it operates. Types no OS covers are open. */
    public static function allowsType(User $vendor, string $type): bool
    {
        $os = self::forType($type);

        return $os === null || self::has($vendor, $os);
    }

    /**
     * Sets the OS a company chose with its plan (the base choice). Extras are kept as they are.
     * @param  string[] $keys
     * @return string|null a message when the choice is not allowed, null when saved
     */
    public static function choose(User $vendor, array $keys, ?VendorPlan $plan = null): ?string
    {
        $plan ??= self::plan($vendor);
        $keys = array_values(array_unique(array_filter($keys)));
        foreach ($keys as $k) {
            if (!isset(self::all()[$k])) {
                return __('Unknown OS: :k', ['k' => $k]);
            }
        }
        if (!$plan) {
            return __('Choose a plan first.');
        }
        $extras = self::extras($vendor);
        $base = array_values(array_diff($keys, $extras));
        if (!$plan->coversAllOs()) {
            if (count($base) < 1) {
                return __('Choose at least one OS.');
            }
            if (count($base) > (int) $plan->os_limit) {
                return __(':plan covers :n OS. Pick fewer, or add extra OS.', ['plan' => $plan->name, 'n' => (int) $plan->os_limit]);
            }
        }
        DB::transaction(function () use ($vendor, $base) {
            DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->where('is_addon', 0)->delete();
            foreach ($base as $k) {
                DB::table('vendor_company_os')->updateOrInsert(['vendor_id' => $vendor->id, 'os_key' => $k], ['is_addon' => 0, 'updated_at' => now(), 'created_at' => now()]);
            }
        });

        return null;
    }

    /** Adds an OS on top of the plan (bought, or granted by the platform team). */
    public static function addExtra(User $vendor, string $key): void
    {
        if (!isset(self::all()[$key])) {
            return;
        }
        if (DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->where('os_key', $key)->exists()) {
            return;   // already operated
        }
        DB::table('vendor_company_os')->insert(['vendor_id' => $vendor->id, 'os_key' => $key, 'is_addon' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public static function removeExtra(User $vendor, string $key): void
    {
        DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->where('os_key', $key)->where('is_addon', 1)->delete();
    }

    /** Names for display, e.g. "Stay OS, Exp OS". */
    public static function names(array $keys): string
    {
        return collect($keys)->map(fn ($k) => self::all()[$k]['name'] ?? $k)->implode(', ');
    }

    /** Staff seats left on the plan (null = unlimited). */
    public static function seatsLeft(User $vendor): ?int
    {
        $plan = self::plan($vendor);
        if (!$plan || !is_enable_plan() || (int) $plan->max_staff === 0) {
            return null;
        }
        $used = DB::table('vendor_team')->where('vendor_id', $vendor->id)->count();

        return max(0, (int) $plan->max_staff - $used);
    }
}
