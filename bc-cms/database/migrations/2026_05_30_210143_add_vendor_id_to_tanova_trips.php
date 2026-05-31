<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tanova_trips', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('user_id')->comment('Vendor who created/owns this trip');
                $table->index('vendor_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
