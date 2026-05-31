<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntegrationsTable extends Migration
{
    public function up(): void
    {
        // Stores per-integration connection state + credentials
        Schema::create('bc_integrations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // e.g. "airbnb", "booking", "expedia", "stripe", "mpesa"
            $table->string('slug', 60)->unique();

            // "stay_os" | "exp_os" | "trans_os" | "airline_os" | "sale_os" | "payment" | "communication"
            $table->string('category', 40)->index();

            // connected | disconnected | error
            $table->string('status', 20)->default('disconnected');

            // JSON blob for API keys, tokens, IDs (encrypted at rest via cast)
            $table->text('credentials')->nullable();

            // Last time we verified the connection
            $table->timestamp('last_verified_at')->nullable();

            // Human-readable error from last attempt
            $table->text('last_error')->nullable();

            $table->bigInteger('create_user')->nullable();
            $table->bigInteger('update_user')->nullable();
            $table->timestamps();
        });

        // Per-room NoBeds channel enable/disable (mirrors NoBeds Rentals OTA flags locally)
        Schema::create('bc_integration_room_channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('integration_slug', 60)->index(); // "airbnb", "booking", etc.
            $table->integer('nobeds_room_id')->index();
            $table->string('nobeds_hotel_id')->nullable();
            $table->string('channel_room_id')->nullable();   // OTA's own room/listing ID
            $table->string('channel_rate_id')->nullable();
            $table->tinyInteger('enabled')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['integration_slug', 'nobeds_room_id'], 'irc_slug_room_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_integration_room_channels');
        Schema::dropIfExists('bc_integrations');
    }
}
