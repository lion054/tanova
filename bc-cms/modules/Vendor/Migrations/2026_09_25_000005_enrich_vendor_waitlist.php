<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Waitlist entries that know who joined them (the app or the vendor), which
 * customer account they belong to, how often they have been told, and the
 * booking that ended the wait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_waitlist', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('object_id');
            $table->string('source', 12)->default('vendor')->after('status'); // vendor | app
            $table->unsignedSmallInteger('notified_count')->default(0)->after('notified_at');
            $table->unsignedBigInteger('booking_id')->nullable()->after('notified_count');
            $table->index(['vendor_id', 'object_model', 'object_id', 'preferred_date'], 'wl_service_day');
        });
    }

    public function down(): void
    {
        Schema::table('bc_vendor_waitlist', function (Blueprint $table) {
            $table->dropIndex('wl_service_day');
            $table->dropColumn(['customer_id', 'source', 'notified_count', 'booking_id']);
        });
    }
};
