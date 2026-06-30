<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\LoyaltyTier;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\VendorCampaign;
use Tests\TestCase;

/**
 * Phase 3 — proves the engagement models (loyalty, scheduled messages, campaigns)
 * are tenant-isolated by App\Traits\BelongsToVendor, and the loyalty tier lookup.
 * Runs against an isolated throwaway MySQL DB — never the real tsoka_portal data.
 */
class EngagementIsolationTest extends TestCase
{
    private const VENDOR_A = 101;
    private const VENDOR_B = 202;

    private array $tables = [
        'bc_vendor_loyalty_tiers',
        'bc_vendor_loyalty_accounts',
        'bc_vendor_scheduled_messages',
        'bc_vendor_campaigns',
    ];

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

        Schema::create('bc_vendor_loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->integer('min_points')->default(0);
            $table->decimal('earn_multiplier', 6, 2)->default(1);
            $table->text('perks')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('bc_vendor_loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->integer('points')->default(0);
            $table->unsignedBigInteger('tier_id')->nullable();
            $table->timestamps();
        });
        Schema::create('bc_vendor_scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('trigger', 40);
            $table->integer('offset_days')->default(0);
            $table->string('channel', 20)->default('email');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('bc_vendor_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('subject');
            $table->text('body');
            $table->string('audience', 30)->default('all_customers');
            $table->string('status', 20)->default('draft');
            $table->integer('sent_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $t) {
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

    public function test_engagement_models_isolate_and_autostamp(): void
    {
        $this->actingAsVendor(self::VENDOR_A);
        LoyaltyAccount::create(['customer_email' => 'a@x.com', 'points' => 50]);
        ScheduledMessage::create(['name' => 'A welcome', 'trigger' => 'pre_trip', 'channel' => 'email', 'body' => 'hi']);
        $campA = VendorCampaign::create(['subject' => 'A news', 'body' => '<p>hi</p>']);

        $this->assertSame(self::VENDOR_A, (int) $campA->vendor_id);

        $this->actingAsVendor(self::VENDOR_B);
        VendorCampaign::create(['subject' => 'B news', 'body' => '<p>yo</p>']);

        // Vendor B sees only its own and none of A's loyalty/messages.
        $this->assertSame(['B news'], VendorCampaign::pluck('subject')->all());
        $this->assertSame(0, LoyaltyAccount::count());
        $this->assertSame(0, ScheduledMessage::count());

        $this->actingAsVendor(self::VENDOR_A);
        $this->assertSame(1, LoyaltyAccount::count());
        $this->assertSame(['A news'], VendorCampaign::pluck('subject')->all());
        $this->assertSame(2, VendorCampaign::withoutVendorScope()->count());
    }

    public function test_loyalty_tier_for_points_is_vendor_scoped(): void
    {
        $this->actingAsVendor(self::VENDOR_A);
        LoyaltyTier::create(['name' => 'Bronze', 'min_points' => 0]);
        LoyaltyTier::create(['name' => 'Gold', 'min_points' => 100]);

        $this->assertSame('Gold', LoyaltyTier::forPoints(150)->name);
        $this->assertSame('Bronze', LoyaltyTier::forPoints(10)->name);

        // Vendor B has no tiers → null, never sees A's tiers.
        $this->actingAsVendor(self::VENDOR_B);
        $this->assertNull(LoyaltyTier::forPoints(150));
    }
}
