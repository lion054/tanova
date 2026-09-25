<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Booking\Models\Booking;
use Modules\Core\Models\Settings;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Services\Commission;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\Ledger;
use Modules\TourPay\Services\Rates;
use Modules\Vendor\Models\VendorPayout;
use Modules\Vendor\Services\BookingPayments;
use Tests\ApiTestCase;

/** The platform's commission on money a business collects itself, and the exchange rates the books use. */
class CommissionAndRatesTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake([\Modules\Booking\Events\BookingUpdatedEvent::class]);
        DB::table('bc_fx_rates')->delete();
        Cache::forget('fx.attempt');
    }

    private function feed(array $rates = [], string $result = 'success', int $status = 200): void
    {
        $all = ['USD' => 1.0, 'ZAR' => 18.0] + $rates;
        for ($i = 0; count($all) < 70; $i++) { $all['T' . $i] = 1.0 + $i; }
        Http::fake(['open.er-api.com/*' => Http::response(['result' => $result, 'rates' => $all], $status)]);
    }

    private function booking(float $total, float $commission, array $o = []): Booking
    {
        $id = DB::table('bc_bookings')->insertGetId($o + ['code' => strtoupper(substr(md5(uniqid('', true)), 0, 10)), 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'first_name' => 'Ann', 'last_name' => 'Ray',
            'email' => 'ann@example.com', 'total' => $total, 'total_before_fees' => $total, 'commission' => $commission, 'paid' => 0, 'status' => 'unpaid', 'currency' => 'USD', 'created_at' => now(), 'updated_at' => now()]);

        return Booking::findOrFail($id);
    }

    private function invoice(float $amount, ?Booking $b, string $cur): Invoice
    {
        $id = DB::table('bc_tourpay_invoices')->insertGetId(['vendor_id' => $this->vendor->id, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-' . uniqid(), 'currency' => $cur, 'client_name' => 'Ann Ray',
            'tax_mode' => 'exclusive', 'tax_rate' => 0, 'booking_id' => $b?->id, 'issue_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tourpay_invoice_items')->insert(['vendor_id' => $this->vendor->id, 'invoice_id' => $id, 'name' => 'Tour', 'quantity' => 1, 'unit_price' => $amount, 'total' => $amount, 'created_at' => now(), 'updated_at' => now()]);

        return Invoice::withoutVendorScope()->findOrFail($id)->recalculate();
    }

    // ── Rates ────────────────────────────────────────────────────────────────

    public function test_the_feed_is_fetched_stored_by_day_and_used(): void
    {
        $this->feed(['TZS' => 2600.0]);
        $r = app(Rates::class);
        $c = $r->convert(2600, 'TZS', 'USD');
        $this->assertEquals(1, $c['amount']);
        $this->assertSame('api', $c['source']);
        $this->assertSame(1, DB::table('bc_fx_rates')->count());
        $this->assertNull($r->convert(5, 'USD', 'NOPE'), 'no rate is never guessed');
        $this->assertSame('same', $r->convert(5, 'usd', 'USD')['source']);
    }

    public function test_a_broken_feed_is_ignored_and_the_last_known_rates_stay(): void
    {
        $this->feed();
        $this->assertTrue(app(Rates::class)->refresh());
        $before = DB::table('bc_fx_rates')->value('rates');

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['open.er-api.com/*' => Http::sequence()->push(['result' => 'error'], 500)->push(['result' => 'success', 'rates' => ['USD' => 1]], 200)]);   // an outage, then far too few currencies to be believed
        $this->assertFalse(app(Rates::class)->refresh());
        $this->assertFalse(app(Rates::class)->refresh());
        $this->assertSame($before, DB::table('bc_fx_rates')->value('rates'));
        $this->assertNotNull(app(Rates::class)->convert(18, 'ZAR', 'USD'), 'the stored rates still answer');
    }

    public function test_an_old_conversion_uses_the_rate_of_its_day(): void
    {
        DB::table('bc_fx_rates')->insert(['day' => now()->subDays(10)->toDateString(), 'base' => 'USD', 'rates' => json_encode(['USD' => 1, 'ZAR' => 10.0]), 'fetched_at' => now()->subDays(10)]);
        DB::table('bc_fx_rates')->insert(['day' => now()->toDateString(), 'base' => 'USD', 'rates' => json_encode(['USD' => 1, 'ZAR' => 20.0]), 'fetched_at' => now()]);
        $r = app(Rates::class);
        $this->assertEquals(10, $r->convert(100, 'ZAR', 'USD', now()->subDays(9))['amount']);
        $this->assertEquals(5, $r->convert(100, 'ZAR', 'USD')['amount']);
    }

    // ── Commission ───────────────────────────────────────────────────────────

    public function test_money_the_business_collects_itself_owes_its_share_of_commission_and_a_refund_gives_it_back(): void
    {
        $b = $this->booking(1000, 100);   // 10% commission
        app(BookingPayments::class)->recordPayment($b, 400, 'cash', null, null, null, $this->vendor->id);
        $this->assertEquals(['USD' => 40.0], app(Commission::class)->owed($this->vendor->id));

        app(BookingPayments::class)->recordRefund(Booking::find($b->id), 100, 'cash', null, null, $this->vendor->id);
        $this->assertEquals(['USD' => 30.0], app(Commission::class)->owed($this->vendor->id));

        app(BookingPayments::class)->recordPayment(Booking::find($b->id), 700, 'bank', null, null, null, $this->vendor->id);   // paid in full
        $this->assertEquals(['USD' => 100.0], app(Commission::class)->owed($this->vendor->id), 'a fully paid booking owes exactly its commission');
    }

    public function test_commission_stays_in_the_currency_it_was_collected_in(): void
    {
        $this->feed();
        $b = $this->booking(1000, 100, ['currency' => 'USD']);
        $inv = $this->invoice(18000, $b, 'ZAR');
        app(InvoiceBook::class)->recordPayment($inv, 9000, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);   // half, in rand

        $owed = app(Commission::class)->owed($this->vendor->id);
        $this->assertEquals(['ZAR' => 900.0], $owed, '10% of 9,000 ZAR is owed in ZAR, not converted');
        $this->assertEquals(500, Booking::find($b->id)->paid, 'the booking is credited 500 USD for its own paid amount');
    }

    public function test_platform_money_and_opening_balances_owe_no_commission(): void
    {
        $b = $this->booking(1000, 100, ['gateway' => 'paypal']);
        $m = Booking::find($b->id);
        $m->paid = 1000;
        $m->save();   // the platform's own checkout
        $this->assertSame([], app(Commission::class)->owed($this->vendor->id));
    }

    public function test_owed_commission_is_held_back_from_a_payout_and_settled_when_it_is_paid(): void
    {
        Settings::store('vendor_payout_booking_status', json_encode(['completed', 'paid', 'partial_payment']));
        $held = $this->booking(1000, 100, ['status' => 'completed', 'gateway' => 'paypal']);
        Booking::find($held->id)->forceFill(['paid' => 1000])->save();                                         // the platform collected 1,000: the business's share is 900
        $direct = $this->booking(500, 50, ['status' => 'completed']);
        app(BookingPayments::class)->recordPayment($direct, 500, 'bank', null, null, null, $this->vendor->id);   // the business collected 500 itself: 50 commission owed

        $this->assertEquals(['USD' => 50.0], app(Commission::class)->owed($this->vendor->id));
        $this->assertEquals(850, $this->vendor->fresh()->available_payout_amount, '900 the platform holds for the business, less the 50 it is owed');

        $po = new VendorPayout();
        $po->vendor_id = $this->vendor->id; $po->amount = 850; $po->status = 'paid'; $po->payout_method = 'bank'; $po->pay_date = now()->toDateString(); $po->save();
        $this->assertSame([], app(Commission::class)->owed($this->vendor->id), 'the 50 is settled from the 50 the platform still holds');
        $this->assertEquals(0, $this->vendor->fresh()->available_payout_amount);
        $this->assertSame('commission_from_held', LedgerEntry::withoutVendorScope()->where('kind', 'commission')->where('amount', '>', 0)->value('source'));
    }

    public function test_a_debt_in_another_currency_is_never_set_against_money_in_the_payout_currency(): void
    {
        $this->feed();
        Settings::store('vendor_payout_booking_status', json_encode(['completed', 'paid']));
        $held = $this->booking(1000, 100, ['status' => 'completed', 'gateway' => 'paypal']);
        Booking::find($held->id)->forceFill(['paid' => 1000])->save();
        $b = $this->booking(1000, 100, ['currency' => 'USD']);
        $inv = $this->invoice(18000, $b, 'ZAR');
        app(InvoiceBook::class)->recordPayment($inv, 18000, 'bank', now()->toDateString(), null, null, 'manual', $this->vendor->id);
        $this->assertEquals(['ZAR' => 1800.0], app(Commission::class)->owed($this->vendor->id));
        $this->assertEquals(900, $this->vendor->fresh()->available_payout_amount, 'the rand debt does not reduce a dollar payout');
    }

    public function test_manual_settlement_cannot_exceed_what_is_owed(): void
    {
        $b = $this->booking(1000, 100);
        app(BookingPayments::class)->recordPayment($b, 1000, 'cash', null, null, null, $this->vendor->id);
        $this->artisan('commission:settle', ['vendor_id' => $this->vendor->id, 'currency' => 'USD', 'amount' => 500])->assertFailed();
        $this->artisan('commission:settle', ['vendor_id' => $this->vendor->id, 'currency' => 'USD', 'amount' => 60])->assertSuccessful();
        $this->assertEquals(['USD' => 40.0], app(Commission::class)->owed($this->vendor->id));
    }

    public function test_the_statement_shows_commission_owed_but_it_is_not_cash(): void
    {
        $b = $this->booking(1000, 100);
        app(BookingPayments::class)->recordPayment($b, 1000, 'cash', null, null, null, $this->vendor->id);
        $this->actingAs($this->vendor);
        $r = app(\Modules\TourPay\Services\AccountStatement::class)->build(now()->startOfMonth(), now());
        $this->assertEquals(100, $r['owed']['USD']);
        $this->assertEquals(1000, $r['totals']['USD']['net'], 'commission is owed, it did not leave the account');
        $this->get(route('tourpay.vendor.statement'))->assertOk()->assertSee('Commission owed to platform')->assertSee('Rates By Exchange Rate API');
    }
}
