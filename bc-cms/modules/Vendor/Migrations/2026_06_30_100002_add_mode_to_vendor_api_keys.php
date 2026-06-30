<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds a live/test mode to vendor API keys so vendors can build against a
 * sandbox before going live:
 *
 *   live  → pk_live_… / sk_live_…   (real data, counts against limits)
 *   test  → pk_test_… / sk_test_…   (sandbox: no subscription required,
 *                                     not counted against the annual cap,
 *                                     responses flagged X-Tsoka-Mode: test)
 *
 * Backward-compatible: existing keys default to 'live'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_vendor_api_keys', 'mode')) {
                $table->string('mode', 10)->default('live')->after('type')->index();
            }
        });

        DB::table('bc_vendor_api_keys')->whereNull('mode')->update(['mode' => 'live']);
    }

    public function down(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('bc_vendor_api_keys', 'mode')) {
                $table->dropIndex(['mode']);
                $table->dropColumn('mode');
            }
        });
    }
};
