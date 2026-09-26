<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A piece of a service's text can be translated on purpose and still read the same (a hotel's name, a loan word). The language dashboard
 * used to count a piece as translated only when it differed from the original, so those could never be finished. This records them.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_translation_marks', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 12);            // hotel, tour, space, car, boat, event, room
            $t->unsignedBigInteger('origin_id');
            $t->string('locale', 12);
            $t->string('piece', 150);          // a field name, or "field:path" inside a list
            $t->timestamps();
            $t->unique(['kind', 'origin_id', 'locale', 'piece'], 'vendor_translation_marks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_translation_marks');
    }
};
