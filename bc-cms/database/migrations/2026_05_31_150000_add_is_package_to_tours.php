<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bc_tours')) {
            return;
        }

        if (!Schema::hasColumn('bc_tours', 'is_package')) {
            Schema::table('bc_tours', function (Blueprint $table) {
                $table->boolean('is_package')->default(0)->comment('Flag to identify multi-day tour packages');
                $table->index('is_package');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bc_tours') && Schema::hasColumn('bc_tours', 'is_package')) {
            Schema::table('bc_tours', function (Blueprint $table) {
                $table->dropIndex(['is_package']);
                $table->dropColumn('is_package');
            });
        }
    }
};
