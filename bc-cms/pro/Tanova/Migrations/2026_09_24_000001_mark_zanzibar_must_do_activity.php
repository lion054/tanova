<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tanova needs at least one zone-1 ("Must Do") activity at a location to build
 * any package there; LuxSav's Zanzibar had none, so trips to it came back empty.
 * The Spice Tour is Zanzibar's signature experience. Touches only LuxSav's own
 * activities (other vendors share this table), and does nothing if it already
 * has a Must Do there or the tour isn't found.
 */
return new class extends Migration
{
    private const ZANZIBAR_LOCATION_ID = 11;

    /** LuxSav's vendor account. */
    private const VENDOR_ID = 7;

    public function up(): void
    {
        $tours = DB::table('bc_tours')
            ->where('author_id', self::VENDOR_ID)
            ->where('location_id', self::ZANZIBAR_LOCATION_ID)
            ->whereNull('deleted_at');

        if ((clone $tours)->where('zone', 1)->exists()) {
            return;
        }

        (clone $tours)->where('title', 'Spice Tour')->update(['zone' => 1]);
    }

    public function down(): void
    {
        DB::table('bc_tours')
            ->where('author_id', self::VENDOR_ID)
            ->where('location_id', self::ZANZIBAR_LOCATION_ID)
            ->where('title', 'Spice Tour')
            ->where('zone', 1)
            ->update(['zone' => 2]);
    }
};
