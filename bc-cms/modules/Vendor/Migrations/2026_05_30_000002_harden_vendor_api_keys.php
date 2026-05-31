<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            // Allow null — plain key is erased from DB immediately after creation/rotation
            $table->string('key', 64)->nullable()->change();
        });

        // Erase any plain keys that were stored before this fix
        \DB::table('bc_vendor_api_keys')->update(['key' => null]);
    }

    public function down(): void
    {
        Schema::table('bc_vendor_api_keys', function (Blueprint $table) {
            $table->string('key', 64)->nullable(false)->change();
        });
    }
};
