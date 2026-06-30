<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Occasions. Customer special dates (birthday / anniversary) the vendor
 * can target with offers. The scheduled-message engine can fire on the "occasion"
 * trigger. Tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_occasions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('type', 30)->default('birthday'); // birthday | anniversary | custom
            $table->date('occasion_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_occasions');
    }
};
