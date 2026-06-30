<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Check-in tracking. One record per booking capturing guest arrival /
 * departure / no-show state for daily operations. Tenant-isolated via vendor_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_booking_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bc_bookings')->cascadeOnDelete();
            $table->string('status', 20)->default('expected'); // expected | checked_in | checked_out | no_show
            $table->timestamp('checkin_at')->nullable();
            $table->timestamp('checkout_at')->nullable();
            $table->integer('guests_present')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('booking_id');
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_booking_checkins');
    }
};
