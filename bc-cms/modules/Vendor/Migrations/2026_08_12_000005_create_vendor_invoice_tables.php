<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 5 — invoices.
 *
 * DESIGN DECISION (TANOVA_PORT_PLAN.md §8 asked this question; this is the answer):
 * an invoice here is a SEPARATE DOCUMENT with its own ledger, not a view over
 * bc_bookings. Vendors invoice for work that never passed through the booking
 * engine — off-platform charter, agency commission, damage recharges.
 *
 * To avoid two competing answers to "was this paid?", this module NEVER writes back
 * to a booking's payment state. `booking_id` is an optional cross-reference only.
 * Booking payments stay the business of the Booking module; invoice payments stay
 * here. Reconciliation between the two is a reporting concern, not a write path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('number', 40);
            $table->unsignedBigInteger('booking_id')->nullable();   // cross-reference only
            $table->unsignedBigInteger('customer_id')->nullable();  // bc_vendor_customers

            // Snapshot of who was billed — an invoice must not change because a
            // customer record was later edited.
            $table->string('bill_to_name')->nullable();
            $table->string('bill_to_email')->nullable();
            $table->text('bill_to_address')->nullable();

            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 8)->default('USD');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);

            $table->string('status', 16)->default('draft'); // draft|sent|part_paid|paid|void
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'number']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('bc_vendor_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('bc_vendor_invoices')->cascadeOnDelete();

            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'invoice_id']);
        });

        Schema::create('bc_vendor_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('bc_vendor_invoices')->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->string('method', 30)->default('cash'); // cash|bank|card|mobile_money|other
            $table->string('reference')->nullable();
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_invoice_payments');
        Schema::dropIfExists('bc_vendor_invoice_lines');
        Schema::dropIfExists('bc_vendor_invoices');
    }
};
