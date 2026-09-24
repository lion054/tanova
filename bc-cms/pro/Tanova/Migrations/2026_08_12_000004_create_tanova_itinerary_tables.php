<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 3 — manual itinerary builder.
 *
 * Ported from `d2t_itinerary_templates` + `d2t_itinerary_days`. The portal already
 * generates itineraries with AI (TanovaEngine → bc_tanova_trips); what it has never
 * had is a way for a human to author or correct one day by day. That is this.
 *
 * A template is reusable and not tied to a booking. Applying one to a trip copies
 * its days, so later edits to the template do not silently rewrite sold itineraries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_itinerary_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('tour_id')->nullable();   // optional link to a Tour
            $table->unsignedSmallInteger('total_days')->default(1);
            $table->unsignedSmallInteger('total_nights')->default(0);
            $table->string('status', 12)->default('draft');      // draft | publish
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });

        Schema::create('bc_tanova_itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('bc_tanova_itinerary_templates')->cascadeOnDelete();

            $table->unsignedSmallInteger('day_number');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            // Catalog references. Nullable / JSON rather than FKs: a day may point at
            // a meal that is later deleted, and losing the whole day to a cascade
            // would be worse than holding a stale id.
            $table->unsignedBigInteger('accommodation_id')->nullable();
            $table->json('meal_ids')->nullable();
            $table->json('activity_ids')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'day_number']);
            $table->index(['vendor_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_itinerary_days');
        Schema::dropIfExists('bc_tanova_itinerary_templates');
    }
};
