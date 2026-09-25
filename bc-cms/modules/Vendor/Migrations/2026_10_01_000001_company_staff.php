<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Company staff: employees of a vendor company. A staff member belongs to exactly one company (users.vendor_id, which the tenant
 * resolver already reads) and has the role "vendor_staff", which carries no permissions of its own: what they may open is what the
 * company owner ticks for them (vendor_team.permissions), checked by StaffAccess.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'vendor_id')) {
            Schema::table('users', function (Blueprint $t) { $t->unsignedBigInteger('vendor_id')->nullable()->index(); });
        }
        // One company per person.
        try {
            Schema::table('vendor_team', function (Blueprint $t) { $t->unique('member_id', 'vendor_team_member_unique'); });
        } catch (\Throwable $e) {
            // already there
        }
        if (!DB::table('core_roles')->where('code', 'vendor_staff')->exists()) {
            DB::table('core_roles')->insert(['name' => 'vendor_staff', 'code' => 'vendor_staff', 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('core_roles')->where('code', 'vendor_staff')->delete();
        try { Schema::table('vendor_team', fn (Blueprint $t) => $t->dropUnique('vendor_team_member_unique')); } catch (\Throwable $e) {}
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('vendor_id'));
    }
};
