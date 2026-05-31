<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthorIdToIntegrations extends Migration
{
    public function up(): void
    {
        Schema::table('bc_integrations', function (Blueprint $table) {
            // Drop the global unique on slug — each vendor will have their own row
            $table->dropUnique(['slug']);

            // Add the vendor (author) column right after slug
            $table->bigInteger('author_id')->unsigned()->nullable()->after('slug')->index();

            // New composite unique: one row per (slug + vendor)
            $table->unique(['slug', 'author_id'], 'integrations_slug_author_unique');
        });

        // Migrate existing rows: treat create_user as the owner
        \DB::table('bc_integrations')
            ->whereNull('author_id')
            ->whereNotNull('create_user')
            ->update(['author_id' => \DB::raw('create_user')]);
    }

    public function down(): void
    {
        Schema::table('bc_integrations', function (Blueprint $table) {
            $table->dropUnique('integrations_slug_author_unique');
            $table->dropColumn('author_id');
            $table->unique(['slug']);
        });
    }
}
