<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanova port, phase 7 — per-vendor AI planning preferences.
 *
 * Ported from the source's settings/ai-plan screen. A dedicated table rather than
 * columns on `users`, because these are Tanova's settings and the pro module should
 * be removable without leaving orphan columns on a core table.
 *
 * One row per vendor, enforced by the unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->string('tone', 20)->default('professional');   // professional | warm | adventurous | luxury
            $table->string('pace', 20)->default('balanced');        // relaxed | balanced | packed
            $table->unsignedSmallInteger('max_days')->default(14);
            $table->unsignedSmallInteger('activities_per_day')->default(3);

            $table->boolean('include_meals')->default(true);
            $table->boolean('include_restaurants')->default(true);
            $table->boolean('prefer_own_catalog')->default(true);   // own catalog before OSM discovery
            $table->boolean('auto_publish')->default(false);        // publish generated trips without review

            $table->text('house_rules')->nullable();                // free-text guidance injected into the prompt
            $table->text('avoid')->nullable();                      // things never to suggest

            $table->timestamps();

            $table->unique('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_ai_settings');
    }
};
