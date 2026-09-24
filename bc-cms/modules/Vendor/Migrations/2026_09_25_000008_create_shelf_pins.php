<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A vendor's own picks for the Trending and Bestsellers shelves; they go first, ahead of the ranking. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_shelf_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('shelf', 12);                    // trending | bestseller
            $table->string('object_model', 40)->default('tour');
            $table->unsignedBigInteger('object_id');
            $table->timestamps();
            $table->unique(['vendor_id', 'shelf', 'object_model', 'object_id'], 'shelf_pin_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_shelf_pins');
    }
};
