<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Scheduled messages. Lifecycle-triggered messages (pre-trip reminder,
 * departure day, check-in, welcome-home, review request, …) sent on the vendor's
 * own channel. A daily command resolves due messages and logs each send.
 * Tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger', 40);          // pre_trip | departure | check_in | welcome_home | review_request | occasion
            $table->integer('offset_days')->default(0); // days relative to the trigger date (negative = before)
            $table->string('channel', 20)->default('email'); // email | whatsapp | telegram | sms
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['vendor_id', 'active']);
        });

        Schema::create('bc_vendor_scheduled_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scheduled_message_id')->constrained('bc_vendor_scheduled_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('channel', 20);
            $table->string('recipient')->nullable();
            $table->string('status', 20)->default('sent'); // sent | failed | skipped
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Explicit short names — the auto-generated ones exceed MySQL's 64-char limit.
            $table->index(['vendor_id', 'scheduled_message_id'], 'sm_logs_vendor_msg_idx');
            $table->index(['scheduled_message_id', 'booking_id'], 'sm_logs_msg_booking_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_scheduled_message_logs');
        Schema::dropIfExists('bc_vendor_scheduled_messages');
    }
};
