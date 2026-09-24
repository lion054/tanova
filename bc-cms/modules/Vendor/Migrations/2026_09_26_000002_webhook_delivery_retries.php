<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Webhook deliveries that can be retried on a schedule and traced by a stable event id. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_webhook_deliveries', function (Blueprint $table) {
            $table->string('event_id', 40)->nullable()->after('webhook_id');
            $table->timestamp('next_attempt_at')->nullable()->after('delivered_at');
            $table->unsignedInteger('duration_ms')->nullable()->after('next_attempt_at');
            $table->index(['success', 'next_attempt_at'], 'wh_retry');
        });
    }

    public function down(): void
    {
        Schema::table('bc_vendor_webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex('wh_retry');
            $table->dropColumn(['event_id', 'next_attempt_at', 'duration_ms']);
        });
    }
};
