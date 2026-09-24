<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A booking document can be a link as well as an uploaded file, so the API (which has no upload) can add one. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_booking_documents', function ($table) {
            $table->string('external_url', 500)->nullable()->after('file_id');
        });
        DB::statement('ALTER TABLE bc_booking_documents MODIFY file_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('bc_booking_documents', function ($table) {
            $table->dropColumn('external_url');
        });
    }
};
