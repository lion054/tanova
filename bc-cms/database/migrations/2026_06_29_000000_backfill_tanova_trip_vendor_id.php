<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill legacy Tanova trips whose vendor_id was never stamped (or stamped null
 * because a vendor owner has no vendor_id column). With the new BelongsToVendor
 * global scope these rows would otherwise be invisible. A trip's owner is its
 * user_id, which is also the vendor-owner's user id — so vendor_id := user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bc_tanova_trips', 'vendor_id')) {
            return;
        }

        DB::table('bc_tanova_trips')
            ->whereNull('vendor_id')
            ->whereNotNull('user_id')
            ->update(['vendor_id' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        // Non-reversible data backfill; nothing to undo.
    }
};
