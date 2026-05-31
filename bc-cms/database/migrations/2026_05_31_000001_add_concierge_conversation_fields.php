<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bc_concierge_conversations')) {
            Schema::table('bc_concierge_conversations', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_concierge_conversations', 'guest_phone')) {
                    $table->string('guest_phone')->nullable()->after('guest_email');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'chatbot_name')) {
                    $table->string('chatbot_name')->nullable()->after('channel');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'category')) {
                    $table->enum('category', ['booking', 'complaint', 'general', 'other'])->default('general')->after('chatbot_name');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'priority')) {
                    $table->enum('priority', ['low', 'normal', 'high'])->default('normal')->after('category');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'initiated_by')) {
                    $table->enum('initiated_by', ['guest', 'vendor'])->default('guest')->after('priority');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'assigned_to')) {
                    $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'resolution_reason')) {
                    $table->text('resolution_reason')->nullable()->after('assigned_to');
                }
                if (!Schema::hasColumn('bc_concierge_conversations', 'assigned_to')) {
                    $table->foreign('assigned_to')->references('id')->on('bc_users')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('bc_concierge_conversations', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['assigned_to']);
            $table->dropColumn([
                'guest_phone',
                'category',
                'priority',
                'initiated_by',
                'assigned_to',
                'resolution_reason',
            ]);
        });
    }
};
