<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Controllers\QuoteController;
use Modules\Vendor\Models\BookingQuote;
use Tests\TestCase;

/**
 * Verifies that accepting a quote applies the agreed amount to the booking and
 * supersedes other open quotes. Isolated throwaway MySQL DB — never live data.
 */
class QuoteApplyTest extends TestCase
{
    private const VENDOR = 101;

    protected function setUp(): void
    {
        parent::setUp();

        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');

        Schema::dropIfExists('bc_booking_quotes');
        Schema::dropIfExists('bc_bookings');

        Schema::create('bc_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->nullable();
            $table->decimal('coupon_amount', 12, 2)->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->decimal('total_before_fees', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('status', 30)->nullable();
            $table->integer('create_user')->nullable();
            $table->integer('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('bc_booking_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('direction', 20)->default('vendor');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->nullable();
            $table->text('message')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('sent');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bc_booking_quotes');
        Schema::dropIfExists('bc_bookings');
        parent::tearDown();
    }

    public function test_accepting_a_quote_applies_amount_and_supersedes_others(): void
    {
        $u = new User();
        $u->id = self::VENDOR;
        $this->actingAs($u);

        $bookingId = DB::table('bc_bookings')->insertGetId([
            'vendor_id' => self::VENDOR, 'total' => 100, 'currency' => 'USD', 'status' => 'processing',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $q1 = BookingQuote::create(['booking_id' => $bookingId, 'amount' => 150, 'currency' => 'USD', 'status' => 'sent']);
        $q2 = BookingQuote::create(['booking_id' => $bookingId, 'amount' => 200, 'currency' => 'USD', 'status' => 'sent']);

        (new QuoteController())->accept($q2);

        // Agreed amount applied to the booking.
        $this->assertEquals(200.0, (float) Booking::find($bookingId)->total);
        // Accepted quote marked accepted; the other open quote superseded (declined).
        $this->assertSame(BookingQuote::STATUS_ACCEPTED, $q2->fresh()->status);
        $this->assertSame(BookingQuote::STATUS_DECLINED, $q1->fresh()->status);
    }
}
