<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds key types to vendor API keys so a vendor's *website* can use a
 * browser-safe, read-only PUBLISHABLE key (pk_live_) while server-side
 * integrations keep using a full-access SECRET key (sk_live_).
 *
 *  - type:   'secret' (default, full access) | 'publishable' (read-only)
 *  - scopes: optional JSON list for future granularity (null = type default)
 *
 * Backward-compatible: every existing key is stamped 'secret', so current
 * sk_live_ keys keep full access unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_vendor_api_keys', 'type')) {
                $table->string('type', 20)->default('secret')->after('name')->index();
            }
            if (!Schema::hasColumn('bc_vendor_api_keys', 'scopes')) {
                $table->json('scopes')->nullable()->after('type');
            }
        });

        // Stamp any pre-existing rows explicitly (defensive; default already covers it).
        DB::table('bc_vendor_api_keys')->whereNull('type')->update(['type' => 'secret']);
    }

    public function down(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('bc_vendor_api_keys', 'scopes')) {
                $table->dropColumn('scopes');
            }
            if (Schema::hasColumn('bc_vendor_api_keys', 'type')) {
                $table->dropIndex(['type']);
                $table->dropColumn('type');
            }
        });
    }
};
