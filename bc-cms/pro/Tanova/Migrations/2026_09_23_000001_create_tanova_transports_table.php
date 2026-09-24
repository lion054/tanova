<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port — ground transport catalog.
 *
 * Ported from luxsav.com's lux_transport / lux_transport_options tables (no
 * equivalent existed anywhere in the portal). Same vendor rule as every other
 * Tanova catalog table (bc_tanova_accommodations, bc_tanova_meals): vendor_id
 * is nullable so a row can be a shared platform-wide default, but a row with
 * vendor_id set is exclusive to that vendor. TanovaEngine queries this table
 * with the same .when($vendorId, ...) conditional used for bc_tours and
 * bc_tanova_accommodations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('bc_locations')->cascadeOnDelete();

            $table->string('transport_type', 30);   // own_transport | some_transfers | chauffeured_car | car_hire | transport_pass
            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('cost_per_day', 12, 2)->default(0);
            $table->decimal('cost_per_trip', 12, 2)->nullable();

            $table->text('terms_and_conditions')->nullable();
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();

            $table->string('status', 12)->default('publish'); // publish | draft
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['location_id', 'status']);
            $table->index(['vendor_id', 'location_id']);
        });

        Schema::create('bc_tanova_transport_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_id')->constrained('bc_tanova_transports')->cascadeOnDelete();

            $table->string('option_name');
            $table->string('option_value')->nullable();
            $table->decimal('price_modifier', 12, 2)->default(0);
            $table->boolean('per_person')->default(false);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();

            $table->string('status', 12)->default('publish');
            $table->timestamps();

            $table->index(['transport_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_transport_options');
        Schema::dropIfExists('bc_tanova_transports');
    }
};
