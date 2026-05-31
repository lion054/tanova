<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('secret', 64);          // HMAC signing secret
            $table->json('events');                 // ['booking.confirmed','booking.cancelled',...]
            $table->boolean('active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bc_vendor_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('bc_vendor_webhooks')->cascadeOnDelete();
            $table->string('event', 80);
            $table->json('payload');
            $table->smallInteger('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->boolean('success')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_webhook_deliveries');
        Schema::dropIfExists('bc_vendor_webhooks');
    }
};
