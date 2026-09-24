<?php

namespace Tests\Feature\Vendor;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\ScheduledMessageLog;
use Modules\Vendor\Models\VendorOccasion;
use Modules\Vendor\Services\OccasionSync;
use Modules\Vendor\Services\VendorChannelDispatcher;
use Tests\TestCase;

/** WP5: the new message triggers, placeholders, yearly occasions, and birthdays read into Occasions. */
class ScheduledMessagesTest extends TestCase
{
    private array $tables = ['bc_vendor_scheduled_message_logs', 'bc_vendor_scheduled_messages', 'bc_vendor_occasions', 'bc_vendor_customers', 'bc_booking_guests', 'bc_booking_meta', 'bc_vendor_loyalty_accounts', 'bc_tours', 'bc_bookings'];
    private $spy;

    protected function setUp(): void
    {
        parent::setUp();
        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        $this->drop();

        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->string('object_model')->nullable();
            $t->unsignedBigInteger('object_id')->nullable();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('paid', 12, 2)->nullable();
            $t->string('status')->default('confirmed');
            $t->dateTime('start_date')->nullable();
            $t->dateTime('end_date')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('bc_tours', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->nullable();
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
        Schema::create('bc_booking_guests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('booking_id');
            $t->boolean('is_lead')->default(false);
            $t->string('source', 10)->default('vendor');
            $t->string('name');
            $t->date('date_of_birth')->nullable();
            $t->string('nationality')->nullable();
            $t->string('passport_number')->nullable();
            $t->string('dietary')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('bc_vendor_customers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('nationality')->nullable();
            $t->string('passport_number')->nullable();
            $t->text('notes')->nullable();
            $t->json('tags')->nullable();
            $t->unsignedInteger('bookings_count')->default(0);
            $t->decimal('total_spent', 14, 2)->default(0);
            $t->timestamp('first_booking_at')->nullable();
            $t->timestamp('last_booking_at')->nullable();
            $t->string('source', 20)->nullable();
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
        });
        Schema::create('bc_vendor_scheduled_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('name');
            $t->string('trigger', 40);
            $t->integer('offset_days')->default(0);
            $t->string('channel', 20)->default('email');
            $t->string('subject')->nullable();
            $t->text('body');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('bc_vendor_scheduled_message_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('scheduled_message_id');
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->string('channel', 20)->nullable();
            $t->string('recipient')->nullable();
            $t->string('status', 20)->default('sent');
            $t->text('error')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
        Schema::create('bc_vendor_occasions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('customer_name');
            $t->string('customer_email')->nullable();
            $t->string('customer_phone')->nullable();
            $t->string('type', 30)->default('birthday');
            $t->string('source', 12)->default('manual');
            $t->string('source_key', 40)->nullable();
            $t->date('occasion_date');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['vendor_id', 'source_key']);
        });

        DB::table('bc_tours')->insert(['id' => 1, 'title' => 'Gorge swing', 'created_at' => now(), 'updated_at' => now()]);

        // Record what would have been sent instead of sending it.
        $this->spy = new class extends VendorChannelDispatcher {
            public array $sent = [];
            public function send(int $vendorId, string $channel, array $recipient, string $subject, string $body): array
            {
                $this->sent[] = ['to' => $recipient['email'] ?? null, 'subject' => $subject, 'body' => $body];

                return ['status' => 'sent', 'error' => null, 'to' => $recipient['email'] ?? null];
            }
        };
        $this->app->instance(VendorChannelDispatcher::class, $this->spy);
    }

    protected function tearDown(): void
    {
        $this->drop();
        parent::tearDown();
    }

    private function drop(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();
    }

    private function message(string $trigger, int $offset, string $body = 'Hi {name}', string $subject = 'S'): ScheduledMessage
    {
        return ScheduledMessage::withoutVendorScope()->create(['vendor_id' => 7, 'name' => $trigger, 'trigger' => $trigger, 'offset_days' => $offset, 'channel' => 'email', 'subject' => $subject, 'body' => $body, 'active' => true]);
    }

    private function booking(string $starts, float $total = 500, float $paid = 500, string $email = 'ann@example.com', ?string $ends = null): int
    {
        return DB::table('bc_bookings')->insertGetId([
            'code' => 'ABCDEF1234', 'vendor_id' => 7, 'object_model' => 'tour', 'object_id' => 1, 'first_name' => 'Ann', 'last_name' => 'Ray',
            'email' => $email, 'total' => $total, 'paid' => $paid, 'status' => 'confirmed',
            'start_date' => $starts . ' 09:00:00', 'end_date' => ($ends ?? $starts) . ' 17:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function dispatchOn(?string $on = null): void
    {
        Artisan::call('vendor:dispatch-scheduled-messages', $on ? ['--date' => $on] : []);
    }

    public function test_placeholders_are_filled_in(): void
    {
        $day = Carbon::now()->addDays(3)->toDateString();
        $this->booking($day, 500, 200);
        $this->message('pre_trip', -3, 'Hi {name}, {trip} ref {reference} on {date}, owing {balance}', 'About {trip}');

        $this->dispatchOn();

        $this->assertCount(1, $this->spy->sent);
        $m = $this->spy->sent[0];
        $this->assertSame('About Gorge swing', $m['subject']);
        $this->assertStringContainsString('Hi Ann, Gorge swing ref ABCDEF12 on ' . Carbon::parse($day)->format('D j M Y') . ', owing $300.00', $m['body']);
    }

    public function test_payment_due_only_goes_to_bookings_with_a_balance(): void
    {
        $day = Carbon::now()->addDays(14)->toDateString();
        $this->booking($day, 500, 500, 'paid@example.com');
        $this->booking($day, 500, 100, 'owes@example.com');
        $this->message('payment_due', -14, 'Owing {balance}');

        $this->dispatchOn();

        $this->assertSame(['owes@example.com'], array_column($this->spy->sent, 'to'));
    }

    public function test_guest_form_reminder_skips_bookings_where_it_is_filled_in(): void
    {
        $day = Carbon::now()->addDays(7)->toDateString();
        $done = $this->booking($day, 500, 500, 'done@example.com');
        $this->booking($day, 500, 500, 'todo@example.com');
        DB::table('bc_booking_guests')->insert(['vendor_id' => 7, 'booking_id' => $done, 'source' => 'customer', 'name' => 'Ann', 'created_at' => now(), 'updated_at' => now()]);
        $this->message('guest_form', -7, 'Fill in: {guest_form_link}');

        $this->dispatchOn();

        $this->assertSame(['todo@example.com'], array_column($this->spy->sent, 'to'));
        $this->assertMatchesRegularExpression('#Fill in: http\S*/guest-form/[A-Za-z0-9]{40}$#', $this->spy->sent[0]['body']);
    }

    public function test_photo_delivery_runs_off_the_end_date_and_only_once(): void
    {
        $this->booking(Carbon::now()->subDays(5)->toDateString(), 500, 500, 'ann@example.com', Carbon::now()->subDays(3)->toDateString());
        $this->message('photo_delivery', 3, 'Photos');

        $this->dispatchOn();
        $this->dispatchOn();

        $this->assertCount(1, $this->spy->sent);
        $this->assertSame(1, ScheduledMessageLog::withoutVendorScope()->where('status', 'sent')->count());
    }

    public function test_loyalty_offer_says_how_many_points_the_guest_has(): void
    {
        $this->booking(Carbon::now()->subDays(15)->toDateString(), 500, 500, 'ann@example.com', Carbon::now()->subDays(14)->toDateString());
        DB::table('bc_vendor_loyalty_accounts')->insert(['vendor_id' => 7, 'customer_email' => 'ann@example.com', 'points' => 120, 'created_at' => now(), 'updated_at' => now()]);
        $this->message('loyalty_offer', 14, 'You have {points} points');

        $this->dispatchOn();

        $this->assertSame('You have 120 points', $this->spy->sent[0]['body']);
    }

    public function test_a_birthday_message_goes_out_again_every_year(): void
    {
        DB::table('bc_vendor_customers')->insert(['vendor_id' => 7, 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'ann@example.com', 'date_of_birth' => '1990-06-15', 'created_at' => now(), 'updated_at' => now()]);
        $this->message('occasion', 0, 'Happy birthday {name}');

        $this->dispatchOn('2026-06-15');
        $this->dispatchOn('2026-06-15'); // same day again: not repeated
        $this->assertCount(1, $this->spy->sent);

        $this->dispatchOn('2027-06-15'); // next year: sent again
        $this->assertCount(2, $this->spy->sent);
        $this->assertSame('Happy birthday Ann', $this->spy->sent[1]['body']);
    }

    public function test_birthdays_are_read_from_customers_and_guest_forms_without_repeats(): void
    {
        $b = $this->booking(Carbon::now()->addDays(20)->toDateString(), 500, 500, 'lead@example.com');
        DB::table('bc_vendor_customers')->insert(['vendor_id' => 7, 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'lead@example.com', 'date_of_birth' => '1990-06-15', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_booking_guests')->insert([
            ['vendor_id' => 7, 'booking_id' => $b, 'is_lead' => 1, 'source' => 'customer', 'name' => 'Ann Ray', 'date_of_birth' => '1990-06-15', 'created_at' => now(), 'updated_at' => now()],
            ['vendor_id' => 7, 'booking_id' => $b, 'is_lead' => 0, 'source' => 'customer', 'name' => 'Tom Ray', 'date_of_birth' => '2015-03-02', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $sync = new OccasionSync();

        $sync->run(7);
        $sync->run(7);

        $rows = VendorOccasion::withoutVendorScope()->orderBy('customer_name')->get();
        $this->assertCount(2, $rows); // Ann once (customer wins over her guest row), Tom once
        $this->assertSame('lead@example.com', $rows->firstWhere('customer_name', 'Ann Ray')->customer_email);
        $this->assertSame('customer', $rows->firstWhere('customer_name', 'Ann Ray')->source);
        $tom = $rows->firstWhere('customer_name', 'Tom Ray');
        $this->assertNull($tom->customer_email); // only the lead can be written to
    }

    public function test_the_starter_pack_uses_only_known_triggers_and_placeholders(): void
    {
        foreach (ScheduledMessage::STARTER as $m) {
            $this->assertArrayHasKey($m['trigger'], ScheduledMessage::TRIGGERS, $m['name']);
            preg_match_all('/\{[a-z_]+\}/', $m['subject'] . $m['body'], $found);
            foreach ($found[0] as $ph) {
                $this->assertArrayHasKey($ph, ScheduledMessage::PLACEHOLDERS, $m['name'] . ' uses ' . $ph);
            }
        }
    }
}
