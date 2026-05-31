<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVendorIdToConciergeConversations extends Migration
{
    public function up(): void
    {
        Schema::table('bc_concierge_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('bc_concierge_conversations', function (Blueprint $table) {
            $table->dropColumn('vendor_id');
        });
    }
}
