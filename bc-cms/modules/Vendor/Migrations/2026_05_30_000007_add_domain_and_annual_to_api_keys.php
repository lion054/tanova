<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            // Domain the key is issued for — auto-registers CORS origin on creation
            $table->string('domain', 255)->nullable()->after('name');
        });

        // Change cache naming from monthly to annual — existing counts are wrong anyway
        // No data migration needed: counts recompute from bc_vendor_api_usage
    }

    public function down(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            $table->dropColumn('domain');
        });
    }
};
