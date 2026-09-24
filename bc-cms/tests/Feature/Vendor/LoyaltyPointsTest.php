<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\LoyaltyAccount;
use Modules\Vendor\Models\LoyaltyRule;
use Modules\Vendor\Models\LoyaltyTier;
use Modules\Vendor\Services\BookingStatusFlow;
use Modules\Vendor\Services\LoyaltyPoints;
use Tests\TestCase;

/** WP5: points are earned once per booking, from what was paid, when the trip completes. */
class LoyaltyPointsTest extends TestCase
{
    private array $tables = ['bc_vendor_loyalty_transactions', 'bc_vendor_loyalty_accounts', 'bc_vendor_loyalty_tiers', 'bc_vendor_loyalty_rules', 'bc_booking_comms', 'bc_booking_meta', 'bc_bookings'];

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        Event::fake([\Modules\Booking\Events\BookingUpdatedEvent::class]);
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('paid', 12, 2)->nullable();
            $t->string('status')->default('unpaid');
            $t->string('email')->nullable();
            $t->unsignedBigInteger('create_user')->nullable();
            $t->unsignedBigInteger('update_user')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_booking_meta', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->string('name');
            $t->text('val')->nullable();
            $t->timestamps();
        });
        Schema::create('bc_booking_comms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('booking_id');
            $t->string('channel')->default('note');
            $t->string('direction')->default('internal');
            $t->string('subject')->nullable();
            $t->text('body');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('bc_vendor_loyalty_rules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->unique();
            $t->boolean('enabled')->default(true);
            $t->decimal('spend_per_point', 10, 2)->default(10);
            $t->timestamps();
        });
        Schema::create('bc_vendor_loyalty_tiers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('name');
            $t->integer('min_points')->default(0);
            $t->decimal('earn_multiplier', 6, 2)->default(1);
            $t->text('perks')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('bc_vendor_loyalty_accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('customer_email');
            $t->string('customer_name')->nullable();
            $t->integer('points')->default(0);
            $t->unsignedBigInteger('tier_id')->nullable();
            $t->timestamps();
            $t->unique(['vendor_id', 'customer_email']);
        });
        Schema::create('bc_vendor_loyalty_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('account_id');
            $t->integer('points');
            $t->string('type', 20)->default('earn');
            $t->string('reason')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->timestamps();
        });

        $u = new User();
        $u->id = 7;
        $this->actingAs($u);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    private function booking(float $paid, string $email = 'Ann@Example.com', string $status = 'confirmed'): Booking
    {
        $id = DB::table('bc_bookings')->insertGetId([
            'code' => 'ABCDEF123', 'vendor_id' => 7, 'first_name' => 'Ann', 'last_name' => 'Ray',
            'total' => $paid, 'paid' => $paid, 'status' => $status, 'email' => $email,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Booking::find($id);
    }

    private function tier(string $name, int $min, float $mult): LoyaltyTier
    {
        return LoyaltyTier::create(['vendor_id' => 7, 'name' => $name, 'min_points' => $min, 'earn_multiplier' => $mult]);
    }

    public function test_default_rule_is_one_point_per_ten_dollars_rounded_down(): void
    {
        $tx = (new LoyaltyPoints())->awardForBooking($this->booking(255));

        $this->assertSame(25, $tx->points);
        $account = LoyaltyAccount::first();
        $this->assertSame(25, $account->points);
        $this->assertSame('ann@example.com', $account->customer_email);
    }

    public function test_a_vendor_rule_changes_the_rate(): void
    {
        LoyaltyRule::create(['vendor_id' => 7, 'enabled' => true, 'spend_per_point' => 5]);

        $this->assertSame(51, (new LoyaltyPoints())->awardForBooking($this->booking(255))->points);
    }

    public function test_a_disabled_rule_gives_nothing(): void
    {
        LoyaltyRule::create(['vendor_id' => 7, 'enabled' => false, 'spend_per_point' => 10]);

        $this->assertNull((new LoyaltyPoints())->awardForBooking($this->booking(500)));
        $this->assertSame(0, LoyaltyAccount::count());
    }

    public function test_nothing_paid_or_no_email_gives_nothing(): void
    {
        $svc = new LoyaltyPoints();
        $this->assertNull($svc->awardForBooking($this->booking(0)));
        $this->assertNull($svc->awardForBooking($this->booking(200, 'not-an-email')));
        $this->assertNull($svc->awardForBooking($this->booking(9)));
        $this->assertSame(0, LoyaltyAccount::count());
    }

    public function test_a_booking_earns_only_once(): void
    {
        $svc = new LoyaltyPoints();
        $b = $this->booking(100);

        $this->assertSame(10, $svc->awardForBooking($b)->points);
        $this->assertNull($svc->awardForBooking($b));
        $this->assertSame(10, LoyaltyAccount::first()->points);
    }

    public function test_the_tier_multiplier_applies_and_the_tier_follows_the_balance(): void
    {
        $this->tier('Bronze', 0, 1);
        $gold = $this->tier('Gold', 100, 1.5);
        $svc = new LoyaltyPoints();

        $svc->awardForBooking($this->booking(1000)); // 100 points -> Gold
        $this->assertSame($gold->id, LoyaltyAccount::first()->tier_id);

        $tx = $svc->awardForBooking($this->booking(200)); // 20 x 1.5 = 30
        $this->assertSame(30, $tx->points);
        $this->assertSame(130, LoyaltyAccount::first()->points);
    }

    public function test_completing_a_booking_awards_points_through_the_status_flow(): void
    {
        $b = $this->booking(300);

        app(BookingStatusFlow::class)->move($b, Booking::COMPLETED, null, 7);

        $this->assertSame(30, LoyaltyAccount::first()->points);
    }
}
