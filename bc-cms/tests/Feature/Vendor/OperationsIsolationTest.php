<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Models\VendorPricingTier;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Models\VendorWaitlist;
use Tests\TestCase;

/**
 * Phase 1 + 2 — proves the new vendor "operations" models are tenant-isolated by
 * App\Traits\BelongsToVendor (auto-scoped reads, auto-stamped vendor_id on create).
 *
 * Runs against an isolated throwaway MySQL DB — never the real tsoka_portal data.
 */
class OperationsIsolationTest extends TestCase
{
    private const VENDOR_A = 101;
    private const VENDOR_B = 202;

    protected function setUp(): void
    {
        parent::setUp();

        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config([
            'database.default' => 'mysql_test',
            'database.connections.mysql_test' => $base,
        ]);
        DB::purge('mysql_test');

        foreach (['bc_vendor_pricing_tiers', 'bc_vendor_upsells', 'bc_vendor_waitlist'] as $t) {
            Schema::dropIfExists($t);
        }

        Schema::create('bc_vendor_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('markup_type')->default('percentage');
            $table->decimal('markup_value', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('publish');
            $table->timestamps();
        });
        Schema::create('bc_vendor_upsells', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('price_type')->default('per_booking');
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('publish');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('bc_vendor_waitlist', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('object_model')->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->integer('party_size')->default(1);
            $table->date('preferred_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['bc_vendor_pricing_tiers', 'bc_vendor_upsells', 'bc_vendor_waitlist'] as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function actingAsVendor(int $id): void
    {
        $u = new User();
        $u->id = $id;
        $this->actingAs($u);
    }

    public function test_create_auto_stamps_vendor_across_models(): void
    {
        $this->actingAsVendor(self::VENDOR_A);

        $tier    = VendorPricingTier::create(['name' => 'VIP', 'markup_type' => 'percentage', 'markup_value' => 10]);
        $upsell  = VendorUpsell::create(['name' => 'Transfer', 'price' => 50, 'price_type' => 'per_booking']);
        $waiting = VendorWaitlist::create(['customer_name' => 'Jane', 'party_size' => 2]);

        $this->assertSame(self::VENDOR_A, (int) $tier->vendor_id);
        $this->assertSame(self::VENDOR_A, (int) $upsell->vendor_id);
        $this->assertSame(self::VENDOR_A, (int) $waiting->vendor_id);
    }

    public function test_reads_are_isolated_between_vendors(): void
    {
        $this->actingAsVendor(self::VENDOR_A);
        VendorPricingTier::create(['name' => 'A-Gold', 'markup_type' => 'percentage', 'markup_value' => 5]);
        VendorUpsell::create(['name' => 'A-Spa', 'price' => 20, 'price_type' => 'per_person']);
        VendorWaitlist::create(['customer_name' => 'A-Cust']);

        $this->actingAsVendor(self::VENDOR_B);
        VendorPricingTier::create(['name' => 'B-Gold', 'markup_type' => 'fixed', 'markup_value' => 30]);

        // Vendor B sees only its own tier and none of A's upsells/waitlist.
        $this->assertSame(['B-Gold'], VendorPricingTier::pluck('name')->all());
        $this->assertSame(0, VendorUpsell::count());
        $this->assertSame(0, VendorWaitlist::count());

        // Vendor A still sees only its own.
        $this->actingAsVendor(self::VENDOR_A);
        $this->assertSame(['A-Gold'], VendorPricingTier::pluck('name')->all());
        $this->assertSame(1, VendorUpsell::count());
        $this->assertSame(1, VendorWaitlist::count());

        // The escape hatch sees both tenants.
        $this->assertSame(2, VendorPricingTier::withoutVendorScope()->count());
    }

    public function test_pricing_tier_markup_math(): void
    {
        $pct   = new VendorPricingTier(['markup_type' => 'percentage', 'markup_value' => 10]);
        $fixed = new VendorPricingTier(['markup_type' => 'fixed', 'markup_value' => 25]);

        $this->assertSame(110.0, $pct->applyTo(100));
        $this->assertSame(125.0, $fixed->applyTo(100));
    }
}
