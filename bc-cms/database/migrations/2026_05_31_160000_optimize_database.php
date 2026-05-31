<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optimize bc_concierge_conversations table
        if (Schema::hasTable('bc_concierge_conversations')) {
            Schema::table('bc_concierge_conversations', function (Blueprint $table) {
                // Add missing indexes for common queries
                if (!$this->indexExists('bc_concierge_conversations', 'idx_vendor_status')) {
                    $table->index(['vendor_id', 'status'], 'idx_vendor_status');
                }
                if (!$this->indexExists('bc_concierge_conversations', 'idx_vendor_channel')) {
                    $table->index(['vendor_id', 'channel'], 'idx_vendor_channel');
                }
                if (!$this->indexExists('bc_concierge_conversations', 'idx_updated_at')) {
                    $table->index('updated_at', 'idx_updated_at');
                }
            });
        }

        // Optimize bc_concierge_messages table
        if (Schema::hasTable('bc_concierge_messages')) {
            Schema::table('bc_concierge_messages', function (Blueprint $table) {
                if (!$this->indexExists('bc_concierge_messages', 'idx_conversation_created')) {
                    $table->index(['conversation_id', 'created_at'], 'idx_conversation_created');
                }
            });
        }

        // Optimize bc_tours table
        if (Schema::hasTable('bc_tours')) {
            Schema::table('bc_tours', function (Blueprint $table) {
                if (!$this->indexExists('bc_tours', 'idx_location_status')) {
                    $table->index(['location_id', 'status'], 'idx_location_status');
                }
                if (!$this->indexExists('bc_tours', 'idx_is_package')) {
                    $table->index('is_package', 'idx_is_package');
                }
                if (!$this->indexExists('bc_tours', 'idx_itinerary')) {
                    $table->fulltext('itinerary', 'idx_itinerary');
                }
            });
        }

        // Optimize users table (vendor lookups)
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'api_key')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'idx_api_key')) {
                    $table->index('api_key', 'idx_api_key');
                }
            });
        }
    }

    public function down(): void
    {
        // Rollback would drop these indexes if needed
    }

    private function indexExists($table, $indexName): bool
    {
        try {
            $indexes = \DB::select("SHOW INDEXES FROM $table WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};
