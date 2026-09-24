<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who did what to a business's money and settings, when, and from where. Rows are only ever added. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bc_audit_log')) {
            return;
        }
        Schema::create('bc_audit_log', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->nullable()->index();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('actor_type', 12)->default('user');          // user | api_key | guest | system
            $t->string('action', 60)->index();
            $t->string('subject_type', 40)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('summary', 255)->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        // Not reversible: an audit trail is not something to drop.
    }
};
