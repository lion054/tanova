<?php

namespace Tests\Feature\Vendor;

use App\Traits\FiltersManageList;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** The "Manage tours / hotels ..." filters: they narrow the vendor's own rows and offer only the vendor's own options. */
class ManageFiltersTest extends TestCase
{
    private array $tables = ['bc_tours', 'bc_locations', 'bc_tour_category'];
    private object $c;

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
        Schema::create('bc_locations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->nullable(); $t->string('status')->default('publish');
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('bc_tour_category', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->string('slug')->nullable(); $t->string('status')->default('publish');
            $t->decimal('price', 12, 2)->default(0); $t->unsignedBigInteger('location_id')->nullable(); $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('author_id')->nullable(); $t->softDeletes(); $t->timestamps();
        });

        DB::table('bc_locations')->insert([['id' => 1, 'name' => 'Victoria Falls'], ['id' => 2, 'name' => 'Nyanga'], ['id' => 3, 'name' => 'SECRET Elsewhere']]);
        DB::table('bc_tour_category')->insert([['id' => 1, 'name' => 'Adventure'], ['id' => 2, 'name' => 'Culture'], ['id' => 3, 'name' => 'Other vendor category']]);
        $rows = [
            // vendor 7
            ['Vic Falls Quad Bike Tour', 'publish', 60, 1, 1, 7],
            ['Tandem Gorge Swing', 'publish', 200, 1, 1, 7],
            ['Village Tour, Livingstone', 'draft', 40, 1, 2, 7],
            ['Guided Hike', 'publish', 10, 2, 2, 7],
            ['50% Sunset Cruise', 'publish', 100, 2, 1, 7],
            // vendor 8: must never appear or be offered
            ['Vic Falls Helicopter', 'publish', 999, 3, 3, 8],
        ];
        foreach ($rows as $i => [$title, $st, $price, $loc, $cat, $v]) {
            DB::table('bc_tours')->insert(['title' => $title, 'status' => $st, 'price' => $price, 'location_id' => $loc, 'category_id' => $cat, 'author_id' => $v, 'created_at' => now()->subDays(10 - $i), 'updated_at' => now()->subDays(10 - $i)]);
        }
        $this->c = new class { use FiltersManageList; public function run(Request $r, $q, array $o) { return $this->manageFilters($r, $q, $o); } };
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function run7(string $qs): array
    {
        $q = \Modules\Tour\Models\Tour::where('author_id', 7);
        [$list, $fb, $per] = $this->c->run(Request::create('/user/tour' . $qs), $q, ['table' => 'bc_tours', 'noun' => 'tours', 'status' => true, 'price' => true, 'category' => ['table' => 'bc_tour_category']]);

        return [$list->paginate($per)->pluck('title')->all(), $fb];
    }

    public function test_no_filters_lists_all_of_this_vendors_rows_newest_first_and_never_another_vendors(): void
    {
        [$titles, $fb] = $this->run7('');

        $this->assertCount(5, $titles);
        $this->assertSame('50% Sunset Cruise', $titles[0]);
        $this->assertNotContains('Vic Falls Helicopter', $titles);
        $this->assertSame([5, 5], [$fb['total']['matching'], $fb['total']['all']]);
    }

    public function test_search_needs_every_word_in_any_order_and_treats_percent_literally(): void
    {
        $this->assertSame(['Vic Falls Quad Bike Tour'], $this->run7('?s=quad+vic')[0]);
        $this->assertSame(['50% Sunset Cruise'], $this->run7('?s=50%25')[0]);
        $this->assertSame([], $this->run7('?s=helicopter')[0], "another vendor's tour is not findable");
        $this->assertSame([], $this->run7('?s=' . urlencode("' OR 1=1 --"))[0]);
        $this->assertCount(5, $this->run7('?s=')[0]);
    }

    public function test_a_number_finds_the_row_by_its_id(): void
    {
        $id = DB::table('bc_tours')->where('title', 'Guided Hike')->value('id');

        $this->assertSame(['Guided Hike'], $this->run7('?s=' . $id)[0]);
    }

    public function test_status_location_and_category_narrow_the_list(): void
    {
        $this->assertSame(['Village Tour, Livingstone'], $this->run7('?status=draft')[0]);
        $this->assertCount(3, $this->run7('?location=1')[0]);
        $this->assertSame(['50% Sunset Cruise', 'Tandem Gorge Swing', 'Vic Falls Quad Bike Tour'], collect($this->run7('?category=1&sort=title')[0])->sort()->values()->all());
        $this->assertSame(['Tandem Gorge Swing'], $this->run7('?location=1&category=1&status=publish&s=swing')[0]);
    }

    public function test_only_this_vendors_locations_and_categories_are_offered_and_others_are_ignored(): void
    {
        [, $fb] = $this->run7('?location=3&category=3');

        $loc = collect($fb['selects'])->firstWhere('name', 'location');
        $cat = collect($fb['selects'])->firstWhere('name', 'category');
        $this->assertSame(['2' => 'Nyanga', '1' => 'Victoria Falls'], $loc['options'], 'alphabetical, and only this vendor\'s');
        $this->assertSame(['1' => 'Adventure', '2' => 'Culture'], $cat['options']);
        $this->assertSame(0, $fb['active'], "asking for another vendor's location or category does nothing");
        $this->assertCount(5, $this->run7('?location=3&category=3')[0]);
    }

    public function test_sorting_and_page_size(): void
    {
        $this->assertSame(['Tandem Gorge Swing', '50% Sunset Cruise'], array_slice($this->run7('?sort=price_desc')[0], 0, 2));
        $this->assertSame('Guided Hike', $this->run7('?sort=price_asc')[0][0]);
        $this->assertSame('50% Sunset Cruise', $this->run7('?sort=title')[0][0]);
        $this->assertSame('Vic Falls Quad Bike Tour', $this->run7('?sort=oldest')[0][0]);
        $this->assertCount(5, $this->run7('?sort=hack')[0], 'an unknown sort falls back, it does not break');
        $this->assertSame(20, $this->run7('?per_page=9999')[1]['per_page_value']);
    }

    public function test_the_count_says_how_many_match_out_of_how_many_there_are(): void
    {
        [, $fb] = $this->run7('?status=publish&location=1');

        $this->assertSame([2, 5], [$fb['total']['matching'], $fb['total']['all']]);
        $this->assertSame(2, $fb['active']);
    }
}
