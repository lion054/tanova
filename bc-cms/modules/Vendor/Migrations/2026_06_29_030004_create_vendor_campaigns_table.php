<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Email campaigns. Bulk email to the vendor's past customers (resolved
 * from booking emails at send time). Tenant-isolated via vendor_id (BelongsToVendor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('audience', 30)->default('all_customers'); // all_customers | completed | upcoming
            $table->string('status', 20)->default('draft'); // draft | sending | sent
            $table->integer('sent_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_campaigns');
    }
};
