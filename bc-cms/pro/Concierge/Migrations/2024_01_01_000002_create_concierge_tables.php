<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConciergeTables extends Migration
{
    public function up(): void
    {
        // A conversation thread — one per guest/booking context
        Schema::create('bc_concierge_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('booking_id')->nullable()->index();

            // NoBeds reference for this thread (referral_order_id from NoBeds Messages)
            $table->string('nobeds_thread_id')->nullable()->index();
            $table->integer('nobeds_hotel_id')->nullable();

            // Guest info snapshot (for non-registered / OTA guests)
            $table->string('guest_name', 255)->nullable();
            $table->string('guest_email', 255)->nullable();
            $table->string('channel', 60)->default('web'); // web, whatsapp, nobeds, email

            // open | resolved | escalated
            $table->string('status', 30)->default('open')->index();

            // Last message preview for list view
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->bigInteger('create_user')->nullable();
            $table->bigInteger('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Individual messages within a conversation
        Schema::create('bc_concierge_messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('conversation_id')->index();
            $table->foreign('conversation_id')
                  ->references('id')->on('bc_concierge_conversations')
                  ->onDelete('cascade');

            // sender_type: guest | agent | ai | system
            $table->string('sender_type', 30)->default('guest');
            $table->unsignedBigInteger('sender_id')->nullable(); // user_id if sender is staff/ai

            $table->text('body');

            // NoBeds message reference
            $table->integer('nobeds_message_id')->nullable()->index();

            // Whether the AI drafted this reply and it still needs staff approval
            $table->tinyInteger('ai_draft')->default(0);
            $table->tinyInteger('approved')->default(0);

            $table->timestamp('read_at')->nullable();

            $table->bigInteger('create_user')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_concierge_messages');
        Schema::dropIfExists('bc_concierge_conversations');
    }
}
