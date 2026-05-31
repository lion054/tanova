<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_allowed_origins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('origin', 255); // e.g. https://dare2travel.com
            $table->timestamps();

            $table->unique(['vendor_id', 'origin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_allowed_origins');
    }
};
