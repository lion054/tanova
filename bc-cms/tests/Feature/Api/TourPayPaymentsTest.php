<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\TourPay\Emails\PaymentReceiptEmail;
use Modules\TourPay\Models\Attempt;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\Gateways\Gateway;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\InvoiceRuleException;
use Modules\TourPay\Services\PayOnline;
use Tests\ApiTestCase;

/** Getting paid: each vendor's own gateway, confirmed by asking the gateway, once; bank transfers that wait; instalments. */
class TourPayPaymentsTest extends ApiTestCase
{
    private InvoiceBook $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->book = app(InvoiceBook::class);
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);
    }

    private function invoice(int $price = 400, ?int $vendorId = null): Invoice
    {
        $vendorId ??= $this->vendor->id;
        \Illuminate\Support\Facades\Auth::setUser(\App\User::find($vendorId));
        $i = Invoice::withoutVendorScope()->create([
            'vendor_id' => $vendorId, 'author_id' => $vendorId, 'type' => 'invoice', 'status' => 'draft', 'client_name' => 'Ann Ray', 'client_email' => 'ann@example.com', 'currency' => 'USD',
            'tax_rate' => 0, 'tax_mode' => 'exclusive', 'issue_date' => now()->toDateString(), 'due_date' => now()->addDays(40)->toDateString(), 'pay_token' => (string) \Illuminate\Support\Str::uuid(),
            'invoice_number' => Invoice::generateNumber($vendorId, 'invoice'),
        ]);
        $this->book->addItem($i, 'Tour', 1, $price);
        $this->book->issue($i->fresh());
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);

        return $i->fresh();
    }

    private function gateways(array $g): void
    {
        Setting::forVendor($this->vendor->id)->update(['gateways' => $g]);
    }

    public function test_keys_are_encrypted_hidden_and_a_gateway_is_only_on_when_fully_set_up(): void
    {
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc'], 'paypal' => ['enabled' => true, 'client_id' => 'only-id'], 'paystack' => ['enabled' => false, 'secret_key' => 'x']]);
        $s = Setting::forVendor($this->vendor->id);
        $this->assertSame(['stripe' => 'Card (Stripe)'], $s->enabledGateways(), 'PayPal lacks its secret and Paystack is off');
        $this->assertStringNotContainsString('sk_test_abc', (string) \DB::table('bc_tourpay_settings')->where('vendor_id', $this->vendor->id)->value('gateways'), 'stored encrypted');
        $this->assertArrayNotHasKey('gateways', $s->toArray());
        $this->assertSame([], Setting::forVendor($this->other->id)->enabledGateways(), 'another vendor has none: nothing is shared');
    }

    public function test_stripe_pay_flow_records_the_payment_once_from_the_gateways_answer(): void
    {
        Mail::fake();
        $inv = $this->invoice(400);
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc']]);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.test/c/cs_test_1'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response(['id' => 'cs_test_1', 'payment_status' => 'paid', 'status' => 'complete', 'amount_total' => 40000, 'currency' => 'usd'], 200),
        ]);
        auth()->logout();

        $r = $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'stripe', 'choice' => 'balance'])->assertRedirect('https://checkout.stripe.test/c/cs_test_1');
        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/checkout/sessions') && $req->hasHeader('Authorization', 'Bearer sk_test_abc') && ($req->data()['line_items'][0]['price_data']['unit_amount'] ?? 0) === 40000);
        $this->assertSame('started', Attempt::withoutVendorScope()->where('reference', 'cs_test_1')->value('status'));
        $this->assertEquals(0, $inv->fresh()->amount_paid, 'nothing counts until the gateway confirms');

        $this->get("/tourpay/pay/{$inv->pay_token}/return/stripe?session_id=cs_test_1")->assertRedirect();
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertEquals(400, $inv->fresh()->amount_paid);
        $this->assertSame('gateway', $inv->fresh()->payments()->first()->source);
        Mail::assertSent(PaymentReceiptEmail::class);

        // Coming back again, or the background check, does not count it twice.
        $this->get("/tourpay/pay/{$inv->pay_token}/return/stripe?session_id=cs_test_1")->assertRedirect();
        $this->assertSame(1, $inv->fresh()->payments()->count());
        $this->assertSame(0, app(PayOnline::class)->reconcile());
    }

    public function test_a_guest_cannot_claim_a_payment_the_gateway_did_not_confirm(): void
    {
        $inv = $this->invoice(400);
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc']]);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_2', 'url' => 'https://checkout.stripe.test/x'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_test_2' => Http::response(['id' => 'cs_test_2', 'payment_status' => 'unpaid', 'status' => 'open', 'amount_total' => 40000, 'currency' => 'usd'], 200),
        ]);
        auth()->logout();
        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'stripe']);
        $this->get("/tourpay/pay/{$inv->pay_token}/return/stripe?session_id=cs_test_2")->assertRedirect();
        $this->assertEquals(0, $inv->fresh()->amount_paid);
        $this->get("/tourpay/pay/{$inv->pay_token}/return/stripe?session_id=made-up")->assertRedirect()->assertSessionHas('error');
        $this->assertEquals(0, $inv->fresh()->amount_paid);
    }

    public function test_a_gateway_that_is_off_or_belongs_to_nobody_cannot_be_used(): void
    {
        $inv = $this->invoice(100);
        $this->gateways(['stripe' => ['enabled' => false, 'secret_key' => 'sk_test_abc']]);
        Http::fake();
        auth()->logout();
        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'stripe'])->assertRedirect()->assertSessionHas('error');
        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'paypal'])->assertRedirect()->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_the_wrong_currency_from_a_gateway_is_never_recorded(): void
    {
        $inv = $this->invoice(400);
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc']]);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_x', 'url' => 'https://x.test'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_x' => Http::response(['payment_status' => 'paid', 'status' => 'complete', 'amount_total' => 40000, 'currency' => 'eur'], 200),
        ]);
        auth()->logout();
        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'stripe']);
        $this->get("/tourpay/pay/{$inv->pay_token}/return/stripe?session_id=cs_x");
        $this->assertEquals(0, $inv->fresh()->amount_paid);
        $this->assertSame('failed', Attempt::withoutVendorScope()->where('reference', 'cs_x')->value('status'));
    }

    public function test_paypal_and_paystack_start_and_confirm(): void
    {
        $inv = $this->invoice(250);
        $this->gateways(['paypal' => ['enabled' => true, 'client_id' => 'id', 'client_secret' => 'sec', 'mode' => 'sandbox'], 'paystack' => ['enabled' => true, 'secret_key' => 'sk_test_ps']]);
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'T'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'ORD1', 'links' => [['rel' => 'payer-action', 'href' => 'https://paypal.test/approve']]], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORD1/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [['status' => 'COMPLETED', 'amount' => ['value' => '100.00', 'currency_code' => 'USD']]]]]]], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORD1' => Http::response(['status' => 'APPROVED'], 200),
            'api.paystack.co/transaction/initialize' => Http::response(['data' => ['authorization_url' => 'https://paystack.test/pay', 'reference' => 'PS1']], 200),
            'api.paystack.co/transaction/verify/PS1' => Http::response(['data' => ['status' => 'success', 'amount' => 15000, 'currency' => 'USD']], 200),
        ]);
        auth()->logout();

        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'paypal'])->assertRedirect('https://paypal.test/approve');
        $this->get("/tourpay/pay/{$inv->pay_token}/return/paypal?token=ORD1");
        $this->assertEquals(100, $inv->fresh()->amount_paid, 'PayPal captured 100 of the 250');
        $this->assertSame('part_paid', $inv->fresh()->status);

        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'paystack'])->assertRedirect('https://paystack.test/pay');
        $this->get("/tourpay/pay/{$inv->pay_token}/return/paystack?reference=PS1");
        $this->assertEquals(250, $inv->fresh()->amount_paid);
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_minor_units(): void
    {
        $this->assertSame(1250, Gateway::minorUnits(12.5, 'USD'));
        $this->assertSame(500, Gateway::minorUnits(500, 'UGX'));
        $this->assertSame(12.5, Gateway::fromMinor(1250, 'usd'));
    }

    public function test_the_connection_test_reports_bad_keys_plainly(): void
    {
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_bad']]);
        Http::fake(['api.stripe.com/v1/balance' => Http::sequence()->push(['error' => ['message' => 'Invalid API Key']], 401)->push(['available' => []], 200)]);
        $this->actingAs($this->vendor);
        $this->postJson('/user/tourpay/settings/gateways/stripe/test')->assertStatus(422)->assertJsonPath('ok', false);
        $this->postJson('/user/tourpay/settings/gateways/bitcoin/test')->assertNotFound();
        $this->postJson('/user/tourpay/settings/gateways/stripe/test')->assertOk()->assertJsonPath('ok', true);
    }

    public function test_saving_gateway_settings_keeps_a_secret_when_the_field_is_left_blank(): void
    {
        $this->actingAs($this->vendor);
        $save = fn (array $g) => $this->post('/user/tourpay/settings', ['section' => 'payments', 'bank_enabled' => 1, 'gateways' => $g])->assertRedirect(route('tourpay.vendor.settings.page') . '#payments');
        $save(['stripe' => ['enabled' => 1, 'secret_key' => 'sk_live_one']]);
        $save(['stripe' => ['enabled' => 1, 'secret_key' => '']]);
        $this->assertSame('sk_live_one', Setting::forVendor($this->vendor->id)->gateway('stripe')['secret_key']);
        $save(['stripe' => ['secret_key' => 'sk_live_two']]);
        $s = Setting::forVendor($this->vendor->id);
        $this->assertSame('sk_live_two', $s->gateway('stripe')['secret_key']);
        $this->assertEmpty($s->gateway('stripe')['enabled']);
        $this->get('/user/tourpay/settings')->assertOk()->assertDontSee('sk_live_two', false)->assertSee('saved (leave blank to keep)', false);
    }

    public function test_a_bank_transfer_waits_for_the_vendor_and_only_counts_once_confirmed(): void
    {
        Storage::fake('local');
        Mail::fake();
        $inv = $this->invoice(300);
        auth()->logout();
        $this->post("/tourpay/pay/{$inv->pay_token}/transfer", ['amount' => 300, 'reference' => 'FNB-77', 'proof' => UploadedFile::fake()->image('slip.jpg')])->assertRedirect()->assertSessionHas('success');
        $p = $inv->fresh()->payments()->first();
        $this->assertSame('pending', $p->status);
        $this->assertNotNull($p->proof_path);
        $this->assertEquals(0, $inv->fresh()->amount_paid);
        $this->assertSame('sent', $inv->fresh()->status);

        $this->post("/tourpay/pay/{$inv->pay_token}/transfer", ['amount' => 999, 'reference' => 'X'])->assertSessionHas('error');
        $this->post("/tourpay/pay/{$inv->pay_token}/transfer", ['amount' => 5, 'reference' => 'X', 'proof' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('proof');

        $this->actingAs($this->vendor);
        $this->get("/user/tourpay/{$inv->id}/payments/{$p->id}/proof")->assertOk();
        $this->post("/user/tourpay/{$inv->id}/payments/{$p->id}/approve")->assertRedirect();
        $this->assertSame('paid', $inv->fresh()->status);
        Mail::assertSent(PaymentReceiptEmail::class);
        $this->post("/user/tourpay/{$inv->id}/payments/{$p->id}/approve")->assertSessionHas('error');
    }

    public function test_a_rejected_transfer_never_counts_and_another_vendor_cannot_touch_it(): void
    {
        $inv = $this->invoice(300);
        $p = $this->book->reportTransfer($inv, 300, 'REF');
        $theirs = $this->invoice(100, $this->other->id);
        $this->actingAs($this->vendor);
        $this->post("/user/tourpay/{$theirs->id}/payments/{$p->id}/approve")->assertNotFound();
        $this->post("/user/tourpay/{$inv->id}/payments/{$p->id}/reject")->assertRedirect();
        $this->assertSame('rejected', $p->fresh()->status);
        $this->assertEquals(0, $inv->fresh()->amount_paid);
    }

    public function test_deposit_and_balance_schedule_follows_the_payments_and_offers_the_next_amount(): void
    {
        $inv = $this->invoice(1000);
        $this->book->setSchedule($inv, 'deposit', 30, null, 14);
        $rows = $this->book->schedule($inv->fresh());
        $this->assertSame([300.0, 700.0], array_column($rows, 'amount'));
        $this->assertEquals(now()->addDays(26)->toDateString(), $rows[1]['due_date']->toDateString(), 'balance is due 14 days before the due date');
        $this->assertEquals(300, app(PayOnline::class)->amounts($inv->fresh())['next']['amount']);

        $this->book->recordPayment($inv->fresh(), 300, 'bank', now()->toDateString());
        $rows = $this->book->schedule($inv->fresh());
        $this->assertSame([0.0, 700.0], array_column($rows, 'remaining'));
        $this->assertEquals(700, app(PayOnline::class)->amounts($inv->fresh())['next']['amount']);

        $this->book->recordPayment($inv->fresh(), 100, 'bank', now()->toDateString());
        $this->assertEquals(600, $this->book->nextDue($inv->fresh())['remaining'], 'extra money goes to the next instalment');

        $this->book->setSchedule($inv->fresh(), 'split', null, 3);
        $this->assertCount(3, $this->book->schedule($inv->fresh()));
        $this->book->setSchedule($inv->fresh(), 'none');
        $this->assertSame([], $this->book->schedule($inv->fresh()));
    }

    public function test_the_vendor_sets_a_schedule_from_the_portal_and_the_guest_pays_the_deposit_online(): void
    {
        $inv = $this->invoice(1000);
        $this->actingAs($this->vendor);
        $this->post("/user/tourpay/{$inv->id}/schedule", ['mode' => 'deposit', 'percent' => 25, 'balance_days' => 7])->assertRedirect();
        $this->assertCount(2, $inv->fresh()->installments);

        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc']]);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_dep', 'url' => 'https://checkout.stripe.test/dep'], 200),
            'api.stripe.com/v1/checkout/sessions/cs_dep' => Http::response(['payment_status' => 'paid', 'status' => 'complete', 'amount_total' => 25000, 'currency' => 'usd'], 200),
        ]);
        auth()->logout();
        $this->get('/tourpay/pay/' . $inv->pay_token)->assertOk()->assertSee('Deposit', false)->assertSee('250.00', false);
        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'stripe', 'choice' => 'next']);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'checkout/sessions') && ($r->data()['line_items'][0]['price_data']['unit_amount'] ?? 0) === 25000);
        $this->get("/tourpay/pay/{$inv->pay_token}/return/stripe?session_id=cs_dep");
        $this->assertEquals(250, $inv->fresh()->amount_paid);
        $this->assertSame('part_paid', $inv->fresh()->status);
    }

    public function test_reconcile_finds_a_payment_the_guest_never_came_back_for(): void
    {
        $inv = $this->invoice(400);
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc']]);
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_late' => Http::response(['payment_status' => 'paid', 'status' => 'complete', 'amount_total' => 40000, 'currency' => 'usd'], 200)]);
        $a = Attempt::create(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'gateway' => 'stripe', 'reference' => 'cs_late', 'amount' => 400, 'currency' => 'USD', 'status' => 'started']);
        \DB::table('bc_tourpay_attempts')->where('id', $a->id)->update(['created_at' => now()->subMinutes(30)]);
        $this->assertSame(1, app(PayOnline::class)->reconcile());
        $this->assertSame('paid', $inv->fresh()->status);

        $old = Attempt::create(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'gateway' => 'stripe', 'reference' => 'cs_old', 'amount' => 1, 'currency' => 'USD', 'status' => 'started']);
        \DB::table('bc_tourpay_attempts')->where('id', $old->id)->update(['created_at' => now()->subDays(5)]);
        app(PayOnline::class)->reconcile();
        $this->assertSame('expired', $old->fresh()->status);
    }

    public function test_an_expired_or_paid_or_draft_invoice_cannot_be_paid_online(): void
    {
        $this->gateways(['stripe' => ['enabled' => true, 'secret_key' => 'sk_test_abc']]);
        Http::fake();
        auth()->logout();
        $paid = $this->invoice(100);
        $this->book->markPaid($paid->fresh());
        $this->post("/tourpay/pay/{$paid->pay_token}/online", ['gateway' => 'stripe'])->assertSessionHas('error');
        $draft = Invoice::withoutVendorScope()->create(['vendor_id' => $this->vendor->id, 'author_id' => $this->vendor->id, 'type' => 'invoice', 'status' => 'draft', 'currency' => 'USD', 'client_name' => 'D', 'issue_date' => now()->toDateString(), 'pay_token' => 'draft-token-1', 'invoice_number' => 'INV-D-1', 'total' => 50]);
        $this->post('/tourpay/pay/draft-token-1/online', ['gateway' => 'stripe'])->assertSessionHas('error');
        Http::assertNothingSent();
    }
}
