<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds tsokanew import tracking fields to bc_tours and bc_locations.
 */
class AddTanovaImportFields extends Migration
{
    public function up(): void
    {
        // bc_tours: activity_type for rainy-day indoor filtering, tsokanew_id for idempotent imports
        Schema::table('bc_tours', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tours', 'zone')) {
                $table->unsignedTinyInteger('zone')->nullable()->default(0);
            }
            if (!Schema::hasColumn('bc_tours', 'time_slot')) {
                $table->unsignedTinyInteger('time_slot')->nullable()->default(0);
            }
            if (!Schema::hasColumn('bc_tours', 'activity_type')) {
                $table->string('activity_type', 100)->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'tsokanew_id')) {
                $table->unsignedInteger('tsokanew_id')->nullable()->unique();
            }
        });

        // bc_locations: number of geographic zones for this destination (used by TanovaEngine)
        Schema::table('bc_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_locations', 'tanova_zones')) {
                $table->unsignedTinyInteger('tanova_zones')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->dropColumn(['activity_type', 'tsokanew_id']);
        });
        Schema::table('bc_locations', function (Blueprint $table) {
            $table->dropColumn('tanova_zones');
        });
    }
}
