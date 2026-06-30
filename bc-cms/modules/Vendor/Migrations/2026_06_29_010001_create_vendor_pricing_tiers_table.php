<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Pricing tiers. Vendor-scoped markup rules (e.g. bronze/silver/gold/VIP)
 * applied on top of a base price. Tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 120)->nullable();
            $table->string('markup_type', 20)->default('percentage'); // percentage | fixed
            $table->decimal('markup_value', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('status', 20)->default('publish');
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_pricing_tiers');
    }
};
