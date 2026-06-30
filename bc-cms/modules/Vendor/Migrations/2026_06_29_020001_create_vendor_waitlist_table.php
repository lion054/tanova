<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Waitlist. Captures interest for sold-out / unavailable services so the
 * vendor can notify customers when space frees up. Tenant-isolated via vendor_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('object_model', 255)->nullable(); // service type interested in
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->integer('party_size')->default(1);
            $table->date('preferred_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('waiting'); // waiting | notified | converted | cancelled
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_waitlist');
    }
};
