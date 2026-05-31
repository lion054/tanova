<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tourpay_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('tanova_trip_id')->nullable()->after('author_id')->index();
        });

        // Backfill vendor_id on existing Tanova bookings
        DB::statement("
            UPDATE bc_bookings b
            JOIN bc_tanova_trips t ON t.booking_id = b.id
            SET b.vendor_id = t.user_id
            WHERE b.object_model = 'tanova_trip'
              AND b.vendor_id IS NULL
              AND t.user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('bc_tourpay_invoices', function (Blueprint $table) {
            $table->dropColumn('tanova_trip_id');
        });
    }
};
