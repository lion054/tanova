<?php

namespace Tests\Feature\Vendor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Models\VendorServiceTier;
use Modules\Vendor\Services\ServiceTiers;
use Tests\TestCase;

/** WP2: what a tier costs for a party, who it takes, and what guests are shown. */
class ServiceTiersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        Schema::dropIfExists('bc_vendor_service_tiers');
        Schema::create('bc_vendor_service_tiers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('object_model', 40)->default('tour');
            $t->unsignedBigInteger('object_id');
            $t->string('tier_key', 20);
            $t->string('name', 80);
            $t->string('tagline', 160)->nullable();
            $t->text('description')->nullable();
            $t->decimal('price', 12, 2)->default(0);
            $t->boolean('price_per_person')->default(true);
            $t->unsignedSmallInteger('min_guests')->nullable();
            $t->unsignedSmallInteger('max_guests')->nullable();
            $t->json('bands')->nullable();
            $t->json('inclusions')->nullable();
            $t->json('included_upsell_ids')->nullable();
            $t->boolean('recommended')->default(false);
            $t->boolean('active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['vendor_id', 'object_model', 'object_id', 'tier_key'], 'svc_tier_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bc_vendor_service_tiers');
        parent::tearDown();
    }

    private function tier(array $over = []): VendorServiceTier
    {
        return VendorServiceTier::withoutVendorScope()->create($over + [
            'vendor_id' => 7, 'object_id' => 1, 'tier_key' => 'classic', 'name' => 'Classic', 'price' => 100, 'price_per_person' => true,
        ]);
    }

    public function test_per_person_price_is_multiplied_by_the_party(): void
    {
        $r = (new ServiceTiers())->priceFor($this->tier(), 3);

        $this->assertSame(300.0, $r['total']);
        $this->assertSame('per_person', $r['basis']);
    }

    public function test_a_group_price_is_one_total_for_any_party(): void
    {
        $t = $this->tier(['price' => 1500, 'price_per_person' => false]);

        $this->assertSame(1500.0, (new ServiceTiers())->priceFor($t, 2)['total']);
        $this->assertSame(1500.0, (new ServiceTiers())->priceFor($t, 6)['total']);
    }

    public function test_a_band_wins_over_the_per_person_price_only_inside_its_range(): void
    {
        $t = $this->tier(['bands' => [['min' => 4, 'max' => 6, 'total' => 320], ['min' => 7, 'max' => null, 'total' => 500]]]);
        $s = new ServiceTiers();

        $this->assertSame(200.0, $s->priceFor($t, 2)['total']);          // below the bands: 2 x 100
        $this->assertSame(['total' => 320.0, 'basis' => 'band'], $s->priceFor($t, 4));
        $this->assertSame(320.0, $s->priceFor($t, 6)['total']);
        $this->assertSame(500.0, $s->priceFor($t, 12)['total']);         // open-ended band
    }

    public function test_party_size_limits(): void
    {
        $t = $this->tier(['min_guests' => 2, 'max_guests' => 8]);
        $s = new ServiceTiers();

        $this->assertFalse($s->accepts($t, 1));
        $this->assertTrue($s->accepts($t, 2));
        $this->assertTrue($s->accepts($t, 8));
        $this->assertFalse($s->accepts($t, 9));
        $this->assertTrue($s->accepts($this->tier(['tier_key' => 'signature']), 50)); // no limits set
    }

    public function test_from_price_compares_like_with_like(): void
    {
        $s = new ServiceTiers();

        // $190 a head, or $640 for 4 to 6 people (about $107 a head): the band is cheaper per person.
        $withBand = $this->tier(['price' => 190, 'bands' => [['min' => 4, 'max' => 6, 'total' => 640]]]);
        $this->assertSame(['amount' => 106.67, 'per' => 'person'], $s->from($withBand));

        $plain = $this->tier(['tier_key' => 'signature', 'price' => 190]);
        $this->assertSame(['amount' => 190.0, 'per' => 'person'], $s->from($plain));

        $group = $this->tier(['tier_key' => 'sublime', 'price' => 2400, 'price_per_person' => false]);
        $this->assertSame(['amount' => 2400.0, 'per' => 'group'], $s->from($group));
    }

    public function test_bad_bands_are_ignored_and_sorted(): void
    {
        $t = $this->tier(['bands' => [['min' => 7, 'max' => null, 'total' => 500], ['min' => 0, 'total' => 50], ['min' => 2, 'max' => 3, 'total' => 0], ['min' => 4, 'max' => 6, 'total' => 320]]]);

        $this->assertSame([4, 7], array_column((new ServiceTiers())->bands($t), 'min'));
    }

    public function test_the_api_shape_says_whether_a_party_fits_and_what_it_costs(): void
    {
        $t = $this->tier(['min_guests' => 2, 'max_guests' => 4, 'inclusions' => ['Guide']]);
        $s = new ServiceTiers();

        $fits = $s->shape($t, 3);
        $this->assertTrue($fits['available_for_party']);
        $this->assertSame(300.0, $fits['total_for_party']);
        $this->assertFalse($s->shape($t, 9)['available_for_party']);
        $this->assertArrayNotHasKey('available_for_party', $s->shape($t));
        $this->assertSame(['Guide'], $fits['inclusions']);
    }
}
