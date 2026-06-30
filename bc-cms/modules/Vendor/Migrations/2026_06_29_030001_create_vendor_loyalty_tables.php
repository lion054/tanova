<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Loyalty program.
 *  - bc_vendor_loyalty_tiers: vendor's tier definitions (bronze→VIP) by point threshold.
 *  - bc_vendor_loyalty_accounts: a customer's running balance with a vendor (keyed by email).
 *  - bc_vendor_loyalty_transactions: append-only points ledger (earn / redeem / adjust).
 * All tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->integer('min_points')->default(0);
            $table->decimal('earn_multiplier', 6, 2)->default(1);
            $table->text('perks')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'min_points']);
        });

        Schema::create('bc_vendor_loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->integer('points')->default(0);
            $table->unsignedBigInteger('tier_id')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'customer_email']);
        });

        Schema::create('bc_vendor_loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('bc_vendor_loyalty_accounts')->cascadeOnDelete();
            $table->integer('points'); // positive = earn, negative = redeem
            $table->string('type', 20)->default('earn'); // earn | redeem | adjust
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_loyalty_transactions');
        Schema::dropIfExists('bc_vendor_loyalty_accounts');
        Schema::dropIfExists('bc_vendor_loyalty_tiers');
    }
};
