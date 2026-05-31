<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds scheduling / availability columns to bc_tours so Tanova can skip
 * activities that are not running on a specific date.
 *
 *   available_months  — JSON array of months (1–12) when the activity runs.
 *                       NULL means available every month.
 *                       Example: [6,7,8,9,10,11]  for Jun–Nov whale watching.
 *
 *   available_days    — JSON array of ISO day-of-week integers (1=Mon … 7=Sun)
 *                       when the activity runs. NULL means every day.
 *                       Example: [1,2,3,4,5]  for weekdays only.
 */
class AddAvailabilityToBcTours extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->json('available_months')->nullable()->after('activity_type');
            $table->json('available_days')->nullable()->after('available_months');
        });
    }

    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->dropColumn(['available_months', 'available_days']);
        });
    }
}
