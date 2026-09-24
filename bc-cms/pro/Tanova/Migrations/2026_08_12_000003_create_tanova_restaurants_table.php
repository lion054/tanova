<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 2 — vendor-curated restaurants.
 *
 * NOTE ON HISTORY: an earlier bc_tanova_restaurants table was dropped in
 * 2024_01_02_000008 and replaced by OverpassRestaurantService, which queries
 * OpenStreetMap live. That decision is NOT reversed here.
 *
 * This table is deliberately additive: it holds the handful of venues a vendor has
 * an actual relationship with (negotiated rates, a contact, a booking policy) —
 * things OSM cannot know. The AI planner still uses OSM for general discovery;
 * these rows take precedence when one matches. Delete this table and the OSM path
 * keeps working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cuisine', 60)->nullable();
            $table->string('location')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // The relationship data that justifies this table existing at all.
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('booking_policy')->nullable();
            $table->decimal('negotiated_discount', 5, 2)->nullable(); // percent off menu
            $table->boolean('is_partner')->default(false);

            $table->unsignedTinyInteger('price_band')->nullable(); // 1–4, "$" to "$$$$"
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('dietary_vegetarian')->default(false);
            $table->boolean('dietary_vegan')->default(false);
            $table->boolean('dietary_halal')->default(false);

            $table->string('status', 12)->default('publish');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['vendor_id', 'is_partner']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_restaurants');
    }
};
