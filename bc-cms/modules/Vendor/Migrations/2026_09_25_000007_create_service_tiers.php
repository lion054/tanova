<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classic / Signature / Sublime: up to three named ways to buy one tour, each with
 * its own price (per person or for the group), group price bands, the party sizes
 * it takes, what is included, and add-ons bundled in at no charge. This is separate
 * from the vendor-wide markup rules in bc_vendor_pricing_tiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_service_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('object_model', 40)->default('tour');
            $table->unsignedBigInteger('object_id');
            $table->string('tier_key', 20);                       // classic | signature | sublime
            $table->string('name', 80);
            $table->string('tagline', 160)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('price_per_person')->default(true);
            $table->unsignedSmallInteger('min_guests')->nullable();
            $table->unsignedSmallInteger('max_guests')->nullable();
            $table->json('bands')->nullable();                     // [{min, max|null, total}]
            $table->json('inclusions')->nullable();               // ["Private guide", ...]
            $table->json('included_upsell_ids')->nullable();      // add-ons bundled in, free
            $table->boolean('recommended')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['vendor_id', 'object_model', 'object_id', 'tier_key'], 'svc_tier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_service_tiers');
    }
};
