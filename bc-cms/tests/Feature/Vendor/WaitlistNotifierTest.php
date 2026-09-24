<?php

namespace Tests\Feature\Vendor;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\VendorWaitlist;
use Modules\Vendor\Services\TourSeats;
use Modules\Vendor\Services\VendorChannelDispatcher;
use Modules\Vendor\Services\WaitlistNotifier;
use Tests\TestCase;

/** WP5: guests are told in order, only when their whole party fits, and only once it really went. */
class WaitlistNotifierTest extends TestCase
{
    private array $tables = ['bc_vendor_waitlist', 'bc_tour_dates', 'bc_bookings', 'bc_tours'];
    private string $day;

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->nullable();
            $t->integer('max_people')->nullable();
            $t->boolean('default_state')->default(true);
            $t->string('status')->default('publish');
            $t->unsignedBigInteger('create_user')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_tour_dates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('target_id');
            $t->dateTime('start_date');
            $t->dateTime('end_date');
            $t->decimal('price', 12, 2)->nullable();
            $t->integer('max_guests')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable();
            $t->integer('total_guests')->default(1);
            $t->string('email')->nullable();
            $t->string('status')->default('unpaid');
            $t->dateTime('start_date')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_vendor_waitlist', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('customer_name');
            $t->string('customer_email')->nullable();
            $t->string('customer_phone')->nullable();
            $t->integer('party_size')->default(1);
            $t->date('preferred_date')->nullable();
            $t->text('notes')->nullable();
            $t->string('status', 20)->default('waiting');
            $t->string('source', 12)->default('vendor');
            $t->timestamp('notified_at')->nullable();
            $t->unsignedSmallInteger('notified_count')->default(0);
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->timestamps();
        });

        $this->day = Carbon::now()->addDays(10)->toDateString();
        DB::table('bc_tours')->insert(['id' => 1, 'title' => 'Gorge swing', 'max_people' => 10, 'created_at' => now(), 'updated_at' => now()]);
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

    private function notifier(string $result = 'sent'): WaitlistNotifier
    {
        $dispatcher = new class($result) extends VendorChannelDispatcher {
            public array $sent = [];
            public function __construct(private string $result) {}
            public function send(int $vendorId, string $channel, array $recipient, string $subject, string $body): array
            {
                $this->sent[] = [$recipient['email'] ?? $recipient['phone'] ?? null, $subject];

                return ['status' => $this->result, 'error' => $this->result === 'sent' ? null : 'no_email', 'to' => null];
            }
        };

        return new WaitlistNotifier(new TourSeats(), $dispatcher);
    }

    private function book(int $guests, string $status = 'confirmed', string $email = 'x@y.com'): int
    {
        return DB::table('bc_bookings')->insertGetId([
            'code' => uniqid(), 'vendor_id' => 7, 'object_model' => 'tour', 'object_id' => 1, 'total_guests' => $guests,
            'status' => $status, 'email' => $email, 'start_date' => $this->day . ' 09:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function wait(string $name, int $party, string $email = null): VendorWaitlist
    {
        return VendorWaitlist::withoutVendorScope()->create([
            'vendor_id' => 7, 'object_model' => 'tour', 'object_id' => 1, 'customer_name' => $name,
            'customer_email' => $email ?? strtolower($name) . '@example.com', 'party_size' => $party,
            'preferred_date' => $this->day, 'status' => 'waiting',
        ]);
    }

    public function test_only_a_party_that_fits_is_free(): void
    {
        $this->book(8); // 2 seats left of 10
        $n = $this->notifier();

        $this->assertSame(2, $n->freeFor($this->wait('Ann', 2)));
        $this->assertTrue($n->fits($this->wait('Ann', 2)));
        $this->assertFalse($n->fits($this->wait('Bob', 3)));
    }

    public function test_freed_seats_go_to_guests_in_order_and_are_not_promised_twice(): void
    {
        $id = $this->book(10, 'confirmed', 'big@party.com'); // full
        $ann = $this->wait('Ann', 2);
        $bob = $this->wait('Bob', 2);
        $cy = $this->wait('Cy', 4);
        $n = $this->notifier();
        $this->assertSame(0, $n->notifyOpenings(1, $this->day, 7));

        DB::table('bc_bookings')->where('id', $id)->update(['status' => 'cancelled']); // 10 seats free
        $told = $n->notifyOpenings(1, $this->day, 7);

        $this->assertSame(3, $told); // 2 + 2 + 4 = 8 <= 10
        $this->assertSame('notified', $ann->fresh()->status);
        $this->assertSame(1, $cy->fresh()->notified_count);

        // Nobody is told twice by a repeat run.
        $this->assertSame(0, $n->notifyOpenings(1, $this->day, 7));
    }

    public function test_one_freed_seat_is_offered_to_the_first_in_line_only(): void
    {
        $this->book(9); // one seat left
        $ann = $this->wait('Ann', 1);
        $bob = $this->wait('Bob', 1);

        $this->assertSame(1, $this->notifier()->notifyOpenings(1, $this->day, 7));
        $this->assertSame('notified', $ann->fresh()->status);
        $this->assertSame('waiting', $bob->fresh()->status);
    }

    public function test_a_message_that_did_not_go_leaves_the_guest_waiting(): void
    {
        $ann = $this->wait('Ann', 1);
        $result = $this->notifier('skipped')->notify($ann);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('waiting', $ann->fresh()->status);
        $this->assertNull($ann->fresh()->notified_at);
    }

    public function test_a_guest_who_books_what_they_waited_for_is_marked_booked(): void
    {
        $ann = $this->wait('Ann', 2, 'ann@example.com');
        $bookingId = $this->book(2, 'confirmed', 'Ann@Example.com');

        $this->notifier()->markBooked(Booking::find($bookingId));

        $this->assertSame('converted', $ann->fresh()->status);
        $this->assertSame($bookingId, (int) $ann->fresh()->booking_id);
    }

    public function test_entries_for_a_past_day_expire(): void
    {
        $old = $this->wait('Old', 1);
        $old->update(['preferred_date' => Carbon::now()->subDay()->toDateString()]);
        $new = $this->wait('New', 1);

        $this->assertSame(1, $this->notifier()->expirePast(7));
        $this->assertSame('expired', $old->fresh()->status);
        $this->assertSame('waiting', $new->fresh()->status);
    }
}
