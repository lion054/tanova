<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bc_vendor_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 64)->nullable()->unique();  // null after one-time reveal
            $table->string('key_hash', 128)->unique();        // hmac-sha256 for lookup
            $table->integer('rate_limit')->default(10000); // monthly request cap
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bc_vendor_api_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_api_key_id')->constrained('bc_vendor_api_keys')->cascadeOnDelete();
            $table->string('endpoint', 255);
            $table->string('method', 10)->default('GET');
            $table->smallInteger('status_code');
            $table->integer('response_time_ms')->default(0);
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_vendor_api_usage');
        Schema::dropIfExists('bc_vendor_api_keys');
    }
};
