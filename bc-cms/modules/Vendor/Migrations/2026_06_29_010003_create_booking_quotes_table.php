<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Counter-offer / quote flow. A negotiation thread on a booking:
 * the vendor sends a quote, the customer can counter, either side accepts/declines.
 * parent_id links a counter to the quote it answers. Tenant-isolated via vendor_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_booking_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bc_bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable(); // the quote this one counters
            $table->string('direction', 20)->default('vendor'); // vendor | customer
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->nullable();
            $table->text('message')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('sent'); // sent | accepted | declined | countered | expired
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_booking_quotes');
    }
};
