<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — MCP marketplace session fields on Tanova trips.
 *  - source: where the trip originated ('admin' | 'mcp').
 *  - session_token: opaque token the traveller/AI uses to address a planning
 *    session (the secret that ties an anonymous marketplace flow to one trip).
 * The owning vendor_id is resolved from the selected experience at draft time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tanova_trips', 'source')) {
                $table->string('source', 20)->default('admin')->after('status')->index();
            }
            if (!Schema::hasColumn('bc_tanova_trips', 'session_token')) {
                $table->string('session_token', 64)->nullable()->unique()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bc_tanova_trips', function (Blueprint $table) {
            if (Schema::hasColumn('bc_tanova_trips', 'session_token')) {
                $table->dropUnique(['session_token']);
                $table->dropColumn('session_token');
            }
            if (Schema::hasColumn('bc_tanova_trips', 'source')) {
                $table->dropIndex(['source']);
                $table->dropColumn('source');
            }
        });
    }
};
