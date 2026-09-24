<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Models\VendorUpsellService;
use Tests\TestCase;

/**
 * WP1: what an add-on costs on each price type, and what a service offers (its own
 * add-ons and the global ones, at the right price, in the right order), isolated
 * per vendor. Runs on an isolated throwaway MySQL DB, never the real portal data.
 */
class UpsellCatalogTest extends TestCase
{
    private const A = 101;
    private const B = 202;

    protected function setUp(): void
    {
        parent::setUp();

        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');

        Schema::dropIfExists('bc_vendor_upsell_services');
        Schema::dropIfExists('bc_vendor_upsells');
        Schema::create('bc_vendor_upsells', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('name');
            $t->string('category', 30)->default('service');
            $t->text('description')->nullable();
            $t->string('short_description')->nullable();
            $t->unsignedBigInteger('image_id')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_global')->default(false);
            $t->decimal('price', 10, 2)->default(0);
            $t->string('price_type', 20)->default('per_booking');
            $t->integer('sort_order')->default(0);
            $t->string('status', 20)->default('publish');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('bc_vendor_upsell_services', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('upsell_id');
            $t->string('object_model', 30);
            $t->unsignedBigInteger('object_id');
            $t->decimal('price_override', 10, 2)->nullable();
            $t->boolean('is_highlighted')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bc_vendor_upsell_services');
        Schema::dropIfExists('bc_vendor_upsells');
        parent::tearDown();
    }

    private function as(int $vendor): void
    {
        $u = new User();
        $u->id = $vendor;
        $this->actingAs($u);
    }

    private function make(array $over = []): VendorUpsell
    {
        return VendorUpsell::create($over + ['name' => 'Add-on', 'price' => 10, 'price_type' => 'per_booking', 'status' => 'publish']);
    }

    // Pricing --------------------------------------------------------------

    public function test_each_price_type_counts_what_it_says(): void
    {
        $u = fn (string $type) => new VendorUpsell(['price' => 25, 'price_type' => $type]);

        // 3 guests, 2 nights, 3 days, asked for once
        $this->assertSame(25.0, $u('per_booking')->computeTotal(1, 3, 2, 3));
        $this->assertSame(75.0, $u('per_person')->computeTotal(1, 3, 2, 3));
        $this->assertSame(50.0, $u('per_night')->computeTotal(1, 3, 2, 3));
        $this->assertSame(75.0, $u('per_day')->computeTotal(1, 3, 2, 3));
        $this->assertSame(25.0, $u('per_item')->computeTotal(1, 3, 2, 3));
        // per item is what the quantity is for; the others multiply it too
        $this->assertSame(100.0, $u('per_item')->computeTotal(4, 3, 2, 3));
        $this->assertSame(150.0, $u('per_person')->computeTotal(2, 3, 2, 3));
    }

    public function test_a_service_specific_price_replaces_the_standard_one(): void
    {
        $u = new VendorUpsell(['price' => 45, 'price_type' => 'per_person']);
        $this->assertSame(90.0, $u->computeTotal(1, 2, 1, 1));
        $this->assertSame(78.0, $u->computeTotal(1, 2, 1, 1, 39.0));
    }

    public function test_nothing_multiplies_by_less_than_one(): void
    {
        $u = new VendorUpsell(['price' => 10, 'price_type' => 'per_person']);
        $this->assertSame(10.0, $u->computeTotal(0, 0, 0, 0));
    }

    public function test_the_price_reads_the_way_a_guest_would_say_it(): void
    {
        $this->assertSame('$45 pp', (new VendorUpsell(['price' => 45, 'price_type' => 'per_person']))->priceLabel());
        $this->assertSame('$12.5/night', (new VendorUpsell(['price' => 12.5, 'price_type' => 'per_night']))->priceLabel());
        $this->assertSame('$30', (new VendorUpsell(['price' => 30, 'price_type' => 'per_booking']))->priceLabel());
        $this->assertSame('$8/item', (new VendorUpsell(['price' => 8, 'price_type' => 'per_item']))->priceLabel());
        $this->assertSame('$39 pp', (new VendorUpsell(['price' => 45, 'price_type' => 'per_person']))->priceLabel(39));
    }

    // What a service offers --------------------------------------------------

    public function test_a_service_offers_its_own_add_ons_and_the_global_ones(): void
    {
        $this->as(self::A);
        $everywhere = $this->make(['name' => 'Transfer', 'is_global' => true]);
        $mine = $this->make(['name' => 'Photos']);
        $other = $this->make(['name' => 'Not here']);
        VendorUpsellService::create(['upsell_id' => $mine->id, 'object_model' => 'tour', 'object_id' => 29]);
        VendorUpsellService::create(['upsell_id' => $other->id, 'object_model' => 'tour', 'object_id' => 30]);

        $names = VendorUpsell::offeredOn('tour', 29)->map(fn ($o) => $o['upsell']->name)->all();
        sort($names);
        $this->assertSame(['Photos', 'Transfer'], $names);
        // Another kind of service with the same number is a different service.
        $this->assertSame(['Transfer'], VendorUpsell::offeredOn('hotel', 29)->map(fn ($o) => $o['upsell']->name)->all());
    }

    public function test_hidden_add_ons_are_not_offered(): void
    {
        $this->as(self::A);
        $this->make(['name' => 'Hidden', 'is_global' => true, 'status' => 'draft']);
        $this->make(['name' => 'Shown', 'is_global' => true]);

        $this->assertSame(['Shown'], VendorUpsell::offeredOn('tour', 1)->map(fn ($o) => $o['upsell']->name)->all());
    }

    public function test_the_price_on_a_service_is_its_own_when_it_has_one(): void
    {
        $this->as(self::A);
        $u = $this->make(['name' => 'Photos', 'price' => 45, 'price_type' => 'per_person']);
        VendorUpsellService::create(['upsell_id' => $u->id, 'object_model' => 'tour', 'object_id' => 2, 'price_override' => 39]);
        VendorUpsellService::create(['upsell_id' => $u->id, 'object_model' => 'tour', 'object_id' => 3]);

        $this->assertSame(39.0, VendorUpsell::offeredOn('tour', 2)->first()['price']);
        $this->assertSame(45.0, VendorUpsell::offeredOn('tour', 3)->first()['price'], 'no override: the standard price');
    }

    public function test_highlighted_lead_then_featured_then_the_vendors_order(): void
    {
        $this->as(self::A);
        $plain2 = $this->make(['name' => 'B plain', 'is_global' => true, 'sort_order' => 2]);
        $plain1 = $this->make(['name' => 'A plain', 'is_global' => true, 'sort_order' => 1]);
        $featured = $this->make(['name' => 'Featured', 'is_global' => true, 'is_featured' => true, 'sort_order' => 9]);
        $starred = $this->make(['name' => 'Highlighted', 'sort_order' => 9]);
        VendorUpsellService::create(['upsell_id' => $starred->id, 'object_model' => 'tour', 'object_id' => 5, 'is_highlighted' => true]);

        $order = VendorUpsell::offeredOn('tour', 5)->map(fn ($o) => $o['upsell']->name)->all();
        $this->assertSame(['Highlighted', 'Featured', 'A plain', 'B plain'], $order);
        $this->assertTrue(VendorUpsell::offeredOn('tour', 5)->first()['highlighted']);
    }

    public function test_one_vendor_never_sees_or_offers_anothers(): void
    {
        $this->as(self::A);
        $this->make(['name' => 'A only', 'is_global' => true]);

        $this->as(self::B);
        $this->assertCount(0, VendorUpsell::offeredOn('tour', 1));
        $this->make(['name' => 'B only', 'is_global' => true]);
        $this->assertSame(['B only'], VendorUpsell::offeredOn('tour', 1)->map(fn ($o) => $o['upsell']->name)->all());
    }

    public function test_where_it_is_offered_reads_plainly(): void
    {
        $this->as(self::A);
        $g = $this->make(['is_global' => true]);
        $none = $this->make();
        $two = $this->make();
        VendorUpsellService::create(['upsell_id' => $two->id, 'object_model' => 'tour', 'object_id' => 1]);
        VendorUpsellService::create(['upsell_id' => $two->id, 'object_model' => 'tour', 'object_id' => 2]);

        $this->assertSame('Everywhere', $g->offeredLabel());
        $this->assertSame('Nowhere yet', $none->offeredLabel());
        $this->assertSame('2 services', $two->offeredLabel());
    }
}
