<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Upsells / add-ons.
 *  - bc_vendor_upsells: the vendor's catalog of add-ons.
 *  - bc_booking_upsells: add-ons attached to a specific booking. Name/price are
 *    SNAPSHOTTED so later catalog edits never rewrite historical bookings.
 * Both tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_upsells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('price_type', 20)->default('per_booking'); // per_booking | per_person | per_night
            $table->integer('sort_order')->default(0);
            $table->string('status', 20)->default('publish');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
        });

        Schema::create('bc_booking_upsells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bc_bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('upsell_id')->nullable(); // catalog ref (snapshot kept below)
            $table->string('name');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->integer('qty')->default(1);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_booking_upsells');
        Schema::dropIfExists('bc_vendor_upsells');
    }
};
