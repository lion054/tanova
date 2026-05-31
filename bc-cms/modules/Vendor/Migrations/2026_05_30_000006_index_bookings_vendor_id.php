<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_bookings', function (Blueprint $table) {
            // All vendor API queries (list, analytics, status) filter by vendor_id.
            // Without this index every query is a full table scan.
            if (!$this->hasIndex('bc_bookings', 'idx_bookings_vendor_id')) {
                $table->index('vendor_id', 'idx_bookings_vendor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bc_bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_vendor_id');
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(\DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains($name);
    }
};
