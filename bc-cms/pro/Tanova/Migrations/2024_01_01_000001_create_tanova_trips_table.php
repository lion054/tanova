<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTanovaTripsTable extends Migration
{
    public function up(): void
    {
        Schema::create('bc_tanova_trips', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Who requested it
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Trip identity
            $table->string('title', 255)->nullable();
            $table->string('destination', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('guests')->default(1);
            $table->string('trip_type', 80)->nullable(); // safari, beach, city, etc.

            // The full AI-generated itinerary (JSON array of day objects)
            $table->json('itinerary')->nullable();

            // Pricing snapshot from generation time
            $table->decimal('estimated_price', 12, 2)->nullable();
            $table->string('currency', 8)->default('USD');

            // NoBeds reference once pushed as a booking
            $table->integer('nobeds_order_id')->nullable()->index();
            $table->string('nobeds_room_id')->nullable();

            // Lifecycle:
            //   created → AI trip created, auto-deleted after 24 h if not booked
            //   booked  → moved to Bookings module / pushed to NoBeds calendar
            $table->string('status', 30)->default('created')->index();

            // The GoTrip booking this became (after "Move to Bookings")
            $table->unsignedBigInteger('booking_id')->nullable()->index();

            // Original user prompt that triggered the generation
            $table->text('prompt')->nullable();

            $table->bigInteger('create_user')->nullable();
            $table->bigInteger('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tanova_trips');
    }
}
