<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 1 — meals catalog.
 *
 * Ported from the standalone Tanova workspace (`d2t_meals_catalog`), which is
 * single-tenant. Here every row is owned by a vendor: the model uses
 * App\Traits\BelongsToVendor, so reads are scoped and vendor_id is stamped on
 * create automatically.
 *
 * These are itinerary *ingredients* — things an itinerary day references — not
 * independently bookable products. That is why this is a Tanova catalog table
 * (like bc_tanova_accommodations) rather than a full service module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('meal_type', 20)->default('lunch');   // breakfast | brunch | lunch | dinner | snack
            $table->string('cuisine', 60)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('image_id')->nullable();           // bc_media_files — nullable, no FK (media rows are soft-managed)

            // Pricing — per person, mirroring the source's adult/child/infant split.
            $table->decimal('adult_price', 12, 2)->default(0);
            $table->decimal('child_price', 12, 2)->nullable();
            $table->decimal('infant_price', 12, 2)->nullable();

            $table->unsignedSmallInteger('min_pax')->nullable();
            $table->unsignedSmallInteger('max_pax')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->boolean('dietary_vegetarian')->default(false);
            $table->boolean('dietary_vegan')->default(false);
            $table->boolean('dietary_halal')->default(false);
            $table->text('allergen_notes')->nullable();

            $table->string('status', 12)->default('publish');    // publish | draft
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['vendor_id', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_meals');
    }
};
