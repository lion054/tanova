<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBcTanovaAccommodationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_accommodations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('location_id')->index();
            $table->unsignedInteger('tsokanew_id')->nullable()->unique();
            $table->string('name', 255);
            $table->enum('stay_type', ['room', 'apartment'])->default('room');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->text('includes')->nullable();
            $table->string('offered_by', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->decimal('cost_per_night', 10, 2)->default(0);
            $table->string('status', 30)->default('publish')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_accommodations');
    }
}
