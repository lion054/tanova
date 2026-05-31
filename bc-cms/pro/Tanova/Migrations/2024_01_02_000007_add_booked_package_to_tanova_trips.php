<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            $table->unsignedTinyInteger('booked_package')->nullable()->after('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            $table->dropColumn('booked_package');
        });
    }
};
