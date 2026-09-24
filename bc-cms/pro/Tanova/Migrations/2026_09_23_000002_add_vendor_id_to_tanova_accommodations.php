<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TanovaEngine::generateAccommodation() has queried bc_tanova_accommodations
 * by vendor_id since it was written, but the column was never migrated —
 * every vendor-scoped call (any /api/v/tanova/generate request with days > 1)
 * has been throwing SQLSTATE[42S22] (unknown column) in production.
 *
 * Nullable and unindexed-by-default-null: existing rows (imported from
 * tsokanew, offered_by populated) stay as shared platform defaults until
 * explicitly claimed by a vendor. Same rule as bc_tanova_transports and
 * bc_tours.author_id — null/unset = shared pool, set = exclusive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tanova_accommodations', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('location_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['vendor_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bc_tanova_accommodations', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
