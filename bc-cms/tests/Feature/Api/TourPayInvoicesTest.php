<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\InvoiceRuleException;
use Tests\ApiTestCase;

/** TourPay is the one invoicing module: numbers, the ledger, quotations, the guest page, and the tenant walls. */
class TourPayInvoicesTest extends ApiTestCase
{
    private InvoiceBook $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->book = app(InvoiceBook::class);
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);
    }

    private function invoice(array $more = [], array $items = [['Gorge swing', 2, 200]], ?int $vendorId = null): Invoice
    {
        $vendorId ??= $this->vendor->id;
        $i = Invoice::withoutVendorScope()->create($more + [
            'vendor_id' => $vendorId, 'author_id' => $vendorId, 'type' => 'invoice', 'status' => 'draft', 'client_name' => 'Ann Ray', 'client_email' => 'ann@example.com',
            'currency' => 'USD', 'tax_rate' => 0, 'tax_mode' => 'exclusive', 'issue_date' => now()->toDateString(), 'pay_token' => (string) \Illuminate\Support\Str::uuid(),
            'invoice_number' => Invoice::generateNumber($vendorId, $more['type'] ?? 'invoice'),
        ]);
        foreach ($items as [$n, $q, $p]) {
            \Illuminate\Support\Facades\Auth::setUser(\App\User::find($vendorId));
            $this->book->addItem($i, $n, $q, $p);
        }
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);

        return $i->fresh();
    }

    public function test_numbers_run_per_vendor_and_per_kind_and_the_prefix_is_the_vendors_own(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $q = $this->invoice(['type' => 'quotation']);
        $other = $this->invoice([], [['x', 1, 1]], $this->other->id);
        $y = date('Y');
        $this->assertSame("INV-{$y}-001", $a->invoice_number);
        $this->assertSame("INV-{$y}-002", $b->invoice_number);
        $this->assertSame("QUO-{$y}-001", $q->invoice_number);
        $this->assertSame("INV-{$y}-001", $other->invoice_number, 'another business starts at 001 as well');

        Setting::forVendor($this->vendor->id)->update(['invoice_prefix' => 'lux']);
        $this->assertSame("LUX-{$y}-003", Invoice::generateNumber($this->vendor->id, 'invoice'));
    }

    public function test_the_ledger_drives_the_status_and_totals(): void
    {
        $i = $this->invoice([], [['Tour', 1, 400]]);
        $this->assertSame('draft', $i->status);
        $this->book->issue($i);
        $this->assertSame('sent', $i->fresh()->status);

        $p = $this->book->recordPayment($i->fresh(), 150, 'bank', now()->toDateString(), 'EFT1');
        $this->assertSame('part_paid', $i->fresh()->status);
        $this->assertEquals(250, $i->fresh()->balance());

        $this->book->recordPayment($i->fresh(), 250, 'cash', now()->toDateString());
        $this->assertSame('paid', $i->fresh()->status);

        $this->book->removePayment($i->fresh(), $p);
        $this->assertSame('part_paid', $i->fresh()->status, 'removing a payment reopens the balance');
    }

    public function test_overpaying_and_nonsense_payments_are_refused(): void
    {
        $i = $this->invoice([], [['Tour', 1, 100]]);
        $this->book->issue($i);
        foreach ([[500, 'exceeds_balance'], [0, 'invalid_amount'], [-5, 'invalid_amount']] as [$amt, $code]) {
            try { $this->book->recordPayment($i->fresh(), $amt, 'cash', now()->toDateString()); $this->fail("$amt was accepted"); }
            catch (InvoiceRuleException $e) { $this->assertSame($code, $e->errorCode); }
        }
        $this->assertEquals(0, $i->fresh()->amount_paid);
    }

    public function test_mark_paid_records_the_balance_and_the_booking_follows_the_money_but_is_never_completed(): void
    {
        \Illuminate\Support\Facades\Event::fake([\Modules\Booking\Events\BookingUpdatedEvent::class]);
        $booking = DB::table('bc_bookings')->insertGetId(['code' => 'ZZ1', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => 1, 'total_guests' => 1, 'total' => 100, 'status' => 'unpaid', 'email' => 'a@b.co', 'start_date' => now()->addDays(9), 'created_at' => now(), 'updated_at' => now()]);
        $i = $this->invoice(['booking_id' => $booking], [['Tour', 1, 100]]);
        $this->book->issue($i);
        $this->book->recordPayment($i->fresh(), 30, 'cash', now()->toDateString());
        $paid = $this->book->markPaid($i->fresh(), 'bank');
        $this->assertSame('paid', $paid->status);
        $this->assertEquals(2, $paid->payments()->count());
        // One ledger: the money paid on the invoice is money paid on the booking, so the booking shows paid. It is never marked completed: the trip still has to happen.
        $this->assertSame('paid', DB::table('bc_bookings')->where('id', $booking)->value('status'));
        $this->assertEquals(100, DB::table('bc_bookings')->where('id', $booking)->value('paid'));
    }

    public function test_tax_inclusive_and_exclusive_and_discount(): void
    {
        $inc = $this->invoice(['tax_rate' => 15, 'tax_mode' => 'inclusive'], [['Safari', 1, 1150]]);
        $this->assertEquals(1150, $inc->total);
        $this->assertEquals(150, $inc->tax_amount);
        $this->assertEquals(1000, $inc->subtotal);

        $exc = $this->invoice(['tax_rate' => 10, 'tax_mode' => 'exclusive', 'discount' => 100], [['Safari', 1, 1000]]);
        $exc->recalculate();
        $this->assertEquals(90, $exc->fresh()->tax_amount);
        $this->assertEquals(990, $exc->fresh()->total);
    }

    public function test_quotation_is_answered_once_and_converts_to_one_invoice(): void
    {
        $q = $this->invoice(['type' => 'quotation', 'valid_days' => 14], [['Zambia trip', 2, 1450]]);
        $this->book->issue($q);
        try { $this->book->recordPayment($q->fresh(), 10, 'cash', now()->toDateString()); $this->fail('paid a quotation'); }
        catch (InvoiceRuleException $e) { $this->assertSame('not_an_invoice', $e->errorCode); }

        $this->assertSame('accepted', $this->book->answerQuotation($q->fresh(), true)->status);
        try { $this->book->answerQuotation($q->fresh(), false); $this->fail('answered twice'); }
        catch (InvoiceRuleException $e) { $this->assertSame('not_open', $e->errorCode); }

        $inv = $this->book->convertQuotation($q->fresh());
        $this->assertSame('invoice', $inv->type);
        $this->assertSame($q->id, $inv->parent_id);
        $this->assertEquals(2900, $inv->total);
        try { $this->book->convertQuotation($q->fresh()); $this->fail('converted twice'); }
        catch (InvoiceRuleException $e) { $this->assertSame('already_converted', $e->errorCode); }
    }

    public function test_an_expired_quotation_cannot_be_accepted(): void
    {
        $q = $this->invoice(['type' => 'quotation', 'valid_days' => 7, 'issue_date' => now()->subDays(20)->toDateString()], [['Trip', 1, 100]]);
        $this->book->issue($q);
        try { $this->book->answerQuotation($q->fresh(), true); $this->fail('accepted an expired quotation'); }
        catch (InvoiceRuleException $e) { $this->assertSame('expired', $e->errorCode); }
        $this->assertSame('expired', $q->fresh()->status);
    }

    public function test_locked_documents_and_only_drafts_can_be_deleted(): void
    {
        $i = $this->invoice([], [['Tour', 1, 100]]);
        $this->book->issue($i);
        try { $this->book->deleteDraft($i->fresh()); $this->fail('deleted an issued invoice'); }
        catch (InvoiceRuleException $e) { $this->assertSame('not_a_draft', $e->errorCode); }
        $this->book->markPaid($i->fresh());
        try { $this->book->addItem($i->fresh(), 'x', 1, 1); $this->fail('edited a paid invoice'); }
        catch (InvoiceRuleException $e) { $this->assertSame('invoice_locked', $e->errorCode); }
    }

    public function test_overdue_is_a_fact_about_the_due_date(): void
    {
        $late = $this->invoice(['due_date' => now()->subDays(3)->toDateString()], [['Tour', 1, 100]]);
        $this->book->issue($late);
        $fine = $this->invoice(['due_date' => now()->addDays(3)->toDateString()], [['Tour', 1, 100]]);
        $this->book->issue($fine);
        $draft = $this->invoice(['due_date' => now()->subDays(3)->toDateString()], [['Tour', 1, 100]]);
        $this->assertTrue($late->fresh()->isOverdue());
        $this->assertSame('overdue', $late->fresh()->display_status);
        $this->assertFalse($fine->fresh()->isOverdue());
        $this->assertFalse($draft->isOverdue(), 'a draft is not overdue');
        $this->assertSame([$late->id], Invoice::overdue()->pluck('id')->all());
    }

    public function test_one_vendor_never_sees_or_touches_another_vendors_invoices(): void
    {
        $mine = $this->invoice();
        $theirs = $this->invoice([], [['x', 1, 1]], $this->other->id);
        $this->assertSame([$mine->id], Invoice::pluck('id')->all(), 'the tenant scope hides the other vendor');
        $this->assertNull(Invoice::find($theirs->id));
    }

    public function test_the_portal_screens_are_walled_per_vendor_and_the_ledger_works_over_http(): void
    {
        $mine = $this->invoice([], [['Tour', 1, 100]]);
        $this->book->issue($mine);
        $theirs = $this->invoice(['client_name' => 'Zed Otherperson'], [['x', 1, 1]], $this->other->id);
        $this->actingAs($this->vendor);
        // (both vendors have an INV-…-001: numbers are per business, so tell them apart by client)
        $this->get('/user/tourpay')->assertOk()->assertSee('Ann Ray')->assertDontSee('Zed Otherperson');
        $this->get("/user/tourpay/{$mine->id}/view")->assertOk()->assertSee('Record a payment');
        $this->get("/user/tourpay/{$theirs->id}/view")->assertNotFound();
        $this->post("/user/tourpay/{$theirs->id}/payments", ['amount' => 1, 'method' => 'cash'])->assertNotFound();
        $this->post("/user/tourpay/{$theirs->id}/void")->assertNotFound();

        $this->post("/user/tourpay/{$mine->id}/payments", ['amount' => 40, 'method' => 'bank', 'reference' => 'EFT9'])->assertRedirect();
        $this->assertEquals(40, $mine->fresh()->amount_paid);
        $this->post("/user/tourpay/{$mine->id}/payments", ['amount' => 999, 'method' => 'bank'])->assertSessionHas('error');
        $this->assertEquals(40, $mine->fresh()->amount_paid);
    }

    public function test_the_guest_page_shows_the_balance_marks_it_viewed_and_lets_a_guest_answer_a_quotation(): void
    {
        $inv = $this->invoice([], [['Tour', 1, 100]]);
        $this->book->issue($inv);
        $this->book->recordPayment($inv->fresh(), 30, 'cash', now()->toDateString());
        $this->assertNull($inv->fresh()->viewed_at);

        auth()->logout();
        $this->get('/tourpay/pay/' . $inv->pay_token)->assertOk()->assertSee('Balance due', false)->assertSee('70.00', false);
        $this->assertNotNull($inv->fresh()->viewed_at);
        $this->get('/tourpay/pay/not-a-real-token')->assertNotFound();

        $q = $this->invoice(['type' => 'quotation', 'valid_days' => 14], [['Trip', 1, 500]]);
        $this->book->issue($q);
        $this->post("/tourpay/pay/{$q->pay_token}/accept")->assertRedirect();
        $this->assertSame('accepted', $q->fresh()->status);
        $this->assertContains($this->post("/tourpay/pay/{$q->pay_token}/hack")->status(), [404, 405]);
    }

    public function test_sending_reports_failure_and_only_marks_sent_when_it_went(): void
    {
        $inv = $this->invoice(['client_email' => 'ann@example.com'], [['Tour', 1, 100]]);
        $this->actingAs($this->vendor);
        Mail::fake();
        $this->post("/user/tourpay/{$inv->id}/send-email", ['email' => 'ann@example.com'])->assertSessionHas('success');
        Mail::assertSent(\Modules\TourPay\Emails\InvoiceEmail::class);
        $this->assertSame('sent', $inv->fresh()->status);
        $this->assertNotNull($inv->fresh()->sent_at);

        $empty = $this->invoice([], []);
        $this->post("/user/tourpay/{$empty->id}/send-email", ['email' => 'ann@example.com'])->assertSessionHas('error');
        $this->assertSame('draft', $empty->fresh()->status);
    }

    public function test_the_portal_form_creates_an_invoice_with_items_and_defaults(): void
    {
        $this->actingAs($this->vendor);
        Setting::forVendor($this->vendor->id)->update(['default_currency' => 'ZAR', 'default_terms' => '30% deposit']);
        $this->get('/user/tourpay/create')->assertOk()->assertSee('30% deposit');

        $this->post('/user/tourpay/store/-1', [
            'type' => 'invoice', 'client_name' => 'Chipo Dube', 'currency' => 'zar', 'tax_rate' => 15, 'tax_mode' => 'inclusive', 'discount' => 0,
            'items' => [['name' => 'Safari', 'quantity' => 2, 'unit_price' => 575], ['name' => '', 'quantity' => 1, 'unit_price' => 5]],
        ])->assertRedirect();
        $i = Invoice::latest('id')->first();
        $this->assertSame('ZAR', $i->currency);
        $this->assertEquals(1150, $i->total);
        $this->assertSame(1, $i->items()->count(), 'a row without a name is dropped');
        $this->assertSame($this->vendor->id, (int) $i->vendor_id);
        $this->post('/user/tourpay/store/-1', ['type' => 'invoice', 'client_name' => 'X', 'currency' => 'USD', 'items' => [['name' => '', 'quantity' => 1, 'unit_price' => 1]]])->assertSessionHasErrors('items');
    }

    public function test_duplicate_makes_a_fresh_draft_with_a_new_number_and_link(): void
    {
        $i = $this->invoice([], [['Tour', 2, 50]]);
        $this->book->issue($i);
        $this->book->markPaid($i->fresh());
        $copy = $this->book->duplicate($i->fresh());
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($i->invoice_number, $copy->invoice_number);
        $this->assertNotSame($i->pay_token, $copy->pay_token);
        $this->assertEquals(100, $copy->total);
        $this->assertEquals(0, $copy->amount_paid);
    }
}
