<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tanova only offers a vendor the accommodations that vendor owns, but every
 * accommodation imported from the old catalogue had no owner, so LuxSav's
 * packages came back with no hotel. They are all LuxSav's own data (no other
 * vendor has any), so this gives them to LuxSav.
 *
 * The Singapore hotels were also filed under "Singapore City" (location 12), a
 * duplicate of Singapore (location 5) that has no activities, so they are moved
 * to where the activities are.
 */
return new class extends Migration
{
    private const VENDOR_ID = 7;
    private const SINGAPORE = 5;
    private const SINGAPORE_CITY = 12;

    public function up(): void
    {
        DB::table('bc_tanova_accommodations')
            ->whereNull('vendor_id')
            ->update(['vendor_id' => self::VENDOR_ID]);

        DB::table('bc_tanova_accommodations')
            ->where('vendor_id', self::VENDOR_ID)
            ->where('location_id', self::SINGAPORE_CITY)
            ->update(['location_id' => self::SINGAPORE]);
    }

    public function down(): void
    {
        // Ownership was never recorded before, and moving Singapore's hotels
        // back would leave Singapore without any, so this stays as it is.
    }
};
