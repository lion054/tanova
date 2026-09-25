<?php

namespace Tests\Feature\Vendor;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\BookingPaymentPlan;
use Modules\Vendor\Services\BookingPayments;
use Tests\TestCase;

/**
 * WP3: the payment schedule, and money received and refunded, on an isolated
 * throwaway DB. Status has to follow the money the way the gateways do it.
 */
class BookingPaymentsTest extends TestCase
{
    private const VENDOR = 101;

    private array $tables = ['bc_money_ledger_anchor', 'bc_money_ledger', 'bc_tourpay_payments', 'bc_tourpay_invoices', 'bc_tourpay_settings', 'bc_booking_ledger', 'bc_booking_payment_plan', 'bc_booking_meta', 'bc_bookings'];

    protected function setUp(): void
    {
        parent::setUp();

        $base = config('database.connections.mysql');
        $base['database'] = env('DB_TEST_DATABASE', 'tsoka_portal_phase0_test');
        config(['database.default' => 'mysql_test', 'database.connections.mysql_test' => $base]);
        DB::purge('mysql_test');
        // Only the booking's own event: faking every event would also switch off the
        // model events that stamp the vendor.
        Event::fake([\Modules\Booking\Events\BookingUpdatedEvent::class]);
        Carbon::setTestNow('2026-10-01 09:00:00');

        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('bc_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('paid', 12, 2)->nullable();
            $t->string('status')->default('unpaid');
            $t->dateTime('start_date')->nullable();
            $t->dateTime('end_date')->nullable();
            $t->integer('total_guests')->nullable();
            $t->string('currency')->nullable();
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
        Schema::create('bc_booking_payment_plan', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('booking_id');
            $t->string('label');
            $t->decimal('amount', 12, 2);
            $t->date('due_date')->nullable();
            $t->string('status')->default('pending');
            $t->timestamp('paid_at')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('bc_booking_ledger', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('booking_id');
            $t->string('type');
            $t->decimal('amount', 12, 2);
            $t->string('method')->default('other');
            $t->string('reference')->nullable();
            $t->string('note')->nullable();
            $t->unsignedBigInteger('plan_id')->nullable();
            $t->timestamp('occurred_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        // The money ledger, and the invoice tables the ledger looks at (there is no invoice in these tests).
        Schema::create('bc_money_ledger', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('vendor_id');
            $t->string('entry_key', 120)->unique();
            $t->string('kind', 12);
            $t->decimal('amount', 14, 2);
            $t->char('currency', 3);
            $t->string('held_by', 10)->default('vendor');
            $t->string('method', 30)->nullable();
            $t->string('source', 30);
            $t->unsignedBigInteger('source_id')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->unsignedBigInteger('bill_id')->nullable();
            $t->unsignedBigInteger('payout_id')->nullable();
            $t->unsignedBigInteger('reverses_id')->nullable();
            $t->string('reference', 120)->nullable();
            $t->string('note', 255)->nullable();
            $t->char('base_currency', 3)->nullable();
            $t->decimal('base_amount', 14, 2)->nullable();
            $t->decimal('booking_amount', 14, 2)->nullable();
            $t->decimal('fx_rate', 18, 8)->nullable();
            $t->string('fx_source', 20)->nullable();
            $t->char('chain_prev', 64)->nullable();
            $t->char('chain_hash', 64)->nullable();
            $t->dateTime('occurred_at');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('bc_money_ledger_anchor', function (Blueprint $t) {
            $t->unsignedBigInteger('vendor_id')->primary();
            $t->unsignedBigInteger('last_id')->default(0);
            $t->char('last_hash', 64)->default(str_repeat('0', 64));
            $t->timestamp('updated_at')->nullable();
        });
        Schema::create('bc_tourpay_invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->string('currency', 8)->nullable();
            $t->decimal('total', 14, 2)->default(0);
            $t->decimal('credit_total', 14, 2)->default(0);
            $t->softDeletes();
        });
        Schema::create('bc_tourpay_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->string('status')->nullable();
            $t->decimal('amount', 14, 2)->default(0);
        });
        Schema::create('bc_tourpay_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->nullable();
            $t->string('base_currency', 3)->nullable();
            $t->text('rates')->nullable();
        });

        $u = new User();
        $u->id = self::VENDOR;
        $this->actingAs($u);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ($this->tables as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function booking(float $total = 1000, ?string $start = '2026-12-01', float $paid = 0): Booking
    {
        $b = new Booking();
        $b->forceFill([
            'code' => 'ABC123', 'vendor_id' => self::VENDOR, 'total' => $total, 'paid' => $paid,
            'status' => 'unpaid', 'start_date' => $start, 'end_date' => $start, 'total_guests' => 2, 'currency' => 'USD',
        ])->save();

        return $b;
    }

    private function svc(): BookingPayments
    {
        return new BookingPayments();
    }

    private function rows(Booking $b): array
    {
        return BookingPaymentPlan::where('booking_id', $b->id)->orderBy('sort_order')->get()
            ->map(fn ($r) => [$r->label, (float) $r->amount, $r->due_date->toDateString(), $r->status])->all();
    }

    // The schedule ---------------------------------------------------------

    public function test_a_deposit_now_and_the_balance_before_the_trip(): void
    {
        $b = $this->booking(1000, '2026-12-01');
        $this->svc()->buildPlan($b, 'deposit', ['percent' => 30, 'balance_days' => 14]);

        $this->assertSame([
            ['Deposit (30%)', 300.0, '2026-10-01', 'pending'],
            ['Balance', 700.0, '2026-11-17', 'pending'],
        ], $this->rows($b));
    }

    public function test_the_balance_is_never_due_in_the_past(): void
    {
        // The trip is in 5 days and the balance is asked 14 days before: due today, not last week.
        $b = $this->booking(500, '2026-10-06');
        $this->svc()->buildPlan($b, 'deposit', ['percent' => 50, 'balance_days' => 14]);

        $this->assertSame('2026-10-01', $this->rows($b)[1][2]);
    }

    public function test_instalments_add_up_exactly_and_the_last_is_before_the_trip(): void
    {
        $b = $this->booking(1000.01, '2026-12-01');
        $this->svc()->buildPlan($b, 'split', ['parts' => 3, 'balance_days' => 14]);

        $rows = $this->rows($b);
        $this->assertCount(3, $rows);
        $this->assertEqualsWithDelta(1000.01, array_sum(array_column($rows, 1)), 0.001, 'to the cent');
        $this->assertSame('2026-10-01', $rows[0][2]);
        $this->assertSame('2026-11-17', $rows[2][2]);
        $this->assertTrue($rows[1][2] > $rows[0][2] && $rows[1][2] < $rows[2][2]);
    }

    public function test_full_payment_is_one_row(): void
    {
        $b = $this->booking(800);
        $this->svc()->buildPlan($b, 'full', ['balance_days' => 7]);
        $this->assertSame([['Full payment', 800.0, '2026-11-24', 'pending']], $this->rows($b));
    }

    public function test_building_again_replaces_what_is_unpaid_and_keeps_what_is_paid(): void
    {
        $b = $this->booking(1000);
        $this->svc()->buildPlan($b, 'deposit', ['percent' => 30]);
        $this->svc()->recordPayment($b, 300);          // pays the deposit row
        $this->svc()->buildPlan($b->fresh(), 'split', ['parts' => 2]);

        $rows = $this->rows($b);
        $this->assertSame('paid', $rows[0][3]);
        $this->assertSame(300.0, $rows[0][1]);
        $this->assertEqualsWithDelta(700.0, $rows[1][1] + $rows[2][1], 0.001, 'only what is still owed is scheduled');
    }

    public function test_an_unknown_plan_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->svc()->buildPlan($this->booking(), 'whenever');
    }

    // Money in -------------------------------------------------------------

    public function test_a_payment_moves_paid_and_the_status_follows(): void
    {
        $b = $this->booking(1000);
        $this->svc()->recordPayment($b, 400, 'bank', 'TX-1');
        $this->assertSame(400.0, (float) $b->fresh()->paid);
        $this->assertSame(Booking::PARTIAL_PAYMENT, $b->fresh()->status);

        $this->svc()->recordPayment($b->fresh(), 600, 'cash');
        $this->assertSame(1000.0, (float) $b->fresh()->paid);
        $this->assertSame(Booking::PAID, $b->fresh()->status);
        $this->assertSame(0.0, $this->svc()->balance($b->fresh()));
        $this->assertSame(2, BookingLedger::where('booking_id', $b->id)->count());
    }

    public function test_a_payment_pays_the_schedule_in_order(): void
    {
        $b = $this->booking(1000);
        $this->svc()->buildPlan($b, 'split', ['parts' => 4]);   // 4 x 250
        $this->svc()->recordPayment($b, 500);

        $this->assertSame(['paid', 'paid', 'pending', 'pending'], array_column($this->rows($b), 3));
    }

    public function test_a_part_payment_does_not_tick_a_row_it_does_not_cover(): void
    {
        $b = $this->booking(1000);
        $this->svc()->buildPlan($b, 'deposit', ['percent' => 30]);
        $this->svc()->recordPayment($b, 100);

        $this->assertSame(['pending', 'pending'], array_column($this->rows($b), 3));
        $this->assertSame(900.0, $this->svc()->balance($b->fresh()));
    }

    public function test_nothing_or_less_than_nothing_is_not_a_payment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->svc()->recordPayment($this->booking(), 0);
    }

    // Money back -----------------------------------------------------------

    public function test_a_refund_moves_paid_back_and_cannot_exceed_it(): void
    {
        $b = $this->booking(1000);
        $this->svc()->recordPayment($b, 1000);
        $this->svc()->recordRefund($b->fresh(), 250, 'bank', 'RF-1', 'Weather');

        $this->assertSame(750.0, (float) $b->fresh()->paid);
        $this->assertSame(Booking::PARTIAL_PAYMENT, $b->fresh()->status);
        $this->assertSame('refund', BookingLedger::where('booking_id', $b->id)->orderByDesc('id')->first()->type);

        $this->expectException(\InvalidArgumentException::class);
        $this->svc()->recordRefund($b->fresh(), 800);
    }

    public function test_refunding_everything_can_cancel_the_booking(): void
    {
        $b = $this->booking(400);
        $this->svc()->recordPayment($b, 400);
        $this->svc()->recordRefund($b->fresh(), 400, cancel: true);

        $this->assertSame(0.0, (float) $b->fresh()->paid);
        $this->assertSame(Booking::CANCELLED, $b->fresh()->status);
    }

    public function test_refunding_everything_without_cancelling_reopens_it_for_payment(): void
    {
        $b = $this->booking(400);
        $this->svc()->recordPayment($b, 400);
        $this->svc()->recordRefund($b->fresh(), 400);

        $this->assertSame(Booking::UNPAID, $b->fresh()->status);
    }

    public function test_a_completed_booking_is_not_cancelled_by_a_refund(): void
    {
        $b = $this->booking(400);
        $this->svc()->recordPayment($b, 400);
        $b = $b->fresh();
        $b->status = Booking::COMPLETED;
        $b->save();
        $this->svc()->recordRefund($b->fresh(), 400, cancel: true);

        $this->assertNotSame(Booking::CANCELLED, $b->fresh()->status);
    }
}
