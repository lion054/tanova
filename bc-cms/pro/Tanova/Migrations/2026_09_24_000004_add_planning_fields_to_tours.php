<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The fields the LuxSav app plans a day with, which bc_tours never had:
 * when an activity usually starts, how physical it is, the youngest age, its
 * timed steps, and for packages how many nights, what board, and the day by
 * day plan. All optional. Where they are empty the API works them out from the
 * activity (see ActivityPlanning), so a vendor only fills in what they know.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tours', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tours', 'start_time')) {
                $t->string('start_time', 5)->nullable()->after('time_slot');   // "08:30"
            }
            if (!Schema::hasColumn('bc_tours', 'thrill')) {
                $t->string('thrill', 12)->nullable()->after('zone');           // easy | moderate | thrill
            }
            if (!Schema::hasColumn('bc_tours', 'min_age')) {
                $t->unsignedTinyInteger('min_age')->nullable()->after('thrill');
            }
            if (!Schema::hasColumn('bc_tours', 'stages')) {
                $t->json('stages')->nullable();                                // [{at, title, detail}]
            }
            if (!Schema::hasColumn('bc_tours', 'package_nights')) {
                $t->unsignedTinyInteger('package_nights')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'package_board')) {
                $t->string('package_board', 20)->nullable();                   // full_board | half_board | bed_and_breakfast
            }
            if (!Schema::hasColumn('bc_tours', 'package_itinerary')) {
                $t->json('package_itinerary')->nullable();                     // [{day, title, stops:[{at,title,detail,kind,service}]}]
            }
        });
    }

    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $t) {
            foreach (['start_time', 'thrill', 'min_age', 'stages', 'package_nights', 'package_board', 'package_itinerary'] as $c) {
                if (Schema::hasColumn('bc_tours', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
