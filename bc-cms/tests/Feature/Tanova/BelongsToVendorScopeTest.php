<?php

namespace Tests\Feature\Tanova;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Pro\Tanova\Models\TanovaTrip;
use Tests\TestCase;

/**
 * Phase 0 tenancy foundation — proves App\Traits\BelongsToVendor isolates data.
 *
 * Uses an isolated throwaway MySQL database (NOT RefreshDatabase, NOT the real
 * `tsoka_portal` DB) so production/dev data is never touched. Exercises the trait
 * at the model
 * layer: auto-stamping on create, automatic read scoping, the withoutVendorScope
 * escape hatch, cross-vendor invisibility, team-member resolution, and the
 * no-tenant (system/CLI) no-op.
 */
class BelongsToVendorScopeTest extends TestCase
{
    private const VENDOR_A = 101;
    private const VENDOR_B = 202;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolated throwaway MySQL DB — clone the default mysql config, swap the
        // database name. Never the real tsoka_portal DB.
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config([
            'database.default' => 'mysql_test',
            'database.connections.mysql_test' => $base,
        ]);
        DB::purge('mysql_test');

        Schema::dropIfExists('bc_tanova_trips');
        Schema::create('bc_tanova_trips', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->string('title')->nullable();
            $table->string('destination')->nullable();
            $table->string('status')->default(TanovaTrip::STATUS_CREATED);
            $table->json('itinerary')->nullable();
            $table->decimal('estimated_price', 12, 2)->nullable();
            $table->string('currency', 8)->default('USD');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->bigInteger('create_user')->nullable();
            $table->bigInteger('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bc_tanova_trips');
        parent::tearDown();
    }

    /** Build an in-memory user (vendor owner or team member) without hitting the DB. */
    private function user(int $id, ?int $vendorId = null): User
    {
        $u = new User();
        $u->id = $id;
        if ($vendorId !== null) {
            $u->vendor_id = $vendorId;
        }
        return $u;
    }

    /** Seed a trip for a specific vendor regardless of current auth. */
    private function seedTrip(int $vendorId, string $title): TanovaTrip
    {
        return TanovaTrip::create([
            'user_id'   => $vendorId,
            'vendor_id' => $vendorId,
            'title'     => $title,
        ]);
    }

    public function test_create_auto_stamps_current_vendor(): void
    {
        $this->actingAs($this->user(self::VENDOR_A));

        $trip = TanovaTrip::create(['title' => 'Unstamped']);

        $this->assertSame(self::VENDOR_A, (int) $trip->vendor_id);
    }

    public function test_team_member_create_stamps_owner_not_member(): void
    {
        // Team member #999 belonging to vendor owner 101.
        $this->actingAs($this->user(999, self::VENDOR_A));

        $trip = TanovaTrip::create(['title' => 'By team member']);

        $this->assertSame(self::VENDOR_A, (int) $trip->vendor_id);
    }

    public function test_reads_are_scoped_to_current_vendor(): void
    {
        $this->seedTrip(self::VENDOR_A, 'A trip');
        $this->seedTrip(self::VENDOR_B, 'B trip');

        $this->actingAs($this->user(self::VENDOR_A));
        $this->assertSame(1, TanovaTrip::count());
        $this->assertSame(['A trip'], TanovaTrip::pluck('title')->all());

        $this->actingAs($this->user(self::VENDOR_B));
        $this->assertSame(['B trip'], TanovaTrip::pluck('title')->all());
    }

    public function test_cross_vendor_find_returns_null(): void
    {
        $bTrip = $this->seedTrip(self::VENDOR_B, 'B trip');

        $this->actingAs($this->user(self::VENDOR_A));

        $this->assertNull(TanovaTrip::find($bTrip->id));
        $this->assertNotNull(TanovaTrip::withoutVendorScope()->find($bTrip->id));
    }

    public function test_without_vendor_scope_sees_all_tenants(): void
    {
        $this->seedTrip(self::VENDOR_A, 'A trip');
        $this->seedTrip(self::VENDOR_B, 'B trip');

        $this->actingAs($this->user(self::VENDOR_A));

        $this->assertSame(1, TanovaTrip::count());
        $this->assertSame(2, TanovaTrip::withoutVendorScope()->count());
    }

    public function test_no_tenant_context_is_a_noop(): void
    {
        // Unauthenticated / system / CLI context — scope must not constrain,
        // so crons like expireOld() operate across all vendors.
        $this->seedTrip(self::VENDOR_A, 'A trip');
        $this->seedTrip(self::VENDOR_B, 'B trip');

        $this->assertSame(2, TanovaTrip::count());
    }
}
