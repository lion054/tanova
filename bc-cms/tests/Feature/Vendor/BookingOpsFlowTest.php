<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingComm;
use Modules\Vendor\Services\BookingStatusFlow;
use Modules\Vendor\Services\GuestForm;
use Tests\TestCase;

/**
 * WP3: where a booking can go next (and that every move is written down), and the
 * customer's guest-form link. Isolated throwaway DB.
 */
class BookingOpsFlowTest extends TestCase
{
    private array $tables = ['bc_booking_comms', 'bc_booking_meta', 'bc_bookings'];

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        Event::fake([\Modules\Booking\Events\BookingUpdatedEvent::class]);

        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->unsignedBigInteger('vendor_id')->nullable();
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

        $u = new User();
        $u->id = 101;
        $this->actingAs($u);
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function booking(string $status = 'unpaid'): Booking
    {
        $b = new Booking();
        $b->forceFill(['code' => 'CODE1234', 'vendor_id' => 101, 'total' => 100, 'paid' => 0, 'status' => $status, 'email' => 'x@y.co'])->save();

        return $b;
    }

    // Status ------------------------------------------------------------------

    public function test_the_moves_a_booking_can_make(): void
    {
        $f = new BookingStatusFlow();
        $this->assertEqualsCanonicalizing(['confirmed', 'cancelled'], $f->allowedFrom('unpaid'));
        $this->assertEqualsCanonicalizing(['confirmed', 'completed', 'cancelled'], $f->allowedFrom('paid'));
        $this->assertEqualsCanonicalizing(['completed', 'cancelled'], $f->allowedFrom('confirmed'));
        $this->assertSame([], $f->allowedFrom('cancelled'), 'a cancelled booking stays cancelled');
        $this->assertSame([], $f->allowedFrom('completed'), 'so does a completed one');
    }

    public function test_a_move_changes_the_status_and_is_written_down_with_its_reason(): void
    {
        $b = $this->booking('paid');
        (new BookingStatusFlow())->move($b, 'cancelled', 'Weather closed the park', 7);

        $this->assertSame('cancelled', $b->fresh()->status);
        $note = BookingComm::where('booking_id', $b->id)->first();
        $this->assertStringContainsString('paid', $note->subject);
        $this->assertStringContainsString('cancelled', $note->subject);
        $this->assertSame('Weather closed the park', $note->body);
        $this->assertSame(7, (int) $note->created_by);
        Event::assertDispatched(\Modules\Booking\Events\BookingUpdatedEvent::class);
    }

    public function test_a_move_that_is_not_allowed_changes_nothing(): void
    {
        $b = $this->booking('cancelled');
        try {
            (new BookingStatusFlow())->move($b, 'confirmed');
            $this->fail('should not be allowed');
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame('cancelled', $b->fresh()->status);
        $this->assertSame(0, BookingComm::where('booking_id', $b->id)->count());
    }

    public function test_a_booking_waiting_for_payment_can_still_be_confirmed(): void
    {
        $b = $this->booking('unpaid');
        (new BookingStatusFlow())->move($b, 'confirmed');
        $this->assertSame('confirmed', $b->fresh()->status);
    }

    // Guest form -----------------------------------------------------------------

    public function test_a_booking_has_one_stable_link_until_it_is_replaced(): void
    {
        $g = new GuestForm();
        $b = $this->booking();

        $first = $g->tokenFor($b);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $first);
        $this->assertSame($first, $g->tokenFor($b), 'asking again gives the same link');
        $this->assertSame($b->id, $g->bookingFor($first)->id);
        $this->assertStringEndsWith('/guest-form/' . $first, $g->urlFor($b));

        $second = $g->regenerate($b);
        $this->assertNotSame($first, $second);
        $this->assertNull($g->bookingFor($first), 'the old link stops working');
        $this->assertSame($b->id, $g->bookingFor($second)->id);
    }

    public function test_only_a_real_token_opens_a_booking(): void
    {
        $g = new GuestForm();
        $this->assertNull($g->bookingFor('short'));
        $this->assertNull($g->bookingFor(str_repeat('a', 40)));
        $this->assertNull($g->bookingFor("' OR 1=1 --"));
    }

    public function test_two_bookings_never_share_a_link(): void
    {
        $g = new GuestForm();
        $a = $this->booking();
        $b = $this->booking();
        $this->assertNotSame($g->tokenFor($a), $g->tokenFor($b));
        $this->assertSame($a->id, $g->bookingFor($g->tokenFor($a))->id);
    }
}
