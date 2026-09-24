<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 4 — vendor CRM.
 *
 * Ported from `d2t_customers`. A customer is a *person the vendor has dealt with*,
 * which is not the same thing as a registered site user: many bookings are made by
 * guests who never create an account. Records are derived from bc_bookings by
 * CustomerSyncService and deduplicated on email, then phone (see the service for
 * the rule) — hence the unique indexes below rather than a plain id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            // Optional link to a registered account. Null for guest bookers.
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('passport_number', 60)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            // Denormalised rollups, refreshed by CustomerSyncService.
            $table->unsignedInteger('bookings_count')->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->timestamp('first_booking_at')->nullable();
            $table->timestamp('last_booking_at')->nullable();

            $table->string('source', 20)->default('booking'); // booking | manual | import
            $table->timestamps();

            // Dedupe keys — scoped per vendor, so the same person can exist for two
            // vendors without collision. Nullable columns skip the constraint in
            // MySQL, which is what we want for guests with only a phone.
            $table->unique(['vendor_id', 'email'], 'vendor_customer_email_unique');
            $table->unique(['vendor_id', 'phone'], 'vendor_customer_phone_unique');
            $table->index(['vendor_id', 'last_booking_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_customers');
    }
};
