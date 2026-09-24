<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WP4 — departures with real capacity.
 *
 * bc_tour_dates.max_guests and min_guest are TINYINT (signed, 127 at most), so a
 * departure for 150 people cannot be stored. Widen them to SMALLINT UNSIGNED (65535).
 * Values already there are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE bc_tour_dates MODIFY max_guests SMALLINT UNSIGNED NULL');
        DB::statement('ALTER TABLE bc_tour_dates MODIFY min_guest SMALLINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bc_tour_dates MODIFY max_guests TINYINT NULL');
        DB::statement('ALTER TABLE bc_tour_dates MODIFY min_guest TINYINT NULL');
    }
};
