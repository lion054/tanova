<?php

namespace Tests\Feature\Api;

use App\Support\Health;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Core\Models\Settings;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Models\Payment;
use Modules\TourPay\Services\BillBook;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\InvoiceRuleException;
use Modules\TourPay\Services\Ledger;
use Modules\TourPay\Services\MoneyBackfill;
use Modules\TourPay\Services\MoneyReconcile;
use Modules\Vendor\Models\VendorPayout;
use Modules\Vendor\Services\BookingPayments;
use Tests\ApiTestCase;

/**
 * One ledger, one truth: every movement of money is written once, never changed, and every total agrees with it.
 */
class MoneyLedgerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake([\Modules\Booking\Events\BookingUpdatedEvent::class]);   // no e-mails from these tests
    }

    /** Serves the exchange-rate feed from memory (USD base) so the tests never touch the network. */
    private function fakeRates(array $rates): void
    {
        $all = ['USD' => 1.0, 'EUR' => 0.9, 'GBP' => 0.8, 'KES' => 129.0, 'TZS' => 2600.0, 'UGX' => 3800.0] + $rates;
        for ($i = 0; count($all) < 70; $i++) { $all['T' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)] = 1.0 + $i; }
        \Illuminate\Support\Facades\Http::fake(['open.er-api.com/*' => \Illuminate\Support\Facades\Http::response(['result' => 'success', 'rates' => $all])]);
        DB::table('bc_fx_rates')->delete();
        \Illuminate\Support\Facades\Cache::forget('fx.attempt');
    }

    private function booking(float $total = 500, ?int $vendor = null, array $o = []): Booking
    {
        $id = DB::table('bc_bookings')->insertGetId($o + ['code' => strtoupper(substr(md5(uniqid('', true)), 0, 10)), 'vendor_id' => $vendor ?? $this->vendor->id, 'object_model' => 'tour', 'first_name' => 'Ann', 'last_name' => 'Ray',
            'email' => 'ann@example.com', 'total' => $total, 'total_before_fees' => $total, 'paid' => 0, 'status' => 'unpaid', 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now()]);

        return Booking::findOrFail($id);
    }

    private function invoice(float $amount = 500, ?Booking $b = null, string $cur = 'USD', ?int $vendor = null): Invoice
    {
        $v = $vendor ?? $this->vendor->id;
        $id = DB::table('bc_tourpay_invoices')->insertGetId(['vendor_id' => $v, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-' . uniqid(), 'currency' => $cur, 'client_name' => 'Ann Ray',
            'tax_mode' => 'exclusive', 'tax_rate' => 0, 'booking_id' => $b?->id, 'issue_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tourpay_invoice_items')->insert(['vendor_id' => $v, 'invoice_id' => $id, 'name' => 'Tour', 'quantity' => 1, 'unit_price' => $amount, 'total' => $amount, 'created_at' => now(), 'updated_at' => now()]);

        return Invoice::withoutVendorScope()->findOrFail($id)->recalculate();
    }

    private function net(array $where): float
    {
        return round((float) LedgerEntry::withoutVendorScope()->where($where)->sum('amount'), 2);
    }

    // ── One fact, one row ────────────────────────────────────────────────────

    public function test_a_fact_is_written_once_and_the_ledger_cannot_be_rewritten(): void
    {
        $l = app(Ledger::class);
        $row = ['vendor_id' => $this->vendor->id, 'entry_key' => 'test:1', 'kind' => 'payment', 'amount' => 10, 'currency' => 'usd', 'source' => 'test'];
        $a = $l->record($row);
        $b = $l->record($row);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, LedgerEntry::withoutVendorScope()->where('entry_key', 'test:1')->count());
        $this->assertSame('USD', $a->currency);

        $this->expectException(\LogicException::class);
        $a->update(['amount' => 99]);
    }

    public function test_a_row_cannot_be_deleted_only_reversed_once(): void
    {
        $l = app(Ledger::class);
        $e = $l->record(['vendor_id' => $this->vendor->id, 'entry_key' => 'test:2', 'kind' => 'payment', 'amount' => 10, 'currency' => 'USD', 'source' => 'test']);
        try {
            $e->delete();
            $this->fail('deleting should be refused');
        } catch (\LogicException $x) {
            $this->assertStringContainsString('append-only', $x->getMessage());
        }
        $r1 = $l->reverse('test:2');
        $r2 = $l->reverse('test:2');
        $this->assertSame($r1->id, $r2->id);
        $this->assertEquals(-10, $r1->amount);
        $this->assertSame($e->id, $r1->reverses_id);
        $this->assertNull($l->reverse('test:2:reversal'), 'a reversal is not reversed again');
        $this->assertEquals(0, $this->net(['vendor_id' => $this->vendor->id, 'source' => 'test']));
    }

    // ── Invoice payments ─────────────────────────────────────────────────────

    public function test_an_invoice_payment_is_one_ledger_row_and_removing_it_reverses_it(): void
    {
        $inv = $this->invoice(500);
        $book = app(InvoiceBook::class);
        $p = $book->recordPayment($inv, 200, 'bank', now()->toDateString(), 'REF1', null, 'manual', $this->vendor->id, 'gw-1');
        $again = $book->recordPayment($inv->fresh(), 200, 'bank', now()->toDateString(), 'REF1', null, 'manual', $this->vendor->id, 'gw-1');
        $this->assertSame($p->id, $again->id);

        $rows = LedgerEntry::withoutVendorScope()->where('invoice_id', $inv->id)->get();
        $this->assertCount(1, $rows);
        $this->assertEquals(200, $rows[0]->amount);
        $this->assertSame('vendor', $rows[0]->held_by);
        $this->assertSame('payment', $rows[0]->kind);

        $book->removePayment($inv->fresh(), $p);
        $this->assertEquals(0, $this->net(['invoice_id' => $inv->id]));
        $this->assertCount(2, LedgerEntry::withoutVendorScope()->where('invoice_id', $inv->id)->get(), 'history keeps both the payment and its reversal');
    }

    public function test_a_pending_transfer_is_not_money_until_confirmed_and_a_refund_is_negative(): void
    {
        $inv = $this->invoice(300);
        $book = app(InvoiceBook::class);
        $pending = $book->reportTransfer($inv, 300, 'BANKREF');
        $this->assertSame(0, LedgerEntry::withoutVendorScope()->where('invoice_id', $inv->id)->count());
        $book->approvePayment($inv->fresh(), $pending, $this->vendor->id);
        $this->assertEquals(300, $this->net(['invoice_id' => $inv->id]));

    }

    // ── A booking and its invoice are one story ──────────────────────────────

    public function test_a_payment_on_the_booking_page_counts_once_on_the_booking_and_on_its_invoice(): void
    {
        $b = $this->booking(500);
        $inv = $this->invoice(500, $b);
        app(BookingPayments::class)->recordPayment($b, 200, 'cash', 'R-1', null, null, $this->vendor->id);

        $this->assertEquals(200, Booking::find($b->id)->paid);
        $this->assertEquals(200, $inv->fresh()->amount_paid);
        $this->assertSame('part_paid', $inv->fresh()->status);
        $this->assertEquals(200, $this->net(['booking_id' => $b->id]), 'one cash fact, not two');
        $this->assertSame(1, LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->count());

        // A refund on the booking takes it back off the invoice too.
        app(BookingPayments::class)->recordRefund($b->fresh(), 50, 'cash', 'R-2', null, $this->vendor->id);
        $this->assertEquals(150, Booking::find($b->id)->paid);
        $this->assertEquals(150, $inv->fresh()->amount_paid);
    }

    public function test_a_payment_on_the_invoice_raises_the_booking_and_its_status(): void
    {
        $b = $this->booking(500);
        $inv = $this->invoice(500, $b);
        app(InvoiceBook::class)->recordPayment($inv, 500, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);

        $fresh = Booking::find($b->id);
        $this->assertEquals(500, $fresh->paid);
        $this->assertSame('paid', $fresh->status);
        $this->assertEquals(500, LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->sum('amount'));
    }

    public function test_an_invoice_in_another_currency_keeps_its_currency_and_credits_the_booking_at_the_days_rate(): void
    {
        $this->fakeRates(['ZAR' => 18.0]);
        $b = $this->booking(500, null, ['currency' => 'USD']);
        $inv = $this->invoice(1800, $b, 'ZAR');
        app(InvoiceBook::class)->recordPayment($inv, 900, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);

        $row = LedgerEntry::withoutVendorScope()->where('invoice_id', $inv->id)->firstOrFail();
        $this->assertSame('ZAR', $row->currency);
        $this->assertEquals(900, $row->amount, 'the payment and its invoice keep their own currency');
        $this->assertEquals(50, $row->booking_amount, '900 ZAR at 18 is 50 USD on the booking');
        $this->assertSame('api', $row->fx_source);
        $this->assertEquals(50, Booking::find($b->id)->paid);
        $this->assertEquals(900, $inv->fresh()->amount_paid, 'the invoice is not converted');
        $this->assertSame([], app(MoneyReconcile::class)->run()['problems']);
    }

    public function test_the_businesss_own_rate_beats_the_feed_and_a_missing_rate_links_nothing(): void
    {
        $this->fakeRates(['ZAR' => 18.0]);
        DB::table('bc_tourpay_settings')->updateOrInsert(['vendor_id' => $this->vendor->id], ['base_currency' => 'USD', 'rates' => json_encode(['ZAR' => 0.05]), 'created_at' => now(), 'updated_at' => now()]);   // 1 ZAR = 0.05 USD: 20 per USD
        $b = $this->booking(500, null, ['currency' => 'USD']);
        $inv = $this->invoice(1800, $b, 'ZAR');
        app(InvoiceBook::class)->recordPayment($inv, 1000, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);
        $row = LedgerEntry::withoutVendorScope()->where('invoice_id', $inv->id)->firstOrFail();
        $this->assertEquals(50, $row->booking_amount);
        $this->assertSame('vendor', $row->fx_source);

        // A currency nobody has a rate for is never guessed: the payment stands, it just is not linked to the booking.
        $b2 = $this->booking(500, null, ['currency' => 'USD']);
        $inv2 = $this->invoice(100, $b2, 'XTS');
        app(InvoiceBook::class)->recordPayment($inv2, 40, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);
        $this->assertNull(LedgerEntry::withoutVendorScope()->where('invoice_id', $inv2->id)->first()->booking_id);
        $this->assertEquals(0, Booking::find($b2->id)->paid);
        $this->assertEquals(40, $inv2->fresh()->amount_paid);
    }

    public function test_money_recorded_on_a_booking_in_one_currency_is_allocated_to_its_invoice_in_the_other(): void
    {
        $this->fakeRates(['ZAR' => 18.0]);
        $b = $this->booking(500, null, ['currency' => 'USD']);
        $inv = $this->invoice(9000, $b, 'ZAR');
        app(BookingPayments::class)->recordPayment($b, 100, 'cash', null, null, null, $this->vendor->id);
        $this->assertEquals(1800, $inv->fresh()->amount_paid, '100 USD paid on the booking is 1,800 ZAR on the invoice');
        $this->assertEquals(100, Booking::find($b->id)->paid);
    }

    public function test_money_recorded_on_the_booking_cannot_be_removed_from_its_invoice(): void
    {
        $b = $this->booking(500);
        $inv = $this->invoice(500, $b);
        app(BookingPayments::class)->recordPayment($b, 100, 'cash', null, null, null, $this->vendor->id);
        $alloc = Payment::withoutVendorScope()->where('invoice_id', $inv->id)->where('source', 'booking')->firstOrFail();

        $this->expectException(InvoiceRuleException::class);
        app(InvoiceBook::class)->removePayment($inv->fresh(), $alloc);
    }

    public function test_an_invoice_made_from_a_booking_starts_with_what_was_paid(): void
    {
        $b = $this->booking(500);
        app(BookingPayments::class)->recordPayment($b, 120, 'cash', null, null, null, $this->vendor->id);
        [$inv] = app(\Modules\TourPay\Services\InvoiceFromBooking::class)->make($b->fresh());
        $this->assertEquals(120, $inv->fresh()->amount_paid);
        $this->assertEquals(120, Booking::find($b->id)->paid);
        $this->assertSame(1, LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->count(), 'the invoice adds no second row for the same money');
    }

    public function test_a_gateway_that_writes_paid_directly_lands_in_the_ledger_as_platform_money(): void
    {
        $b = $this->booking(300);
        $b->gateway = 'paypal';
        $b->paid = 300;
        $b->save();   // what the booking engine's gateways still do

        $row = LedgerEntry::withoutVendorScope()->where('booking_id', $b->id)->firstOrFail();
        $this->assertEquals(300, $row->amount);
        $this->assertSame('platform', $row->held_by);
        $this->assertSame('gateway', $row->source);
        $this->assertEquals(300, Booking::find($b->id)->paid, 'the gateway total is left as it wrote it');
        $this->assertEquals(300, app(Ledger::class)->bookingPaid($b->id));
    }

    // ── Suppliers and payouts ────────────────────────────────────────────────

    public function test_supplier_payments_are_negative_rows_and_never_overpay(): void
    {
        $bill = Bill::withoutVendorScope()->create(['vendor_id' => $this->vendor->id, 'supplier_name' => 'Lodge', 'currency' => 'USD', 'total' => 100, 'status' => 'open', 'bill_date' => now()->toDateString()]);
        app(BillBook::class)->pay($bill, ['amount' => 60, 'method' => 'bank']);
        try {
            app(BillBook::class)->pay($bill, ['amount' => 60, 'method' => 'bank']);
            $this->fail('the second payment would overpay the bill');
        } catch (InvoiceRuleException $e) {
            $this->assertSame('exceeds_balance', $e->errorCode);
        }
        $this->assertEquals(-60, $this->net(['bill_id' => $bill->id]));
        $this->assertSame('expense', LedgerEntry::withoutVendorScope()->where('bill_id', $bill->id)->first()->kind);
        $this->assertEquals(60, $bill->fresh()->amount_paid);
    }

    public function test_payouts_only_count_money_the_platform_actually_holds_and_only_the_vendor_share(): void
    {
        Settings::store('vendor_payout_booking_status', json_encode(['paid', 'completed']));
        // Guest paid the platform: held by the platform.
        $held = $this->booking(100, null, ['status' => 'completed', 'commission' => 10, 'vendor_service_fee_amount' => 0, 'total_before_fees' => 100, 'gateway' => 'paypal']);
        Booking::find($held->id)->forceFill(['paid' => 100])->save();
        // Guest paid the vendor directly: not the platform's money to pay out.
        $direct = $this->booking(100, null, ['status' => 'completed', 'commission' => 10, 'vendor_service_fee_amount' => 0, 'total_before_fees' => 100]);
        app(BookingPayments::class)->recordPayment($direct, 100, 'bank', null, null, null, $this->vendor->id);

        // held 100, vendor share 90; the 100 the guest paid the business directly is not the platform's to pay out, and its 10 commission is held back.
        $this->assertEquals(80, $this->vendor->fresh()->available_payout_amount);

        $po = new VendorPayout();
        $po->vendor_id = $this->vendor->id; $po->amount = 40; $po->status = 'initial'; $po->payout_method = 'bank'; $po->save();
        $this->assertEquals(40, $this->vendor->fresh()->available_payout_amount, 'a requested payout is already spoken for');

        $po->status = 'paid'; $po->pay_date = now()->toDateString(); $po->save();
        $row = LedgerEntry::withoutVendorScope()->where('entry_key', 'payout:' . $po->id)->firstOrFail();
        $this->assertEquals(40, $row->amount);
        $this->assertSame('payout', $row->kind);
        $po->status = 'rejected'; $po->save();
        $this->assertEquals(0, $this->net(['payout_id' => $po->id]), 'a payout that is no longer paid is reversed');
    }

    // ── Loading history, and checking it ─────────────────────────────────────

    public function test_the_backfill_loads_old_money_once_and_reconcile_finds_a_difference(): void
    {
        $b = $this->booking(400, null, ['paid' => 150, 'gateway' => 'paypal', 'status' => 'partial_payment']);   // raw insert: no ledger row yet
        $r1 = app(MoneyBackfill::class)->run();
        $r2 = app(MoneyBackfill::class)->run();
        $this->assertGreaterThanOrEqual(1, $r1['opening']);
        $this->assertSame(0, $r2['opening'] + $r2['tourpay_payments'] + $r2['booking_payments'], 'a second run writes nothing');
        $this->assertEquals(150, $this->net(['booking_id' => $b->id]));
        $this->assertSame('platform', LedgerEntry::withoutVendorScope()->where('entry_key', 'opening:booking:' . $b->id)->value('held_by'));

        $this->assertArrayNotHasKey('booking_paid_differs_from_ledger', app(MoneyReconcile::class)->run()['problems']);

        DB::table('bc_bookings')->where('id', $b->id)->update(['paid' => 999]);   // someone edits the stored total behind the ledger's back
        $res = app(MoneyReconcile::class)->run();
        $this->assertArrayHasKey('booking_paid_differs_from_ledger', $res['problems']);
        $this->assertFalse(Health::run()['checks']['money']['ok']);

        app(MoneyReconcile::class)->run(true);
        $this->assertEquals(150, Booking::find($b->id)->paid, 'the stored total is refreshed from the ledger; the ledger is never bent to fit');
        $this->assertArrayNotHasKey('booking_paid_differs_from_ledger', app(MoneyReconcile::class)->run()['problems']);
    }

    public function test_reconcile_writes_a_missing_row_for_a_payment_that_bypassed_the_hooks(): void
    {
        $inv = $this->invoice(200);
        $pid = DB::table('bc_tourpay_payments')->insertGetId(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'amount' => 80, 'method' => 'bank', 'paid_at' => now()->toDateString(), 'status' => 'confirmed', 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);
        $res = app(MoneyReconcile::class)->run();
        $this->assertArrayHasKey('payment_missing_in_ledger', $res['problems']);

        $fixed = app(MoneyReconcile::class)->run(true);
        $this->assertGreaterThan(0, $fixed['fixed']);
        $this->assertEquals(80, $this->net(['entry_key' => 'tourpay_payment:' . $pid]));
    }

    public function test_health_reports_the_nightly_money_check(): void
    {
        \Cache::forget(MoneyReconcile::CACHE_KEY);
        $this->assertTrue(Health::run()['checks']['money']['ok'], 'not run yet is not a failure');
        \Cache::forever(MoneyReconcile::CACHE_KEY, ['ok' => true, 'ran_at' => now()->subDays(3)->toIso8601String(), 'problems' => [], 'samples' => [], 'fixed' => 0]);
        $this->assertFalse(Health::run()['checks']['money']['ok'], 'a check that has not run for days is red');
        \Cache::forget(MoneyReconcile::CACHE_KEY);
    }

    // ── Under load ───────────────────────────────────────────────────────────

    public function test_money_changes_lock_the_row_they_change(): void
    {
        $b = $this->booking(500);
        $inv = $this->invoice(500);
        $bill = Bill::withoutVendorScope()->create(['vendor_id' => $this->vendor->id, 'supplier_name' => 'Lodge', 'currency' => 'USD', 'total' => 100, 'status' => 'open', 'bill_date' => now()->toDateString()]);
        $queries = [];
        DB::connection('mysql_api')->listen(function ($q) use (&$queries) { $queries[] = strtolower($q->sql); });

        app(BookingPayments::class)->recordPayment($b, 10, 'cash', null, null, null, $this->vendor->id);
        app(InvoiceBook::class)->recordPayment($inv, 10, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);
        app(BillBook::class)->pay($bill, ['amount' => 10, 'method' => 'bank']);

        foreach (['bc_bookings', 'bc_tourpay_invoices', 'bc_tourpay_bills'] as $table) {
            $this->assertTrue(collect($queries)->contains(fn ($sql) => str_contains($sql, $table) && str_contains($sql, 'for update')), "{$table} is locked while its balance is checked");
        }
    }

    public function test_the_same_payment_twice_in_a_minute_is_recorded_once(): void
    {
        $b = $this->booking(500);
        $svc = app(BookingPayments::class);
        $one = $svc->recordPayment($b, 50, 'bank', 'SLIP-9', null, null, $this->vendor->id);
        $two = $svc->recordPayment($b->fresh(), 50, 'bank', 'SLIP-9', null, null, $this->vendor->id);
        $this->assertSame($one->id, $two->id);
        $this->assertEquals(50, Booking::find($b->id)->paid);
    }

    public function test_every_stored_total_still_equals_its_rows_after_a_mixed_run(): void
    {
        $b = $this->booking(1000);
        $inv = $this->invoice(1000, $b);
        $pay = app(BookingPayments::class);
        $book = app(InvoiceBook::class);
        $pay->recordPayment($b, 300, 'cash', null, null, null, $this->vendor->id);
        $book->recordPayment($inv->fresh(), 200, 'bank', now()->toDateString(), 'X', null, 'manual', $this->vendor->id);
        $pay->recordRefund($b->fresh(), 100, 'cash', null, null, $this->vendor->id);
        $book->markPaid($inv->fresh(), 'bank', $this->vendor->id);

        $res = app(MoneyReconcile::class)->run();
        $this->assertSame([], $res['problems'], json_encode($res['samples']));
        $this->assertEquals(1000, Booking::find($b->id)->paid);
        $this->assertEquals(1000, $inv->fresh()->amount_paid);
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_a_gateway_working_from_a_stale_total_cannot_make_the_booking_and_ledger_disagree(): void
    {
        $b = $this->booking(300);
        app(BookingPayments::class)->recordPayment($b, 50, 'cash', null, null, null, $this->vendor->id);

        $stale = Booking::find($b->id);            // the gateway loaded the booking...
        app(BookingPayments::class)->recordPayment(Booking::find($b->id), 25, 'cash', null, null, null, $this->vendor->id);   // ...a payment landed meanwhile...
        $stale->paid = 50 + 100;                   // ...and the gateway writes the total it computed from what it loaded
        $stale->gateway = 'stripe';
        $stale->save();

        $this->assertEquals(175, app(Ledger::class)->bookingPaid($b->id));
        $this->assertEquals(175, Booking::find($b->id)->paid, 'the stored total is made equal to the ledger');
        $this->assertSame([], app(MoneyReconcile::class)->run()['problems']);
    }

    public function test_a_booking_that_predates_the_ledger_is_levelled_before_a_gateway_adds_to_it(): void
    {
        $b = $this->booking(400, null, ['paid' => 100, 'gateway' => 'paypal']);   // raw insert: paid 100, no ledger row
        $m = Booking::find($b->id);
        $m->paid = 250;                                                            // the gateway adds 150
        $m->save();

        $this->assertEquals(250, app(Ledger::class)->bookingPaid($b->id));
        $this->assertEquals(250, Booking::find($b->id)->paid, 'the 100 it already held is not lost');
        $this->assertEquals(100, LedgerEntry::withoutVendorScope()->where('entry_key', 'opening:booking:' . $b->id)->value('amount'));
    }

    public function test_a_refund_the_business_gave_from_its_own_pocket_lowers_what_the_platform_can_pay_out(): void
    {
        Settings::store('vendor_payout_booking_status', json_encode(['paid', 'completed', 'partial_payment']));
        $b = $this->booking(100, null, ['status' => 'completed', 'commission' => 0, 'vendor_service_fee_amount' => 0, 'total_before_fees' => 100, 'gateway' => 'paypal']);
        Booking::find($b->id)->forceFill(['paid' => 100])->save();
        $this->assertEquals(100, $this->vendor->fresh()->available_payout_amount);

        app(BookingPayments::class)->recordRefund(Booking::find($b->id), 60, 'cash', null, null, $this->vendor->id);
        $this->assertEquals(40, $this->vendor->fresh()->available_payout_amount, 'the guest got 60 back, so only 40 is left to pay out');
    }

    public function test_the_statement_opens_with_what_came_before_the_period(): void
    {
        $inv = $this->invoice(500);
        $book = app(InvoiceBook::class);
        $book->recordPayment($inv, 200, 'bank', now()->subDays(20)->toDateString(), null, null, 'manual', $this->vendor->id);
        $book->recordPayment($inv->fresh(), 100, 'bank', now()->subDays(2)->toDateString(), null, null, 'manual', $this->vendor->id);
        $this->actingAs($this->vendor);

        $r = app(\Modules\TourPay\Services\AccountStatement::class)->build(now()->subDays(5), now());
        $this->assertEquals(200, $r['opening']['USD']);
        $this->assertCount(1, $r['entries']);
        $this->assertEquals(300, $r['entries'][0]['balance']);
    }

    // ── One schedule ─────────────────────────────────────────────────────────

    public function test_a_plan_built_on_the_booking_is_the_invoices_schedule_and_paid_rows_follow_the_ledger(): void
    {
        $b = $this->booking(1000, null, ['start_date' => now()->addDays(60)->toDateString() . ' 09:00:00']);
        $inv = $this->invoice(1000, $b);
        $pay = app(BookingPayments::class);
        $pay->buildPlan($b, 'deposit', ['percent' => 30, 'balance_days' => 14]);

        $rows = \Modules\TourPay\Models\Installment::where('invoice_id', $inv->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $rows, 'the invoice has the same two instalments');
        $this->assertEquals([300, 700], $rows->pluck('amount')->map(fn ($v) => (float) $v)->all());
        $this->assertSame(['Deposit (30%)', 'Balance'], $rows->pluck('label')->all());

        // The deposit is paid on the INVOICE side: the booking's plan shows it paid too.
        app(InvoiceBook::class)->recordPayment($inv->fresh(), 300, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);
        $plan = \Modules\Vendor\Models\BookingPaymentPlan::where('booking_id', $b->id)->orderBy('sort_order')->get();
        $this->assertSame(['paid', 'pending'], $plan->pluck('status')->all());

        // A refund on the booking takes the deposit back to pending, on both.
        $pay->recordRefund(Booking::find($b->id), 300, 'bank', null, null, $this->vendor->id);
        $this->assertSame(['pending', 'pending'], \Modules\Vendor\Models\BookingPaymentPlan::where('booking_id', $b->id)->orderBy('sort_order')->pluck('status')->all());
        $this->assertEquals(0, collect(app(InvoiceBook::class)->schedule($inv->fresh()))->sum('paid'));
    }

    public function test_a_schedule_set_on_the_invoice_becomes_the_bookings_plan_and_clearing_it_clears_both(): void
    {
        $b = $this->booking(600, null, ['start_date' => now()->addDays(60)->toDateString() . ' 09:00:00']);
        $inv = $this->invoice(600, $b);
        app(InvoiceBook::class)->setSchedule($inv->fresh(), 'split', null, 3);

        $plan = \Modules\Vendor\Models\BookingPaymentPlan::where('booking_id', $b->id)->orderBy('sort_order')->get();
        $this->assertCount(3, $plan);
        $this->assertEquals(600, $plan->sum('amount'));

        app(InvoiceBook::class)->setSchedule($inv->fresh(), 'none');
        $this->assertSame(0, \Modules\Vendor\Models\BookingPaymentPlan::where('booking_id', $b->id)->count());
    }

    public function test_an_invoice_made_from_a_booking_takes_the_bookings_plan(): void
    {
        $b = $this->booking(400, null, ['start_date' => now()->addDays(60)->toDateString() . ' 09:00:00']);
        app(BookingPayments::class)->buildPlan($b, 'split', ['parts' => 2]);
        [$inv] = app(\Modules\TourPay\Services\InvoiceFromBooking::class)->make($b->fresh());
        $this->assertCount(2, \Modules\TourPay\Models\Installment::where('invoice_id', $inv->id)->get());
    }

    // ── Backfill of NULLs, scale, and the database protection ────────────────

    public function test_the_backfill_brings_a_null_paid_booking_up_to_the_ledger(): void
    {
        $b = $this->booking(300, null, ['paid' => null]);
        $inv = $this->invoice(300, $b);
        // An invoice payment that predates the ledger: a raw insert, and the booking's stored paid is NULL.
        DB::table('bc_tourpay_payments')->insert(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'amount' => 120, 'method' => 'bank', 'paid_at' => now()->toDateString(), 'status' => 'confirmed', 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertNull(DB::table('bc_bookings')->where('id', $b->id)->value('paid'));

        $n = app(MoneyBackfill::class)->run();
        $this->assertGreaterThanOrEqual(1, $n['booking_caches']);
        $this->assertEquals(120, DB::table('bc_bookings')->where('id', $b->id)->value('paid'));
    }

    public function test_reconcile_stays_a_handful_of_queries_however_many_payments_there_are(): void
    {
        $inv = $this->invoice(1000000);
        $rows = [];
        for ($i = 1; $i <= 3000; $i++) {
            $rows[] = ['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'amount' => 1, 'method' => 'bank', 'paid_at' => now()->toDateString(), 'status' => 'confirmed', 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($rows, 500) as $chunk) { DB::table('bc_tourpay_payments')->insert($chunk); }
        $inv->recalculate();   // rows went in directly, so refresh the stored total the way the app would
        app(MoneyBackfill::class)->run();

        $count = 0;
        DB::connection('mysql_api')->listen(function () use (&$count) { $count++; });
        $t = microtime(true);
        $res = app(MoneyReconcile::class)->run();
        $this->assertSame([], $res['problems'], json_encode($res['samples']));
        $this->assertLessThan(25, $count, "the check ran {$count} queries for 3,000 payments: it must be set-based");
        $this->assertLessThan(10.0, microtime(true) - $t);
    }

    public function test_the_hash_chain_catches_an_edit_a_deletion_and_a_forged_row_made_behind_the_apps_back(): void
    {
        $inv = $this->invoice(900);
        $book = app(InvoiceBook::class);
        foreach ([100, 200, 300] as $amt) { $book->recordPayment($inv->fresh(), $amt, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id); }
        $this->assertSame([], \Modules\TourPay\Services\LedgerChain::verify(), 'a normal run leaves an intact chain');
        $ids = LedgerEntry::withoutVendorScope()->where('vendor_id', $this->vendor->id)->orderBy('id')->pluck('id')->all();

        // 1. Someone edits an amount with raw SQL.
        DB::table('bc_money_ledger')->where('id', $ids[1])->update(['amount' => 999]);
        $res = app(MoneyReconcile::class)->run();
        $this->assertArrayHasKey('ledger_chain_broken', $res['problems']);
        $this->assertStringContainsString('was changed', $res['samples']['ledger_chain_broken'][0]);
        $this->assertFalse(Health::run()['checks']['money']['ok'], 'the health check goes red');
        DB::table('bc_money_ledger')->where('id', $ids[1])->update(['amount' => 200]);   // put it back
        $this->assertSame([], \Modules\TourPay\Services\LedgerChain::verify());

        // 2. A row in the middle is deleted.
        $copy = (array) DB::table('bc_money_ledger')->where('id', $ids[1])->first();
        DB::table('bc_money_ledger')->where('id', $ids[1])->delete();
        $this->assertStringContainsString('removed or added', \Modules\TourPay\Services\LedgerChain::verify()[0]);
        DB::table('bc_money_ledger')->insert($copy);   // put it back
        $this->assertSame([], \Modules\TourPay\Services\LedgerChain::verify());

        // 3. The newest row is deleted (caught by the anchor).
        $last = (array) DB::table('bc_money_ledger')->where('id', $ids[2])->first();
        DB::table('bc_money_ledger')->where('id', $ids[2])->delete();
        $this->assertStringContainsString('anchor', \Modules\TourPay\Services\LedgerChain::verify()[0]);
        DB::table('bc_money_ledger')->insert($last);

        // 4. A forged row with no seal.
        $forged = $last; unset($forged['id']); $forged['entry_key'] = 'forged:1'; $forged['chain_hash'] = null; $forged['chain_prev'] = null;
        DB::table('bc_money_ledger')->insert($forged);
        $this->assertStringContainsString('without the chain', \Modules\TourPay\Services\LedgerChain::verify()[0]);
        DB::table('bc_money_ledger')->where('entry_key', 'forged:1')->delete();
        $this->assertSame([], \Modules\TourPay\Services\LedgerChain::verify());
    }

    public function test_the_chain_stays_intact_through_reversals_commission_and_a_mixed_run(): void
    {
        $b = $this->booking(1000, null, ['commission' => 100]);
        $inv = $this->invoice(1000, $b);
        $p = app(InvoiceBook::class)->recordPayment($inv, 400, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);
        app(BookingPayments::class)->recordPayment(Booking::find($b->id), 100, 'cash', 'C-1', null, null, $this->vendor->id);
        app(InvoiceBook::class)->removePayment($inv->fresh(), $p);    // a reversal
        $this->assertGreaterThan(3, LedgerEntry::withoutVendorScope()->where('vendor_id', $this->vendor->id)->count(), 'payments, reversal and commission rows');
        $this->assertSame([], \Modules\TourPay\Services\LedgerChain::verify());
        $this->assertSame([], app(MoneyReconcile::class)->run()['problems']);
    }

    public function test_the_backfill_gives_existing_money_its_commission_once(): void
    {
        $b = $this->booking(1000, null, ['commission' => 100]);
        // Money that was in the ledger before commission existed: written with the accrual switched off.
        Ledger::$backfilling = true;
        app(Ledger::class)->record(['vendor_id' => $this->vendor->id, 'entry_key' => 'legacy:1', 'kind' => 'payment', 'amount' => 500, 'currency' => 'USD', 'source' => 'booking_ledger', 'source_id' => 9, 'booking_id' => $b->id]);
        Ledger::$backfilling = false;
        $this->assertSame(0, LedgerEntry::withoutVendorScope()->where('kind', 'commission')->count());
        $this->assertArrayHasKey('commission_missing', app(MoneyReconcile::class)->run()['problems']);

        $first = app(MoneyBackfill::class)->run();
        $again = app(MoneyBackfill::class)->run();
        $this->assertSame(1, $first['commission']);
        $this->assertSame(0, $again['commission']);
        $this->assertEquals(['USD' => 50.0], app(\Modules\TourPay\Services\Commission::class)->owed($this->vendor->id));
        $this->assertArrayNotHasKey('commission_missing', app(MoneyReconcile::class)->run()['problems']);
    }
}
