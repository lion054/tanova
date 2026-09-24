<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Where an occasion came from, so birthdays read off customers and guest forms are kept up to date, not duplicated. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_occasions', function (Blueprint $table) {
            $table->string('source', 12)->default('manual')->after('type'); // manual | customer | guest
            $table->string('source_key', 40)->nullable()->after('source');
            $table->unique(['vendor_id', 'source_key'], 'occ_source_key');
        });
    }

    public function down(): void
    {
        Schema::table('bc_vendor_occasions', function (Blueprint $table) {
            $table->dropUnique('occ_source_key');
            $table->dropColumn(['source', 'source_key']);
        });
    }
};
