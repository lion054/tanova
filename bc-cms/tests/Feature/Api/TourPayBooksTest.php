<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\InvoiceRuleException;
use Modules\TourPay\Services\Profit;
use Modules\TourPay\Services\Reminders;
use Modules\TourPay\Services\Reports;
use Tests\ApiTestCase;

/** Several taxes, credit notes and refunds, reminders, reports, supplier bills and profit. */
class TourPayBooksTest extends ApiTestCase
{
    private InvoiceBook $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->book = app(InvoiceBook::class);
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);
    }

    private function invoice(array $more = [], array $items = [['Tour', 1, 1000]], ?int $vendorId = null, bool $issue = true): Invoice
    {
        $vendorId ??= $this->vendor->id;
        \Illuminate\Support\Facades\Auth::setUser(\App\User::find($vendorId));
        $i = Invoice::withoutVendorScope()->create($more + [
            'vendor_id' => $vendorId, 'author_id' => $vendorId, 'type' => 'invoice', 'status' => 'draft', 'client_name' => 'Ann Ray', 'client_email' => 'ann@example.com', 'currency' => 'USD',
            'tax_rate' => 0, 'tax_mode' => 'exclusive', 'issue_date' => now()->toDateString(), 'due_date' => now()->addDays(30)->toDateString(), 'pay_token' => (string) \Illuminate\Support\Str::uuid(),
            'invoice_number' => Invoice::generateNumber($vendorId, $more['type'] ?? 'invoice'),
        ]);
        foreach ($items as [$n, $q, $p]) {
            $this->book->addItem($i, $n, $q, $p);
        }
        if ($issue && $items) {
            $this->book->issue($i->fresh());
        }
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);

        return $i->fresh();
    }

    public function test_several_named_taxes_are_shared_out_and_add_up(): void
    {
        $exc = $this->invoice(['tax_mode' => 'exclusive', 'tax_lines' => [['name' => 'VAT', 'rate' => 15], ['name' => 'Tourism levy', 'rate' => 1]]], [['Safari', 1, 1000]], null, false);
        $exc->recalculate();
        $x = $exc->fresh();
        $this->assertEquals(160, $x->tax_amount);
        $this->assertEquals(1160, $x->total);
        $this->assertEquals(16, $x->tax_rate);
        $this->assertEquals([150, 10], array_column($x->tax_lines, 'amount'));

        $inc = $this->invoice(['tax_mode' => 'inclusive', 'tax_lines' => [['name' => 'VAT', 'rate' => 15], ['name' => 'Levy', 'rate' => 5]]], [['Safari', 1, 1200]], null, false);
        $inc->recalculate();
        $this->assertEquals(1200, $inc->fresh()->total);
        $this->assertEquals(200, $inc->fresh()->tax_amount);
        $this->assertEquals(200.0, array_sum(array_column($inc->fresh()->tax_lines, 'amount')));
    }

    public function test_a_credit_note_lowers_what_is_owed_and_a_paid_invoice_becomes_a_refund_due(): void
    {
        $inv = $this->invoice([], [['Tour', 1, 1000]]);
        $this->book->recordPayment($inv->fresh(), 400, 'bank', now()->toDateString());
        $cn = $this->book->creditNote($inv->fresh(), 300, 'Guest dropped an activity');
        $this->assertSame('credit_note', $cn->type);
        $this->assertStringStartsWith('CN-', $cn->invoice_number);
        $x = $inv->fresh();
        $this->assertEquals(300, $x->credit_total);
        $this->assertEquals(300, $x->balance(), '1000 - 300 credit - 400 paid');
        $this->assertSame('part_paid', $x->status);

        $this->book->recordPayment($x, 300, 'cash', now()->toDateString());
        $this->assertSame('paid', $inv->fresh()->status);

        // Credit the rest: the client has paid 700 for something now worth 0, so 700 is due back.
        $this->book->creditNote($inv->fresh(), null, 'Trip cancelled');
        $y = $inv->fresh();
        $this->assertEquals(1000, $y->credit_total);
        $this->assertEquals(700, $y->refundDue());
        try { $this->book->recordRefund($y, 800, 'bank', now()->toDateString()); $this->fail('refunded more than is due'); }
        catch (InvoiceRuleException $e) { $this->assertSame('exceeds_refund_due', $e->errorCode); }
        $this->book->recordRefund($y, 700, 'bank', now()->toDateString(), 'REF1');
        $z = $inv->fresh();
        $this->assertEquals(0, $z->refundDue());
        $this->assertEquals(0, $z->amount_paid);
        $this->assertSame('credited', $z->status);
        try { $this->book->creditNote($z, 1, 'again'); $this->fail('credited more than the invoice'); }
        catch (InvoiceRuleException $e) { $this->assertSame('exceeds_invoice', $e->errorCode); }
    }

    public function test_voiding_a_credit_note_gives_the_amount_back_to_the_invoice(): void
    {
        $inv = $this->invoice([], [['Tour', 1, 500]]);
        $cn = $this->book->creditNote($inv->fresh(), 200, 'Discount');
        $this->assertEquals(300, $inv->fresh()->balance());
        $this->book->void($cn->fresh());
        $this->assertEquals(500, $inv->fresh()->balance());
        $this->assertEquals(0, $inv->fresh()->credit_total);
    }

    public function test_credit_note_and_refund_screens_and_walls(): void
    {
        $inv = $this->invoice([], [['Tour', 1, 100]]);
        $this->book->markPaid($inv->fresh());
        $theirs = $this->invoice(['client_name' => 'Zed Other'], [['x', 1, 5]], $this->other->id);
        $this->actingAs($this->vendor);
        $this->post("/user/tourpay/{$theirs->id}/credit-note", ['reason' => 'x'])->assertNotFound();
        $this->post("/user/tourpay/{$inv->id}/credit-note", ['reason' => 'Cancelled', 'amount' => 40])->assertRedirect()->assertSessionHas('success');
        $this->assertEquals(40, $inv->fresh()->refundDue());
        $this->post("/user/tourpay/{$inv->id}/refund", ['amount' => 40, 'method' => 'bank'])->assertRedirect()->assertSessionHas('success');
        $this->get("/user/tourpay/{$inv->id}/view")->assertOk()->assertSee('refund', false);
        $this->get('/user/tourpay')->assertOk()->assertSee('Credit note', false);
    }

    public function test_reminders_are_off_unless_enabled_and_then_follow_the_rules(): void
    {
        Mail::fake();
        $soon = $this->invoice(['due_date' => now()->addDays(2)->toDateString()]);
        $late = $this->invoice(['due_date' => now()->subDays(10)->toDateString()]);
        $far = $this->invoice(['due_date' => now()->addDays(20)->toDateString()]);
        $paid = $this->invoice(['due_date' => now()->subDays(10)->toDateString()], [['x', 1, 5]]);
        $this->book->markPaid($paid->fresh());

        $this->assertSame(['sent' => 0, 'skipped' => 0, 'failed' => 0], app(Reminders::class)->run(), 'off by default: nothing goes out');
        Mail::assertNothingSent();

        Setting::forVendor($this->vendor->id)->update(['remind_enabled' => true, 'remind_before_days' => 3, 'remind_overdue_every' => 7, 'remind_max' => 3]);
        $ids = app(Reminders::class)->due(Setting::forVendor($this->vendor->id))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$soon->id, $late->id], $ids, 'due within 3 days and overdue, not far off and not paid');

        $r = app(Reminders::class)->run();
        $this->assertSame(2, $r['sent']);
        $this->assertSame(1, $late->fresh()->reminders_sent);
        $this->assertSame(0, app(Reminders::class)->run()['sent'], 'never twice in a day');

        \DB::table('bc_tourpay_invoices')->where('id', $late->id)->update(['last_reminded_at' => now()->subDays(8)]);
        $this->assertContains($late->id, app(Reminders::class)->due(Setting::forVendor($this->vendor->id))->pluck('id')->all(), 'overdue again after 7 days');
        \DB::table('bc_tourpay_invoices')->where('id', $late->id)->update(['last_reminded_at' => now()->subDays(8), 'reminders_sent' => 3]);
        $this->assertNotContains($late->id, app(Reminders::class)->due(Setting::forVendor($this->vendor->id))->pluck('id')->all(), 'stops at the limit');
    }

    public function test_reminders_never_touch_another_vendors_invoices_or_settings(): void
    {
        $mine = $this->invoice(['due_date' => now()->subDays(5)->toDateString()]);
        $theirs = $this->invoice(['due_date' => now()->subDays(5)->toDateString()], [['x', 1, 5]], $this->other->id);
        Setting::forVendor($this->other->id)->update(['remind_enabled' => true]);
        $due = app(Reminders::class)->due(Setting::forVendor($this->other->id))->pluck('id')->all();
        $this->assertSame([$theirs->id], $due);
        $this->assertNotContains($mine->id, $due);
    }

    public function test_receivables_are_grouped_by_currency_and_how_late(): void
    {
        $this->invoice(['due_date' => now()->addDays(5)->toDateString()], [['A', 1, 100]]);
        $this->invoice(['due_date' => now()->subDays(15)->toDateString(), 'client_name' => 'Bob', 'client_email' => 'bob@example.com'], [['B', 1, 200]]);
        $this->invoice(['due_date' => now()->subDays(70)->toDateString(), 'currency' => 'ZAR', 'client_name' => 'Chipo', 'client_email' => 'chipo@example.com'], [['C', 1, 5000]]);
        $r = app(Reports::class)->receivables();
        $this->assertEquals(300, $r['USD']['total']);
        $this->assertEquals(100, $r['USD']['buckets']['current']);
        $this->assertEquals(200, $r['USD']['buckets']['d30']);
        $this->assertEquals(5000, $r['ZAR']['buckets']['d90']);
        $this->assertSame('Bob', array_values($r['USD']['clients'])[0]['name'], 'biggest debtor first');
    }

    public function test_revenue_tax_and_statement(): void
    {
        $a = $this->invoice(['tax_mode' => 'exclusive', 'tax_lines' => [['name' => 'VAT', 'rate' => 10]]], [['Tour', 1, 1000]]);
        $this->book->recordPayment($a->fresh(), 500, 'bank', now()->toDateString(), 'R1');
        $cn = $this->book->creditNote($a->fresh(), 110, 'Refunded activity');

        $rev = app(Reports::class)->revenue(now()->startOfMonth(), now()->endOfMonth());
        $ym = now()->format('Y-m');
        $this->assertEquals(990, $rev[$ym]['USD']['invoiced'], '1100 invoiced less the 110 credit');
        $this->assertEquals(500, $rev[$ym]['USD']['received']);

        $tax = app(Reports::class)->tax(now()->startOfMonth(), now()->endOfMonth());
        $this->assertCount(1, $tax);
        $this->assertEquals(90, $tax[0]['tax'], '100 VAT on the invoice, 10 taken back by the credit note');

        $st = app(Reports::class)->statement('ann@example.com');
        $this->assertEquals(490, $st['balances']['USD'], '1100 billed, 500 paid, 110 credited');
        $this->assertSame(['invoice', 'credit note', 'payment'], array_column($st['events'], 'kind'), 'same-day order: billed, then credit, then payment');
    }

    public function test_reports_and_csv_are_walled_and_csv_cells_cannot_be_formulas(): void
    {
        $this->invoice(['client_name' => '=HYPERLINK("http://evil","x")'], [['Tour', 1, 100]]);
        $this->invoice(['client_name' => 'Zed Other'], [['x', 1, 5]], $this->other->id);
        $this->actingAs($this->vendor);
        foreach (['receivables', 'revenue', 'tax', 'profit'] as $tab) {
            $this->get('/user/tourpay/reports?tab=' . $tab)->assertOk()->assertDontSee('Zed Other');
        }
        $csv = $this->get('/user/tourpay/reports/receivables.csv')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Zed Other', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'a formula-looking name is written as text');
        $this->get('/user/tourpay/reports/statement.csv')->assertNotFound();
        $this->get('/user/tourpay/reports/bogus.csv')->assertNotFound();
    }

    private function booking(float $total = 1000, ?int $vendorId = null): Booking
    {
        $id = DB::table('bc_bookings')->insertGetId(['code' => 'BK' . uniqid(), 'vendor_id' => $vendorId ?? $this->vendor->id, 'object_model' => 'tour', 'object_id' => 1, 'total_guests' => 2, 'total' => $total, 'currency' => 'USD', 'status' => 'confirmed', 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'ann@example.com', 'start_date' => now()->addDays(20), 'created_at' => now(), 'updated_at' => now()]);

        return Booking::find($id);
    }

    public function test_bills_have_their_own_ledger_and_status(): void
    {
        $bill = Bill::create(['vendor_id' => $this->vendor->id, 'supplier_name' => 'Intercape', 'currency' => 'USD', 'total' => 300, 'bill_date' => now()->toDateString(), 'status' => 'open']);
        $this->actingAs($this->vendor);
        $this->post("/user/tourpay/bills/{$bill->id}/payments", ['amount' => 100, 'method' => 'bank'])->assertRedirect();
        $this->assertSame('part_paid', $bill->fresh()->status);
        $this->post("/user/tourpay/bills/{$bill->id}/payments", ['amount' => 500, 'method' => 'bank'])->assertSessionHas('error');
        $this->post("/user/tourpay/bills/{$bill->id}/payments", ['amount' => 200, 'method' => 'bank'])->assertRedirect();
        $this->assertSame('paid', $bill->fresh()->status);
        $this->delete("/user/tourpay/bills/{$bill->id}")->assertSessionHas('error');

        $other = Bill::withoutVendorScope()->create(['vendor_id' => $this->other->id, 'supplier_name' => 'Theirs', 'currency' => 'USD', 'total' => 9, 'bill_date' => now()->toDateString(), 'status' => 'open']);
        $this->post("/user/tourpay/bills/{$other->id}/payments", ['amount' => 1, 'method' => 'bank'])->assertNotFound();
        $this->post("/user/tourpay/bills/{$other->id}/void")->assertNotFound();
        $this->get('/user/tourpay/bills?tab=paid')->assertOk()->assertSee('Intercape')->assertDontSee('Theirs');
    }

    public function test_the_bill_form_validates_and_ignores_someone_elses_booking(): void
    {
        $theirBooking = $this->booking(100, $this->other->id);
        $this->actingAs($this->vendor);
        $this->post('/user/tourpay/bills', ['currency' => 'USD', 'total' => 50, 'bill_date' => now()->toDateString()])->assertSessionHasErrors('supplier_name');
        $this->post('/user/tourpay/bills', ['supplier_name' => 'Lodge', 'currency' => 'usd', 'total' => 50, 'bill_date' => now()->toDateString(), 'booking_id' => $theirBooking->id])->assertRedirect();
        $b = Bill::latest('id')->first();
        $this->assertSame('USD', $b->currency);
        $this->assertNull($b->booking_id, 'another vendor\'s booking cannot be attached');
    }

    public function test_profit_is_what_the_booking_is_billed_minus_its_supplier_bills(): void
    {
        $bk = $this->booking(1000);
        $this->assertEquals(1000, app(Profit::class)->forBooking($bk)['revenue'], 'no invoice yet: the booking total');

        $inv = $this->invoice(['booking_id' => $bk->id], [['Tour', 1, 900]]);
        Bill::create(['vendor_id' => $this->vendor->id, 'booking_id' => $bk->id, 'supplier_name' => 'Lodge', 'currency' => 'USD', 'total' => 400, 'bill_date' => now()->toDateString()]);
        Bill::create(['vendor_id' => $this->vendor->id, 'booking_id' => $bk->id, 'supplier_name' => 'Driver', 'currency' => 'USD', 'total' => 100, 'bill_date' => now()->toDateString(), 'status' => 'void']);
        $p = app(Profit::class)->forBooking($bk);
        $this->assertEquals(900, $p['revenue']);
        $this->assertEquals(400, $p['cost'], 'a voided bill does not count');
        $this->assertEquals(500, $p['profit']);
        $this->assertEquals(55.6, $p['margin']);

        $this->book->creditNote($inv->fresh(), 100, 'Discount');
        $this->assertEquals(800, app(Profit::class)->forBooking($bk)['revenue'], 'a credit note lowers revenue');

        Bill::create(['vendor_id' => $this->vendor->id, 'booking_id' => $bk->id, 'supplier_name' => 'Local', 'currency' => 'ZAR', 'total' => 1000, 'bill_date' => now()->toDateString()]);
        $this->assertTrue(app(Profit::class)->forBooking($bk)['mixed'], 'currencies are never added together');
    }

    public function test_the_booking_page_shows_its_profit(): void
    {
        $bk = $this->booking(1000);
        Bill::create(['vendor_id' => $this->vendor->id, 'booking_id' => $bk->id, 'supplier_name' => 'Lodge', 'currency' => 'USD', 'total' => 300, 'bill_date' => now()->toDateString()]);
        $this->actingAs($this->vendor);
        $this->get("/vendor/bookings/{$bk->id}/ops")->assertOk()->assertSee('Profit', false)->assertSee('700.00', false);
    }

    public function test_base_currency_totals_use_the_vendors_own_rates_and_never_guess(): void
    {
        $s = Setting::forVendor($this->vendor->id);
        $s->update(['base_currency' => 'usd', 'rates' => ['ZAR' => 0.05]]);
        $this->assertSame(50.0, Setting::forVendor($this->vendor->id)->toBase(1000, 'ZAR'));
        $this->assertSame(10.0, Setting::forVendor($this->vendor->id)->toBase(10, 'USD'));
        $this->assertNull(Setting::forVendor($this->vendor->id)->toBase(10, 'EUR'), 'no rate, no answer');
        $this->assertNull(Setting::forVendor($this->other->id)->toBase(10, 'ZAR'), 'another vendor has no base');
    }

    public function test_each_settings_section_saves_only_itself(): void
    {
        $this->actingAs($this->vendor);
        $this->post('/user/tourpay/settings', ['section' => 'currency', 'base_currency' => 'usd', 'rates_text' => "ZAR = 0.054\neur: 1.08\nnonsense\nGBP = -1"])->assertRedirect();
        $s = Setting::forVendor($this->vendor->id);
        $this->assertSame('USD', $s->base_currency);
        $this->assertEquals(['ZAR' => 0.054, 'EUR' => 1.08], $s->rates);

        $this->post('/user/tourpay/settings', ['section' => 'reminders', 'remind_enabled' => 1, 'remind_before_days' => 5, 'remind_channel' => 'whatsapp'])->assertRedirect();
        $s = Setting::forVendor($this->vendor->id);
        $this->assertTrue($s->remind_enabled);
        $this->assertSame(5, (int) $s->remind_before_days);
        $this->assertSame('whatsapp', $s->remind_channel);

        // Saving another section must not switch reminders off, or wipe the rates.
        $this->post('/user/tourpay/settings', ['section' => 'general', 'default_currency' => 'zar', 'invoice_prefix' => 'lux'])->assertRedirect();
        $s = Setting::forVendor($this->vendor->id);
        $this->assertTrue($s->remind_enabled);
        $this->assertEquals(['ZAR' => 0.054, 'EUR' => 1.08], $s->rates);
        $this->assertSame('ZAR', $s->default_currency);
        $this->assertSame('LUX', $s->prefixFor('invoice'));
        $this->post('/user/tourpay/settings', ['section' => 'general', 'invoice_prefix' => 'bad prefix!'])->assertSessionHasErrors('invoice_prefix');

        $this->post('/user/tourpay/settings', ['section' => 'reminders'])->assertRedirect();
        $this->assertFalse(Setting::forVendor($this->vendor->id)->remind_enabled, 'unchecked means off');
        $this->get('/user/tourpay/settings')->assertOk()->assertSee('Getting paid', false)->assertSee('Paynow', false)->assertSee('Pesapal', false)->assertSee('Selcom', false);
    }

    public function test_whatsapp_uses_the_vendors_own_channel_and_says_when_it_is_not_connected(): void
    {
        Http::fake();
        $inv = $this->invoice();
        $this->actingAs($this->vendor);
        $this->postJson("/user/tourpay/{$inv->id}/send-whatsapp", ['phone' => '+263771234567'])->assertStatus(422)->assertJsonPath('error', 'Connect your WhatsApp number under Integrations first.');
        Http::assertNothingSent();
    }

    public function test_a_schedule_is_worked_out_on_what_is_owed_and_follows_a_later_credit_note(): void
    {
        $inv = $this->invoice([], [['Trip', 1, 1000]]);
        $this->book->creditNote($inv->fresh(), 200, 'Discount');
        $this->book->setSchedule($inv->fresh(), 'deposit', 25, null, 7);
        $this->assertEquals([200, 600], array_column($this->book->schedule($inv->fresh()), 'amount'), '25% of the 800 still owed');

        $this->book->creditNote($inv->fresh(), 100, 'Another');
        $this->assertEquals(700, array_sum(array_column($this->book->schedule($inv->fresh()), 'amount')), 'the instalments follow the new amount owed');
        $this->assertEquals([200, 500], array_column($this->book->schedule($inv->fresh()), 'amount'), 'the last instalment takes the change');
    }
}
