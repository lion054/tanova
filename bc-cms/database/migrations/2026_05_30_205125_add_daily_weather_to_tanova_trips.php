<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            $table->json('daily_weather')->nullable()->after('itinerary')->comment('Original daily weather when trip was created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            $table->dropColumn('daily_weather');
        });
    }
};
