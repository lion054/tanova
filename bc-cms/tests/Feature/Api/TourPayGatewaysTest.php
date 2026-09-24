<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Modules\TourPay\Models\Attempt;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\InvoiceBook;
use Tests\ApiTestCase;

/** Paynow (Zimbabwe), Pesapal and Selcom (Tanzania): started with the vendor's own keys, confirmed by asking the gateway. */
class TourPayGatewaysTest extends ApiTestCase
{
    private const KEY = 'int-key-123';

    private function invoice(string $currency = 'USD', int $price = 100): Invoice
    {
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);
        $i = Invoice::withoutVendorScope()->create(['vendor_id' => $this->vendor->id, 'author_id' => $this->vendor->id, 'type' => 'invoice', 'status' => 'draft', 'client_name' => 'Ann Ray', 'client_email' => 'ann@example.com', 'client_phone' => '0712345678',
            'currency' => $currency, 'tax_rate' => 0, 'tax_mode' => 'exclusive', 'issue_date' => now()->toDateString(), 'pay_token' => (string) \Illuminate\Support\Str::uuid(), 'invoice_number' => Invoice::generateNumber($this->vendor->id, 'invoice')]);
        app(InvoiceBook::class)->addItem($i, 'Tour', 1, $price);
        app(InvoiceBook::class)->issue($i->fresh());

        return $i->fresh();
    }

    private function gateways(array $g): void
    {
        Setting::forVendor($this->vendor->id)->update(['gateways' => $g]);
    }

    /** A Paynow message: the values in order, then the key, SHA512, in capitals. */
    private function paynowSigned(array $fields): string
    {
        return http_build_query($fields + ['hash' => strtoupper(hash('sha512', implode('', $fields) . self::KEY))]);
    }

    public function test_paynow_signs_what_it_sends_checks_what_it_gets_and_records_a_paid_poll(): void
    {
        $inv = $this->invoice('USD', 100);
        $this->gateways(['paynow' => ['enabled' => true, 'integration_id' => '1234', 'integration_key' => self::KEY]]);
        $init = $this->paynowSigned(['status' => 'Ok', 'browserurl' => 'https://www.paynow.co.zw/Payment/ConfirmPayment/9999', 'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc']);
        $paid = $this->paynowSigned(['reference' => 'X', 'paynowreference' => '55', 'amount' => '100.00', 'status' => 'Paid', 'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc']);
        Http::fake(['www.paynow.co.zw/interface/initiatetransaction' => Http::response($init, 200), 'www.paynow.co.zw/Interface/CheckPayment*' => Http::response($paid, 200)]);
        auth()->logout();

        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'paynow'])->assertRedirect('https://www.paynow.co.zw/Payment/ConfirmPayment/9999');
        Http::assertSent(function ($r) {
            if (!str_contains($r->url(), 'initiatetransaction')) { return false; }
            $d = $r->data();
            $values = implode('', array_values(array_diff_key($d, ['hash' => 1])));   // in the order sent

            return $d['id'] === '1234' && $d['amount'] === '100.00' && $d['status'] === 'Message' && hash_equals(strtoupper(hash('sha512', $values . self::KEY)), $d['hash']);
        });
        $this->assertSame('https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc', Attempt::withoutVendorScope()->latest('id')->value('reference'), 'the poll URL is kept as the reference');
        $this->assertEquals(0, $inv->fresh()->amount_paid);

        // The guest comes back with nothing to identify the payment: the latest open one for the invoice is used.
        $this->get("/tourpay/pay/{$inv->pay_token}/return/paynow")->assertRedirect();
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertEquals(100, $inv->fresh()->amount_paid);
    }

    public function test_paynow_refuses_a_forged_reply_and_a_currency_it_cannot_take(): void
    {
        $inv = $this->invoice('USD', 100);
        $this->gateways(['paynow' => ['enabled' => true, 'integration_id' => '1234', 'integration_key' => self::KEY]]);
        $forged = 'status=Ok&browserurl=https%3A%2F%2Fevil.test&pollurl=https%3A%2F%2Fevil.test%2Fpoll&hash=' . str_repeat('A', 128);
        Http::fake(['www.paynow.co.zw/interface/initiatetransaction' => Http::response($forged, 200)]);
        auth()->logout();
        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'paynow'])->assertRedirect(route('tourpay.pay', $inv->pay_token))->assertSessionHas('error');
        $this->assertSame(0, Attempt::withoutVendorScope()->count());

        $zar = $this->invoice('ZAR', 500);
        $this->get('/tourpay/pay/' . $zar->pay_token)->assertOk()->assertDontSee('EcoCash', false);   // not offered for a currency it cannot take
        $this->post("/tourpay/pay/{$zar->pay_token}/online", ['gateway' => 'paynow'])->assertSessionHas('error');
    }

    public function test_a_forged_paynow_poll_reply_never_records_money_and_notify_only_looks_at_our_own_attempts(): void
    {
        $inv = $this->invoice('USD', 100);
        $this->gateways(['paynow' => ['enabled' => true, 'integration_id' => '1234', 'integration_key' => self::KEY]]);
        Attempt::create(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'gateway' => 'paynow', 'reference' => 'https://www.paynow.co.zw/poll/1', 'amount' => 100, 'currency' => 'USD', 'status' => 'started']);
        Http::fake(['www.paynow.co.zw/poll/1' => Http::response('status=Paid&amount=100.00&paynowreference=1&hash=' . str_repeat('B', 128), 200)]);
        auth()->logout();
        $this->post('/tourpay/notify/paynow', ['pollurl' => 'https://www.paynow.co.zw/poll/1', 'status' => 'Paid'])->assertOk();
        $this->assertEquals(0, $inv->fresh()->amount_paid, 'the message says Paid but its signature is wrong');
        $this->post('/tourpay/notify/paynow', ['pollurl' => 'https://evil.test/never-created'])->assertOk();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'evil.test'));
        $this->assertContains($this->post('/tourpay/notify/bitcoin')->status(), [404, 405]);
    }

    public function test_pesapal_registers_its_notification_address_once_then_starts_and_confirms(): void
    {
        $inv = $this->invoice('KES', 5000);
        $this->gateways(['pesapal' => ['enabled' => true, 'consumer_key' => 'ck', 'consumer_secret' => 'cs', 'mode' => 'sandbox']]);
        Http::fake([
            'cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken' => Http::response(['token' => 'T1', 'status' => '200'], 200),
            'cybqa.pesapal.com/pesapalv3/api/URLSetup/RegisterIPN' => Http::response(['ipn_id' => 'ipn-guid-1', 'status' => '200'], 200),
            'cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest' => Http::response(['order_tracking_id' => 'TRK1', 'redirect_url' => 'https://pesapal.test/pay/TRK1', 'status' => '200'], 200),
            'cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus*' => Http::response(['status_code' => 1, 'amount' => 5000, 'currency' => 'KES'], 200),
        ]);
        auth()->logout();

        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'pesapal'])->assertRedirect('https://pesapal.test/pay/TRK1');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'SubmitOrderRequest') && $r['notification_id'] === 'ipn-guid-1' && $r['currency'] === 'KES' && $r['amount'] == 5000 && $r->hasHeader('Authorization', 'Bearer T1'));
        $this->assertSame('ipn-guid-1', Setting::forVendor($this->vendor->id)->gateway('pesapal')['ipn_id'], 'the registration is kept');

        $this->get("/tourpay/pay/{$inv->pay_token}/return/pesapal?OrderTrackingId=TRK1&OrderMerchantReference=x")->assertRedirect();
        $this->assertSame('paid', $inv->fresh()->status);

        $inv2 = $this->invoice('KES', 700);
        $this->post("/tourpay/pay/{$inv2->pay_token}/online", ['gateway' => 'pesapal']);
        $registrations = Http::recorded(fn ($req) => str_contains($req->url(), 'RegisterIPN'))->count();
        $this->assertSame(1, $registrations, 'the address is registered once, not for every payment');
    }

    public function test_pesapal_failed_and_reversed_payments_never_count(): void
    {
        $inv = $this->invoice('KES', 5000);
        $this->gateways(['pesapal' => ['enabled' => true, 'consumer_key' => 'ck', 'consumer_secret' => 'cs', 'ipn_id' => 'x']]);
        Attempt::create(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv->id, 'gateway' => 'pesapal', 'reference' => 'TRK9', 'amount' => 5000, 'currency' => 'KES', 'status' => 'started']);
        Http::fake(['pay.pesapal.com/v3/api/Auth/RequestToken' => Http::response(['token' => 'T'], 200), 'pay.pesapal.com/v3/api/Transactions/GetTransactionStatus*' => Http::response(['status_code' => 3, 'amount' => 5000, 'currency' => 'KES'], 200)]);
        auth()->logout();
        $this->get("/tourpay/pay/{$inv->pay_token}/return/pesapal?OrderTrackingId=TRK9");
        $this->assertEquals(0, $inv->fresh()->amount_paid);
        $this->assertSame('failed', Attempt::withoutVendorScope()->where('reference', 'TRK9')->value('status'));
    }

    public function test_selcom_signs_its_requests_with_the_vendors_secret_and_confirms_by_asking(): void
    {
        $inv = $this->invoice('TZS', 250000);
        $this->gateways(['selcom' => ['enabled' => true, 'vendor' => 'TILL60000000', 'api_key' => 'KEY1', 'api_secret' => 'SECRET1']]);
        Http::fake([
            'apigw.selcommobile.com/v1/checkout/create-order-minimal' => Http::response(['resultcode' => '000', 'result' => 'SUCCESS', 'data' => [['payment_gateway_url' => base64_encode('https://selcom.test/pay/abc')]]], 200),
            'apigw.selcommobile.com/v1/checkout/order-status*' => Http::response(['resultcode' => '000', 'data' => [['payment_status' => 'COMPLETED', 'amount' => '250000', 'currency' => 'TZS']]], 200),
        ]);
        auth()->logout();

        $this->post("/tourpay/pay/{$inv->pay_token}/online", ['gateway' => 'selcom'])->assertRedirect('https://selcom.test/pay/abc');
        Http::assertSent(function ($r) {
            if (!str_contains($r->url(), 'create-order-minimal')) { return false; }
            $b = $r->data();
            $signed = 'timestamp=' . $r->header('Timestamp')[0];
            foreach ($b as $k => $v) { $signed .= '&' . $k . '=' . $v; }
            $ok = $r->header('Digest')[0] === base64_encode(hash_hmac('sha256', $signed, 'SECRET1', true));

            return $ok && $r->header('Authorization')[0] === 'SELCOM ' . base64_encode('KEY1') && $r->header('Digest-Method')[0] === 'HS256' && $r->header('Signed-Fields')[0] === implode(',', array_keys($b))
                && $b['vendor'] === 'TILL60000000' && $b['amount'] === 250000 && $b['currency'] === 'TZS' && $b['buyer_phone'] === '255712345678' && base64_decode($b['webhook']) === route('tourpay.notify', 'selcom');
        });
        $orderId = Attempt::withoutVendorScope()->latest('id')->value('reference');
        $this->assertStringStartsWith('TP', $orderId);

        $this->assertEquals(0, $inv->fresh()->amount_paid);
        $this->post('/tourpay/notify/selcom', ['order_id' => $orderId])->assertOk();
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertEquals(250000, $inv->fresh()->amount_paid);
    }

    public function test_selcom_only_takes_tanzanian_shillings_and_a_wrong_currency_is_never_recorded(): void
    {
        $usd = $this->invoice('KES', 100);
        $this->gateways(['selcom' => ['enabled' => true, 'vendor' => 'T', 'api_key' => 'K', 'api_secret' => 'S']]);
        Http::fake();
        auth()->logout();
        $this->post("/tourpay/pay/{$usd->pay_token}/online", ['gateway' => 'selcom'])->assertSessionHas('error');
        Http::assertNothingSent();

        $tzs = $this->invoice('TZS', 5000);
        Attempt::create(['vendor_id' => $this->vendor->id, 'invoice_id' => $tzs->id, 'gateway' => 'selcom', 'reference' => 'TPX1', 'amount' => 5000, 'currency' => 'TZS', 'status' => 'started']);
        Http::fake(['apigw.selcommobile.com/v1/checkout/order-status*' => Http::response(['resultcode' => '000', 'data' => [['payment_status' => 'COMPLETED', 'amount' => '5000', 'currency' => 'USD']]], 200)]);
        $this->post('/tourpay/notify/selcom', ['order_id' => 'TPX1'])->assertOk();
        $this->assertEquals(0, $tzs->fresh()->amount_paid);
    }

    public function test_connection_tests_report_plainly(): void
    {
        $this->actingAs($this->vendor);
        $this->gateways(['selcom' => ['enabled' => true, 'vendor' => 'T', 'api_key' => 'K', 'api_secret' => 'S'], 'pesapal' => ['enabled' => true, 'consumer_key' => 'a', 'consumer_secret' => 'b', 'mode' => 'sandbox'], 'paynow' => ['enabled' => true, 'integration_id' => '1', 'integration_key' => self::KEY]]);
        Http::fake([
            'apigw.selcommobile.com/*' => Http::response(['resultcode' => '403', 'message' => 'Invalid'], 403),
            'cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken' => Http::response(['error' => ['code' => 'invalid_consumer_key_or_secret_provided'], 'status' => '500'], 500),
            'www.paynow.co.zw/interface/initiatetransaction' => Http::response('status=Error&error=Invalid+Integration+Id', 200),
        ]);
        $this->postJson('/user/tourpay/settings/gateways/selcom/test')->assertStatus(422)->assertJsonPath('ok', false);
        $this->postJson('/user/tourpay/settings/gateways/pesapal/test')->assertStatus(422)->assertJsonPath('ok', false);
        $this->postJson('/user/tourpay/settings/gateways/paynow/test')->assertStatus(422)->assertSee('Invalid Integration Id', false);
    }

    public function test_the_settings_page_lists_every_gateway_and_never_shows_a_key(): void
    {
        $this->gateways(['selcom' => ['enabled' => true, 'vendor' => 'TILL9', 'api_key' => 'KEYVALUE', 'api_secret' => 'SECRETVALUE']]);
        $this->actingAs($this->vendor);
        $html = $this->get('/user/tourpay/settings')->assertOk()->assertSee('TILL9', false)->assertSee('Stripe', false)->assertSee('Selcom', false)->assertSee('Pesapal', false)->assertSee('Paynow', false)->getContent();
        $this->assertStringNotContainsString('KEYVALUE', $html);
        $this->assertStringNotContainsString('SECRETVALUE', $html);
        $this->get('/user/tourpay')->assertOk()->assertDontSee('tp-gateways', false);
    }

    public function test_the_audit_trail_records_money_and_settings_without_keys(): void
    {
        $this->actingAs($this->vendor);
        $inv = $this->invoice('USD', 100);
        $this->post("/user/tourpay/{$inv->id}/payments", ['amount' => 40, 'method' => 'bank'])->assertRedirect();
        $this->post('/user/tourpay/settings', ['section' => 'payments', 'gateways' => ['selcom' => ['enabled' => 1, 'vendor' => 'T', 'api_key' => 'KEYVALUE', 'api_secret' => 'SECRETVALUE']]])->assertRedirect();
        $this->post("/user/tourpay/{$inv->id}/void")->assertRedirect();
        $rows = \DB::table('bc_audit_log')->where('vendor_id', $this->vendor->id)->orderBy('id')->get();
        $this->assertSame(['payment.recorded', 'payment_settings_changed', 'invoice.voided'], $rows->pluck('action')->all());
        $this->assertStringNotContainsString('KEYVALUE', json_encode($rows));
        $this->assertSame('user', $rows[0]->actor_type);
        $this->get('/user/tourpay/settings')->assertOk()->assertSee('payment.recorded', false);
        $this->assertSame(0, \DB::table('bc_audit_log')->where('vendor_id', $this->other->id)->count());
    }
}
