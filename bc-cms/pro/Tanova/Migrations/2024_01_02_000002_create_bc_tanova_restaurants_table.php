<?php

use Illuminate\Database\Migrations\Migration;

// Restaurants are now fetched live from OpenStreetMap — this table is no longer used.
// Migration 000008 drops it on existing installs.
class CreateBcTanovaRestaurantsTable extends Migration
{
    public function up(): void {}
    public function down(): void {}
}
