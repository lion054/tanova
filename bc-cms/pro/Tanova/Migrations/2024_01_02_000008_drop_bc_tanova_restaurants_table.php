<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropBcTanovaRestaurantsTable extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bc_tanova_restaurants');
    }

    public function down(): void
    {
        Schema::create('bc_tanova_restaurants', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('location_id')->index();
            $table->unsignedInteger('tsokanew_id')->nullable()->unique();
            $table->string('name', 255);
            $table->text('about')->nullable();
            $table->string('offered_by', 255)->nullable();
            $table->string('open_time', 20)->nullable();
            $table->string('close_time', 20)->nullable();
            $table->string('image', 255)->nullable();
            $table->tinyInteger('time_slot')->default(3);
            $table->text('url')->nullable();
            $table->string('status', 30)->default('publish')->index();
            $table->timestamps();
        });
    }
}
