<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Tanova marketplace listings. A vendor opts a service (tour/hotel/…)
 * into the public Tanova marketplace by creating a visible row here. Discovery
 * only surfaces visible listings; everything transactional stays vendor-scoped.
 *
 * vendor_id mirrors the service's owner (author_id) so BelongsToVendor keeps the
 * vendor's own toggle view isolated; the public MCP reads across vendors but only
 * the public listing fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('object_model', 50);  // tour | hotel | ...
            $table->unsignedBigInteger('object_id');
            $table->boolean('visible')->default(true);
            $table->json('channels')->nullable();  // optional per-AI-platform allowlist
            $table->timestamps();

            $table->unique(['object_model', 'object_id'], 'mkt_listing_obj_unique');
            $table->index(['visible', 'object_model'], 'mkt_listing_visible_idx');
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_marketplace_listings');
    }
};
