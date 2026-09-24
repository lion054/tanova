<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WP3 — booking operations at parity with Tanova's booking page.
 *
 *  - bc_booking_comms:         every message and note about a booking, in one timeline.
 *  - bc_booking_guests:        who is travelling (the customer fills this in from a link).
 *  - bc_booking_payment_plan:  what is due when (a deposit and a balance, or instalments).
 *  - bc_booking_ledger:        money that actually moved: payments and refunds.
 *  - bc_booking_documents:     vouchers, tickets and anything else the guest should have.
 * All tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_booking_comms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id');
            // note | email | whatsapp | sms | call
            $table->string('channel', 20)->default('note');
            // out (we told them) | in (they told us) | internal (a note)
            $table->string('direction', 10)->default('internal');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'created_at']);
        });

        Schema::create('bc_booking_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->boolean('is_lead')->default(false);
            // vendor (typed in by the vendor) | customer (sent from the guest-form link)
            $table->string('source', 10)->default('vendor');
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('passport_number', 60)->nullable();
            $table->string('dietary', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('booking_id');
        });

        Schema::create('bc_booking_payment_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->string('label', 80);
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            // pending | paid | waived
            $table->string('status', 12)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['booking_id', 'sort_order']);
        });

        Schema::create('bc_booking_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id');
            // payment | refund
            $table->string('type', 10);
            $table->decimal('amount', 12, 2);
            // paypal | card | bank | cash | other
            $table->string('method', 20)->default('other');
            $table->string('reference', 120)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'occurred_at']);
        });

        Schema::create('bc_booking_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->string('name', 160);
            $table->unsignedBigInteger('file_id');
            $table->boolean('visible_to_customer')->default(true);
            $table->timestamps();
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        foreach (['bc_booking_documents', 'bc_booking_ledger', 'bc_booking_payment_plan', 'bc_booking_guests', 'bc_booking_comms'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
