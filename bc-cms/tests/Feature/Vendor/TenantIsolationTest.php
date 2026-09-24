<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Models\LoyaltyRule;
use Modules\Vendor\Models\VendorServiceTier;
use Modules\Vendor\Models\VendorShelfPin;
use Modules\Vendor\Models\VendorWaitlist;
use Modules\Vendor\Services\Shelves;
use Modules\Vendor\Services\VendorChannelDispatcher;
use Modules\Vendor\Services\WaitlistNotifier;
use Modules\Vendor\Services\TourSeats;
use Tests\TestCase;

/**
 * The portal is multi-tenant: whatever one vendor sets up, sells or is told about must
 * never reach another. Two vendors (7 and 8) side by side.
 */
class TenantIsolationTest extends TestCase
{
    private array $tables = ['bc_vendor_waitlist', 'bc_vendor_shelf_pins', 'bc_vendor_loyalty_rules', 'bc_vendor_service_tiers', 'bc_tour_dates', 'bc_bookings', 'bc_tours'];

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        $this->drop();

        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->string('slug')->nullable(); $t->integer('max_people')->nullable();
            $t->string('status')->default('publish'); $t->decimal('review_score', 4, 1)->nullable();
            $t->unsignedBigInteger('author_id')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('bc_tour_dates', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('target_id'); $t->dateTime('start_date'); $t->dateTime('end_date');
            $t->integer('max_guests')->nullable(); $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('vendor_id')->nullable(); $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable(); $t->integer('total_guests')->default(1);
            $t->string('status')->default('confirmed'); $t->dateTime('start_date')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('bc_vendor_service_tiers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('vendor_id'); $t->string('object_model', 40)->default('tour'); $t->unsignedBigInteger('object_id');
            $t->string('tier_key', 20); $t->string('name', 80); $t->string('tagline', 160)->nullable(); $t->text('description')->nullable();
            $t->decimal('price', 12, 2)->default(0); $t->boolean('price_per_person')->default(true);
            $t->unsignedSmallInteger('min_guests')->nullable(); $t->unsignedSmallInteger('max_guests')->nullable();
            $t->json('bands')->nullable(); $t->json('inclusions')->nullable(); $t->json('included_upsell_ids')->nullable();
            $t->boolean('recommended')->default(false); $t->boolean('active')->default(true); $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps(); $t->unique(['vendor_id', 'object_model', 'object_id', 'tier_key'], 'svc_tier_unique');
        });
        Schema::create('bc_vendor_loyalty_rules', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('vendor_id')->unique(); $t->boolean('enabled')->default(true);
            $t->decimal('spend_per_point', 10, 2)->default(10); $t->timestamps();
        });
        Schema::create('bc_vendor_shelf_pins', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('vendor_id'); $t->string('shelf', 12); $t->string('object_model', 40)->default('tour');
            $t->unsignedBigInteger('object_id'); $t->timestamps(); $t->unique(['vendor_id', 'shelf', 'object_model', 'object_id'], 'shelf_pin_unique');
        });
        Schema::create('bc_vendor_waitlist', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('vendor_id'); $t->string('object_model')->nullable(); $t->unsignedBigInteger('object_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable(); $t->string('customer_name'); $t->string('customer_email')->nullable();
            $t->string('customer_phone')->nullable(); $t->integer('party_size')->default(1); $t->date('preferred_date')->nullable();
            $t->text('notes')->nullable(); $t->string('status', 20)->default('waiting'); $t->string('source', 12)->default('vendor');
            $t->timestamp('notified_at')->nullable(); $t->unsignedSmallInteger('notified_count')->default(0);
            $t->unsignedBigInteger('booking_id')->nullable(); $t->timestamps();
        });

        foreach ([7 => 'Seven tour', 8 => 'Eight tour'] as $vendor => $title) {
            DB::table('bc_tours')->insert(['id' => $vendor * 10, 'title' => $title, 'max_people' => 10, 'author_id' => $vendor, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('bc_bookings')->insert(['vendor_id' => $vendor, 'object_model' => 'tour', 'object_id' => $vendor * 10, 'total_guests' => 10, 'status' => 'confirmed', 'start_date' => now()->addDays(5)->toDateString() . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
            VendorServiceTier::withoutVendorScope()->create(['vendor_id' => $vendor, 'object_id' => $vendor * 10, 'tier_key' => 'classic', 'name' => "Tier $vendor", 'price' => 100]);
            LoyaltyRule::withoutVendorScope()->create(['vendor_id' => $vendor, 'enabled' => true, 'spend_per_point' => $vendor]);
            VendorShelfPin::withoutVendorScope()->create(['vendor_id' => $vendor, 'shelf' => 'trending', 'object_id' => $vendor * 10]);
            VendorWaitlist::withoutVendorScope()->create(['vendor_id' => $vendor, 'object_model' => 'tour', 'object_id' => $vendor * 10, 'customer_name' => "Guest $vendor", 'customer_email' => "g$vendor@example.com", 'party_size' => 1, 'preferred_date' => now()->addDays(5)->toDateString()]);
        }
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

    private function actAs(int $id): void
    {
        $u = new User();
        $u->id = $id;
        $this->actingAs($u);
    }

    public function test_a_vendor_only_ever_reads_their_own_rows(): void
    {
        foreach ([7, 8] as $me) {
            $this->actAs($me);
            $this->assertSame(["Tier $me"], VendorServiceTier::pluck('name')->all());
            $this->assertSame([(float) $me], LoyaltyRule::pluck('spend_per_point')->map(fn ($v) => (float) $v)->all());
            $this->assertSame([$me * 10], VendorShelfPin::pluck('object_id')->all());
            $this->assertSame(["Guest $me"], VendorWaitlist::pluck('customer_name')->all());
            $this->assertNull(VendorServiceTier::find($me === 7 ? 2 : 1), 'the other vendor\'s tier is not reachable by id');
        }
    }

    public function test_a_vendor_cannot_create_rows_for_someone_else(): void
    {
        $this->actAs(7);
        $row = VendorServiceTier::create(['object_id' => 70, 'tier_key' => 'signature', 'name' => 'Mine', 'price' => 1]);

        $this->assertSame(7, (int) $row->vendor_id);
    }

    public function test_shelves_never_include_another_vendors_tours_or_bookings(): void
    {
        $titles = (new Shelves())->shelf('bestseller', 7)->map(fn ($r) => $r['tour']->title)->all();

        $this->assertSame(['Seven tour'], $titles);
    }

    public function test_notifying_a_waitlist_only_reaches_that_vendors_guests_and_needs_a_vendor(): void
    {
        $dispatcher = new class extends VendorChannelDispatcher {
            public array $to = [];
            public function send(int $vendorId, string $channel, array $recipient, string $subject, string $body): array
            {
                $this->to[] = $recipient['email'];

                return ['status' => 'sent', 'error' => null, 'to' => $recipient['email']];
            }
        };
        $n = new WaitlistNotifier(new TourSeats(), $dispatcher);
        // Free up both vendors' tours, then notify for vendor 7 only.
        DB::table('bc_bookings')->update(['status' => 'cancelled']);

        $told = $n->notifyOpenings(null, null, 7);

        $this->assertSame(1, $told);
        $this->assertSame(['g7@example.com'], $dispatcher->to);
        $this->assertSame('waiting', VendorWaitlist::withoutVendorScope()->where('vendor_id', 8)->first()->status);

        $this->expectException(\InvalidArgumentException::class);
        $n->notifyOpenings(null, null, 0);
    }

    public function test_expiring_old_entries_leaves_other_vendors_alone(): void
    {
        DB::table('bc_vendor_waitlist')->update(['preferred_date' => now()->subDay()->toDateString()]);
        $n = new WaitlistNotifier(new TourSeats(), new VendorChannelDispatcher());

        $this->assertSame(1, $n->expirePast(7));
        $this->assertSame('waiting', VendorWaitlist::withoutVendorScope()->where('vendor_id', 8)->first()->status);
    }
}
