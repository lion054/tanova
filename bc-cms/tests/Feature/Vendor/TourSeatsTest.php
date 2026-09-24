<?php

namespace Tests\Feature\Vendor;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Services\TourSeats;
use Tests\TestCase;

/**
 * WP4: seats on a tour on a day. Capacity from the departure or the tour, what is
 * gone (paid, in progress, confirmed), what is held by an unpaid booking for a
 * while, and what is free again. Isolated throwaway DB.
 */
class TourSeatsTest extends TestCase
{
    private array $tables = ['bc_bookings', 'bc_tour_dates', 'bc_tours'];

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        Carbon::setTestNow('2026-10-01 10:00:00');

        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('author_id')->nullable();
            $t->integer('max_people')->nullable();
            $t->string('title')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_tour_dates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('target_id')->nullable();
            $t->timestamp('start_date')->nullable();
            $t->timestamp('end_date')->nullable();
            $t->decimal('price', 12, 2)->nullable();
            $t->unsignedSmallInteger('max_guests')->nullable();
            $t->tinyInteger('active')->default(0);
            $t->timestamps();
        });
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable();
            $t->dateTime('start_date')->nullable();
            $t->integer('total_guests')->default(0);
            $t->string('status')->default('unpaid');
            $t->softDeletes();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function tour(?int $max = 20): Tour
    {
        // Inserted directly: saving a Tour would also make its slug, translations and so on.
        $id = DB::table('bc_tours')->insertGetId(['author_id' => 7, 'max_people' => $max, 'title' => 'Falls', 'created_at' => now(), 'updated_at' => now()]);

        return Tour::find($id);
    }

    private function departure(Tour $t, string $date, int $capacity, bool $active = true): void
    {
        DB::table('bc_tour_dates')->insert([
            'target_id' => $t->id, 'start_date' => "$date 00:00:00", 'end_date' => "$date 00:00:00",
            'max_guests' => $capacity, 'active' => $active ? 1 : 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function book(Tour $t, string $date, int $guests, string $status = 'paid', ?string $touched = null): int
    {
        return DB::table('bc_bookings')->insertGetId([
            'object_model' => 'tour', 'object_id' => $t->id, 'start_date' => "$date 00:00:00",
            'total_guests' => $guests, 'status' => $status,
            'created_at' => $touched ?? now(), 'updated_at' => $touched ?? now(),
        ]);
    }

    private function seats(): TourSeats
    {
        return new TourSeats();
    }

    // Capacity -------------------------------------------------------------------

    public function test_a_departures_own_capacity_beats_the_tours_usual_one(): void
    {
        $t = $this->tour(20);
        $this->departure($t, '2026-11-05', 8);

        $this->assertSame(8, $this->seats()->capacity($t, '2026-11-05'));
        $this->assertSame(20, $this->seats()->capacity($t, '2026-11-06'), 'another day: the usual capacity');
    }

    public function test_a_closed_departure_falls_back_to_the_usual_capacity(): void
    {
        $t = $this->tour(20);
        $this->departure($t, '2026-11-05', 8, active: false);
        $this->assertSame(20, $this->seats()->capacity($t, '2026-11-05'));
    }

    public function test_no_capacity_anywhere_means_no_limit_not_sold_out(): void
    {
        $t = $this->tour(null);
        $this->assertNull($this->seats()->capacity($t, '2026-11-05'));
        $this->assertNull($this->seats()->remaining($t, '2026-11-05'));
    }

    public function test_a_range_covers_every_day_in_it_and_the_days_own_row_wins(): void
    {
        $t = $this->tour(20);
        DB::table('bc_tour_dates')->insert(['target_id' => $t->id, 'start_date' => '2026-12-01 00:00:00', 'end_date' => '2026-12-31 00:00:00', 'max_guests' => 12, 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->departure($t, '2026-12-25', 6);

        $this->assertSame(12, $this->seats()->capacity($t, '2026-12-10'));
        $this->assertSame(6, $this->seats()->capacity($t, '2026-12-25'));
    }

    public function test_a_departure_can_hold_more_than_a_tinyint_allows(): void
    {
        $t = $this->tour(20);
        $this->departure($t, '2026-11-05', 400);
        $this->assertSame(400, $this->seats()->capacity($t, '2026-11-05'));
    }

    // Seats gone -----------------------------------------------------------------

    public function test_seats_fill_as_people_book_and_run_out(): void
    {
        $t = $this->tour(20);
        $this->assertSame(20, $this->seats()->remaining($t, '2026-11-05'));
        $this->book($t, '2026-11-05', 8);
        $this->assertSame(12, $this->seats()->remaining($t, '2026-11-05'));
        $this->book($t, '2026-11-05', 12, 'confirmed');
        $this->assertSame(0, $this->seats()->remaining($t, '2026-11-05'));
        $this->book($t, '2026-11-05', 5);
        $this->assertSame(0, $this->seats()->remaining($t, '2026-11-05'), 'never below zero');
    }

    public function test_each_day_and_each_tour_has_its_own_seats(): void
    {
        $a = $this->tour(10);
        $b = $this->tour(10);
        $this->book($a, '2026-11-05', 10);

        $this->assertSame(0, $this->seats()->remaining($a, '2026-11-05'));
        $this->assertSame(10, $this->seats()->remaining($a, '2026-11-06'));
        $this->assertSame(10, $this->seats()->remaining($b, '2026-11-05'));
    }

    public function test_a_cancelled_booking_gives_its_seats_back(): void
    {
        $t = $this->tour(10);
        $id = $this->book($t, '2026-11-05', 6);
        $this->assertSame(4, $this->seats()->remaining($t, '2026-11-05'));
        DB::table('bc_bookings')->where('id', $id)->update(['status' => 'cancelled']);
        $this->assertSame(10, $this->seats()->remaining($t, '2026-11-05'));
    }

    public function test_every_way_of_paying_holds_seats(): void
    {
        $t = $this->tour(50);
        foreach (['paid', 'partial_payment', 'processing', 'confirmed', 'completed'] as $s) {
            $this->book($t, '2026-11-05', 2, $s);
        }
        $this->assertSame(40, $this->seats()->remaining($t, '2026-11-05'));
    }

    // The hold -------------------------------------------------------------------

    public function test_an_unpaid_booking_holds_its_seats_for_a_while_then_lets_go(): void
    {
        $t = $this->tour(10);
        $this->book($t, '2026-11-05', 4, 'unpaid', now()->subMinutes(10));
        $this->assertSame(6, $this->seats()->remaining($t, '2026-11-05'), 'held: someone is paying');
        $this->assertSame(4, $this->seats()->held($t->id, '2026-11-05'));

        $this->book($t, '2026-11-06', 4, 'unpaid', now()->subMinutes(45));
        $this->assertSame(10, $this->seats()->remaining($t, '2026-11-06'), 'the hold ran out: free again');
    }

    public function test_the_last_seats_cannot_be_promised_to_two_people(): void
    {
        $t = $this->tour(10);
        $this->book($t, '2026-11-05', 8, 'paid');
        $this->book($t, '2026-11-05', 2, 'unpaid');   // someone is about to pay for the last two
        $this->assertSame(0, $this->seats()->remaining($t, '2026-11-05'));
    }

    public function test_a_booking_does_not_block_itself_when_it_comes_to_pay(): void
    {
        $t = $this->tour(10);
        $mine = $this->book($t, '2026-11-05', 6, 'unpaid');
        $this->assertSame(4, $this->seats()->remaining($t, '2026-11-05'));
        $this->assertSame(10, $this->seats()->remaining($t, '2026-11-05', $mine));
    }

    public function test_the_hold_length_can_be_set(): void
    {
        $this->assertSame(30, $this->seats()->holdMinutes());
    }

    public function test_seats_by_day_agree_with_seats_for_one_day(): void
    {
        $t = $this->tour(20);
        $this->book($t, '2026-11-05', 8);
        $this->book($t, '2026-11-05', 2, 'unpaid');                                   // held
        $this->book($t, '2026-11-05', 5, 'unpaid', now()->subMinutes(90));            // hold ran out
        $this->book($t, '2026-11-05', 4, 'cancelled');
        $this->book($t, '2026-11-07', 3, 'confirmed');

        $by = $this->seats()->takenByDay($t->id, '2026-11-01', '2026-11-30');
        $this->assertSame(['2026-11-05' => 10, '2026-11-07' => 3], $by);
        foreach ($by as $day => $n) {
            $this->assertSame($n, $this->seats()->taken($t->id, $day));
        }
        $this->assertSame([], $this->seats()->takenByDay($t->id, '2026-12-01', '2026-12-31'));
    }
}
