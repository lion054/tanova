<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 4 — holiday greeting calendar.
 *
 * Ported from `d2t_holidays`. This is NOT the same as bc_vendor_occasions:
 *   occasions = a date belonging to one customer (birthday, anniversary)
 *   holidays  = a shared calendar date greeted to many customers (Eid, Christmas)
 *
 * Kept separate from bc_vendor_scheduled_messages because that table's triggers key
 * off a booking's dates; a holiday keys off the calendar and has no booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->string('type', 20)->default('public');   // public | religious | company | custom
            $table->date('date');
            $table->boolean('recurs_annually')->default(true);

            $table->string('channel', 20)->default('email'); // email | whatsapp | sms
            $table->string('custom_subject')->nullable();
            $table->text('custom_body')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['vendor_id', 'active']);
            $table->index(['vendor_id', 'date']);
        });

        // Delivery log — one row per recipient per holiday per year, which is also
        // what makes sending idempotent (see the unique index).
        Schema::create('bc_vendor_holiday_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('holiday_id')->constrained('bc_vendor_holidays')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->unsignedSmallInteger('year');
            $table->string('recipient')->nullable();          // email or msisdn actually used
            $table->string('channel', 20)->default('email');
            $table->string('status', 20)->default('sent');    // sent | failed | skipped
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['holiday_id', 'customer_id', 'year'], 'holiday_customer_year_unique');
            $table->index(['vendor_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_holiday_sends');
        Schema::dropIfExists('bc_vendor_holidays');
    }
};
