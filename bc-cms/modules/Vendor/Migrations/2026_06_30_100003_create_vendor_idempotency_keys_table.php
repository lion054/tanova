<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the result of a write request keyed by the client-supplied
 * Idempotency-Key header, so a retried request (same key) returns the original
 * response instead of performing the action twice (e.g. double bookings).
 *
 * Scoped per vendor. `request_hash` guards against accidentally reusing a key
 * for a different payload. Rows are safe to prune after 24–72h.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bc_vendor_idempotency_keys')) {
            return;
        }

        Schema::create('bc_vendor_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key', 191);
            $table->string('method', 10);
            $table->string('path', 255);
            $table->string('request_hash', 64);       // sha256 of method+path+body
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->unique(['vendor_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_idempotency_keys');
    }
};
