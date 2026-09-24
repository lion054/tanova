<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WP5 — how guests earn loyalty points. One row per vendor: whether points are
 * earned at all, and how much a guest spends for one point (Tanova's rule is
 * $10 = 1 point, times the multiplier of the tier the guest is already in).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('spend_per_point', 10, 2)->default(10);
            $table->timestamps();
            $table->unique('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_loyalty_rules');
    }
};
