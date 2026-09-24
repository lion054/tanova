<?php

namespace Tests\Feature\Vendor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Services\Shelves;
use Tests\TestCase;

/** WP7: shelves are ranked from real bookings, the vendor's pins go first, and other vendors never leak in. */
class ShelvesTest extends TestCase
{
    private array $tables = ['bc_vendor_shelf_pins', 'bc_bookings', 'bc_tours'];

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        $this->drop();

        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->nullable();
            $t->string('status')->default('publish');
            $t->decimal('review_score', 4, 1)->nullable();
            $t->unsignedBigInteger('author_id')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable();
            $t->string('status')->default('confirmed');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_vendor_shelf_pins', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('shelf', 12);
            $t->string('object_model', 40)->default('tour');
            $t->unsignedBigInteger('object_id');
            $t->timestamps();
            $t->unique(['vendor_id', 'shelf', 'object_model', 'object_id'], 'shelf_pin_unique');
        });
    }

    protected function tearDown(): void
    {
        $this->drop();
        parent::tearDown();
    }

    private function drop(): void
    {
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
    }

    private function tour(int $id, string $title, ?float $score = null, int $vendor = 7, string $status = 'publish'): void
    {
        DB::table('bc_tours')->insert(['id' => $id, 'title' => $title, 'review_score' => $score, 'author_id' => $vendor, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function sell(int $tour, int $n, int $daysAgo = 0, string $status = 'confirmed', int $vendor = 7): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('bc_bookings')->insert(['vendor_id' => $vendor, 'object_model' => 'tour', 'object_id' => $tour, 'status' => $status, 'created_at' => now()->subDays($daysAgo), 'updated_at' => now()]);
        }
    }

    private function titles(string $shelf, int $limit = 10): array
    {
        return (new Shelves())->shelf($shelf, 7, $limit)->map(fn ($r) => $r['tour']->title)->all();
    }

    public function test_bestsellers_rank_by_all_time_bookings(): void
    {
        $this->tour(1, 'Old favourite'); $this->tour(2, 'Newcomer');
        $this->sell(1, 10, 200);
        $this->sell(2, 3, 1);

        $this->assertSame(['Old favourite', 'Newcomer'], $this->titles('bestseller'));
    }

    public function test_trending_favours_recent_bookings_over_old_ones(): void
    {
        $this->tour(1, 'Old favourite'); $this->tour(2, 'Newcomer');
        $this->sell(1, 10, 200);   // sold well long ago: not trending at all
        $this->sell(2, 3, 1);

        $this->assertSame(['Newcomer'], $this->titles('trending'));
    }

    public function test_the_last_week_counts_double(): void
    {
        $this->tour(1, 'Steady'); $this->tour(2, 'Picking up');
        $this->sell(1, 4, 20);      // 4 in the month, none this week  => 4
        $this->sell(2, 3, 2);       // 3 in the month, 3 this week     => 6

        $this->assertSame(['Picking up', 'Steady'], $this->titles('trending'));
    }

    public function test_ties_go_to_the_better_reviewed_tour(): void
    {
        $this->tour(1, 'Fine', 3.5); $this->tour(2, 'Loved', 4.9);
        $this->sell(1, 2, 3); $this->sell(2, 2, 3);

        $this->assertSame(['Loved', 'Fine'], $this->titles('trending'));
    }

    public function test_unpaid_and_cancelled_bookings_do_not_count(): void
    {
        $this->tour(1, 'Only unpaid');
        $this->sell(1, 5, 1, 'unpaid'); $this->sell(1, 2, 1, 'cancelled');

        $this->assertSame([], $this->titles('trending'));
        $this->assertSame([], $this->titles('bestseller'));
    }

    public function test_pins_go_first_even_with_no_bookings_and_can_be_removed(): void
    {
        $this->tour(1, 'Busy'); $this->tour(2, 'Our pick');
        $this->sell(1, 9, 1);
        $s = new Shelves();

        $s->pin('trending', 7, 2, true);
        $s->pin('trending', 7, 2, true); // twice is fine
        $rows = $s->shelf('trending', 7);
        $this->assertSame(['Our pick', 'Busy'], $rows->map(fn ($r) => $r['tour']->title)->all());
        $this->assertTrue($rows[0]['pinned']);
        $this->assertSame('Picked by us', $rows[0]['reason']);
        $this->assertSame(['Busy'], $this->titles('bestseller')); // a trending pin does not touch bestsellers

        $s->pin('trending', 7, 2, false);
        $this->assertSame(['Busy'], $this->titles('trending'));
    }

    public function test_only_this_vendors_published_tours_appear_and_the_limit_holds(): void
    {
        $this->tour(1, 'Mine'); $this->tour(2, 'Draft', null, 7, 'draft'); $this->tour(3, 'Theirs', null, 8);
        $this->tour(4, 'Second');
        $this->sell(1, 3, 1); $this->sell(2, 9, 1); $this->sell(3, 9, 1, 'confirmed', 8); $this->sell(4, 1, 1);

        $this->assertSame(['Mine', 'Second'], $this->titles('trending'));
        $this->assertSame(['Mine'], $this->titles('trending', 1));
    }
}
