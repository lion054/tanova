<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WP1 — add-ons at parity with Tanova's upsell catalogue.
 *
 *  - bc_vendor_upsells gains a category (transport, accommodation, activity, service,
 *    equipment, memory), a one-line description, an image, and featured / global flags.
 *  - bc_vendor_upsell_services says which services an add-on is offered on, with an
 *    optional price for that service and a highlight flag. A global add-on is offered
 *    on every service; an add-on with neither flag is offered nowhere until assigned.
 *
 * Nothing is guessed: existing add-ons keep their price and price type, and become
 * global (they were attachable to any booking) so nothing changes for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_vendor_upsells', function (Blueprint $table) {
            $table->string('category', 30)->default('service')->after('name');
            $table->string('short_description', 255)->nullable()->after('description');
            $table->unsignedBigInteger('image_id')->nullable()->after('short_description');
            $table->boolean('is_featured')->default(false)->after('image_id');
            $table->boolean('is_global')->default(false)->after('is_featured');
        });

        // What existed could be attached to any booking: keep that.
        \Illuminate\Support\Facades\DB::table('bc_vendor_upsells')->update(['is_global' => true]);

        Schema::create('bc_vendor_upsell_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('upsell_id');
            // tour | hotel | car | boat | event | space
            $table->string('object_model', 30);
            $table->unsignedBigInteger('object_id');
            $table->decimal('price_override', 10, 2)->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['upsell_id', 'object_model', 'object_id'], 'upsell_service_unique');
            $table->index(['vendor_id', 'object_model', 'object_id'], 'upsell_service_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_upsell_services');
        Schema::table('bc_vendor_upsells', function (Blueprint $table) {
            $table->dropColumn(['category', 'short_description', 'image_id', 'is_featured', 'is_global']);
        });
    }
};
