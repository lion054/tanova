<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_api_usage', function (Blueprint $table) {
            // Composite index so monthlyUsageCount() COUNT(*) is a fast index scan,
            // not a full table scan filtered by date.
            $table->index(['vendor_api_key_id', 'created_at'], 'idx_usage_key_date');
        });
    }

    public function down(): void
    {
        Schema::table('bc_vendor_api_usage', function (Blueprint $table) {
            $table->dropIndex('idx_usage_key_date');
        });
    }
};
