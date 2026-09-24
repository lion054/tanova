<?php

namespace Tests\Feature\Vendor;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Services\Occupancy;
use Modules\Vendor\Services\TourSeats;
use Tests\TestCase;

/** WP6: occupancy is seats sold on the days that are running, not diluted by empty days. */
class OccupancyTest extends TestCase
{
    private array $tables = ['bc_tour_dates', 'bc_bookings', 'bc_tours'];
    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->nullable();
            $t->integer('max_people')->nullable();
            $t->string('status')->default('publish');
            $t->unsignedBigInteger('author_id')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_tour_dates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('target_id');
            $t->dateTime('start_date');
            $t->dateTime('end_date');
            $t->integer('max_guests')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable();
            $t->integer('total_guests')->default(1);
            $t->string('status')->default('confirmed');
            $t->dateTime('start_date')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        $this->today = Carbon::parse('2026-10-01');
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function tour(int $id, string $title, ?int $cap): void
    {
        DB::table('bc_tours')->insert(['id' => $id, 'title' => $title, 'max_people' => $cap, 'author_id' => 7, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function book(int $tour, string $day, int $guests, string $status = 'confirmed'): void
    {
        DB::table('bc_bookings')->insert(['vendor_id' => 7, 'object_model' => 'tour', 'object_id' => $tour, 'total_guests' => $guests, 'status' => $status, 'start_date' => $day . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function occupancy(): array
    {
        return (new Occupancy(new TourSeats()))->forVendor(7, 30, $this->today);
    }

    public function test_only_running_days_count(): void
    {
        $this->tour(1, 'Gorge swing', 10);
        $this->book(1, '2026-10-05', 8);
        $this->book(1, '2026-10-06', 4);

        $o = $this->occupancy();

        $this->assertSame(12, $o['sold']);
        $this->assertSame(20, $o['capacity']);  // two running days of 10, not thirty
        $this->assertSame(60, $o['percent']);
    }

    public function test_a_listed_departure_with_nobody_booked_counts_as_empty_seats(): void
    {
        $this->tour(1, 'Gorge swing', 10);
        $this->book(1, '2026-10-05', 5);
        DB::table('bc_tour_dates')->insert(['target_id' => 1, 'start_date' => '2026-10-12 00:00:00', 'end_date' => '2026-10-12 00:00:00', 'max_guests' => 20, 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $o = $this->occupancy();

        $this->assertSame(5, $o['sold']);
        $this->assertSame(30, $o['capacity']); // 10 on the booked day + the departure's own 20
    }

    public function test_unpaid_bookings_and_days_outside_the_window_do_not_count(): void
    {
        $this->tour(1, 'Gorge swing', 10);
        $this->book(1, '2026-10-05', 3);
        $this->book(1, '2026-10-06', 9, 'cancelled');
        $this->book(1, '2026-12-25', 9); // beyond 30 days

        $o = $this->occupancy();

        $this->assertSame(3, $o['sold']);
        $this->assertSame(10, $o['capacity']);
    }

    public function test_tours_without_a_capacity_are_left_out_and_nothing_gives_no_percentage(): void
    {
        $this->tour(1, 'No limit', null);
        $this->book(1, '2026-10-05', 12);

        $o = $this->occupancy();

        $this->assertSame([], $o['tours']);
        $this->assertNull($o['percent']);
    }

    public function test_the_fullest_tours_come_first(): void
    {
        $this->tour(1, 'Half', 10); $this->tour(2, 'Nearly full', 10);
        $this->book(1, '2026-10-05', 5);
        $this->book(2, '2026-10-05', 9);

        $this->assertSame(['Nearly full', 'Half'], array_column($this->occupancy()['tours'], 'title'));
    }
}
