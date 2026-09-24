<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\InvoiceBook;
use Tests\ApiTestCase;

/** The TourPay features over the API: credits and refunds, schedules, quotations, bank transfers, settings, reports, supplier bills and profit. */
class TourPayApiTest extends ApiTestCase
{
    private function make(array $more = [], ?string $key = null): array
    {
        return $this->api('POST', '/invoices', $more + ['bill_to_name' => 'Ann Ray', 'bill_to_email' => 'ann@example.com', 'due_date' => now()->addDays(30)->toDateString(),
            'lines' => [['description' => 'Gorge swing', 'quantity' => 2, 'unit_price' => 500]]], $key)->assertCreated()->json('data');
    }

    private function issued(array $more = []): array
    {
        $i = $this->make($more);
        $this->api('POST', "/invoices/{$i['id']}/issue")->assertOk();

        return $i;
    }

    public function test_named_taxes_and_the_documented_shape(): void
    {
        $i = $this->make(['tax_lines' => [['name' => 'VAT', 'rate' => 15], ['name' => 'Levy', 'rate' => 1]]]);
        $this->assertEquals(1160, $i['total']);
        $this->assertEquals(16, $i['tax_rate']);
        $this->assertSame(['VAT', 'Levy'], array_column($i['tax_lines'], 'name'));
        $this->assertStringContainsString('/tourpay/pay/', $i['pay_url']);
        $this->assertMatchesDocs($this->apiGet("/invoices/{$i['id']}"), 'GET /invoices/{id}');
        $this->assertMatchesDocs($this->apiGet('/invoices'), 'GET /invoices');
        $this->api('POST', '/invoices', ['bill_to_name' => 'X', 'tax_lines' => [['name' => 'A', 'rate' => 500]]])->assertStatus(422);
    }

    public function test_credit_note_refund_and_the_wall(): void
    {
        $i = $this->issued();
        $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 1000, 'method' => 'bank'])->assertCreated();
        $cn = $this->api('POST', "/invoices/{$i['id']}/credit-note", ['amount' => 300, 'reason' => 'Dropped an activity'])->assertCreated();
        $this->assertMatchesDocs($cn, 'POST /invoices/{id}/credit-note', 201);
        $this->assertSame('credit_note', $cn->json('data.type'));
        $this->assertSame($i['id'], $cn->json('data.parent_id'));
        $inv = $this->apiGet("/invoices/{$i['id']}")->json('data');
        $this->assertEquals(300, $inv['refund_due']);
        $this->assertEquals(300, $inv['credit_total']);

        $this->api('POST', "/invoices/{$i['id']}/refunds", ['amount' => 500, 'method' => 'bank'])->assertStatus(422)->assertJsonPath('error.code', 'exceeds_refund_due');
        $r = $this->api('POST', "/invoices/{$i['id']}/refunds", ['amount' => 300, 'method' => 'bank', 'reference' => 'RF1'])->assertCreated();
        $this->assertMatchesDocs($r, 'POST /invoices/{id}/refunds', 201);
        $this->assertEquals(700, $r->json('data.amount_paid'));
        $this->assertSame(-300.0, (float) collect($r->json('data.payments'))->firstWhere('source', 'refund')['amount']);

        $this->assertSame([$cn->json('data.id')], array_column($this->apiGet('/invoices', ['type' => 'credit_note'])->json('data'), 'id'));
        $this->api('POST', "/invoices/{$i['id']}/credit-note", ['reason' => 'x'], $this->otherKey)->assertNotFound();
        $draft = $this->make();
        $this->api('POST', "/invoices/{$draft['id']}/credit-note", ['reason' => 'x'])->assertStatus(409)->assertJsonPath('error.code', 'not_creditable');
    }

    public function test_schedule_convert_duplicate_and_send(): void
    {
        $i = $this->issued(['lines' => [['description' => 'Trip', 'quantity' => 1, 'unit_price' => 1000]]]);
        $s = $this->api('PUT', "/invoices/{$i['id']}/schedule", ['mode' => 'deposit', 'percent' => 30, 'balance_days' => 14])->assertOk();
        $this->assertMatchesDocs($s, 'PUT /invoices/{id}/schedule', 200);
        $this->assertEquals([300, 700], array_column($s->json('data.schedule'), 'amount'));
        $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 300, 'method' => 'bank'])->assertCreated();
        $this->assertEquals([0, 700], array_column($this->apiGet("/invoices/{$i['id']}")->json('data.schedule'), 'remaining'));
        $this->api('PUT', "/invoices/{$i['id']}/schedule", ['mode' => 'none'])->assertOk()->assertJsonPath('data.schedule', []);
        $this->api('PUT', "/invoices/{$i['id']}/schedule", ['mode' => 'weekly'])->assertStatus(422);

        $q = $this->make(['type' => 'quotation', 'valid_days' => 10]);
        $this->assertSame('quotation', $q['type']);
        $this->assertStringStartsWith('QUO-', $q['number']);
        $inv = $this->api('POST', "/invoices/{$q['id']}/convert")->assertCreated();
        $this->assertSame($q['id'], $inv->json('data.parent_id'));
        $this->assertSame('invoice', $inv->json('data.type'));
        $this->api('POST', "/invoices/{$q['id']}/convert")->assertStatus(409)->assertJsonPath('error.code', 'already_converted');
        $this->api('POST', "/invoices/{$i['id']}/convert")->assertStatus(409)->assertJsonPath('error.code', 'not_a_quotation');

        $d = $this->api('POST', "/invoices/{$i['id']}/duplicate")->assertCreated();
        $this->assertSame('draft', $d->json('data.status'));
        $this->assertEquals(0, $d->json('data.amount_paid'));
        $this->assertNotSame($i['number'], $d->json('data.number'));

        $sent = $this->api('POST', "/invoices/{$d->json('data.id')}/send")->assertOk();
        $this->assertTrue($sent->json('data.simulated'), 'a test key sends nothing');
        $this->api('POST', "/invoices/{$d->json('data.id')}/send", ['email' => 'not-an-email'])->assertStatus(422);
    }

    public function test_send_with_a_live_key_e_mails_it_and_marks_it_sent(): void
    {
        Mail::fake();
        $live = \Modules\Vendor\Models\VendorApiKey::generate($this->vendor, 'live', 100000, null, 'secret', 'live')->key;
        $this->vendor->forceFill(['vendor_plan_expires_at' => now()->addYear()])->save();
        $i = $this->make();
        $r = $this->api('POST', "/invoices/{$i['id']}/send", [], $live);
        if ($r->status() === 402) { $this->markTestSkipped('the test vendor has no live plan'); }
        $r->assertOk();
        Mail::assertSent(\Modules\TourPay\Emails\InvoiceEmail::class);
        $this->assertSame('sent', $this->apiGet("/invoices/{$i['id']}")->json('data.status'));
    }

    public function test_a_guests_bank_transfer_is_confirmed_over_the_api(): void
    {
        $i = $this->issued();
        \Illuminate\Support\Facades\Auth::setUser($this->vendor);
        $inv = \Modules\TourPay\Models\Invoice::find($i['id']);
        $p = app(InvoiceBook::class)->reportTransfer($inv, 400, 'FNB-1');
        $before = $this->apiGet("/invoices/{$i['id']}")->json('data');
        $this->assertEquals(0, $before['amount_paid']);
        $this->assertSame('pending', collect($before['payments'])->first()['status']);

        $this->api('POST', "/invoices/{$i['id']}/payments/{$p->id}/approve", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/invoices/{$i['id']}/payments/{$p->id}/approve")->assertOk()->assertJsonPath('data.amount_paid', 400);
        $this->api('POST', "/invoices/{$i['id']}/payments/{$p->id}/approve")->assertStatus(409)->assertJsonPath('error.code', 'not_pending');
        $this->assertContains($this->api('POST', "/invoices/{$i['id']}/payments/{$p->id}/maybe")->status(), [404, 405]);
    }

    public function test_settings_are_readable_writable_and_never_expose_gateway_keys(): void
    {
        Setting::forVendor($this->vendor->id)->update(['gateways' => ['stripe' => ['enabled' => true, 'secret_key' => 'sk_live_topsecret']]]);
        $get = $this->apiGet('/invoices/settings')->assertOk();
        $this->assertMatchesDocs($get, 'GET /invoices/settings');
        $this->assertSame(['stripe'], $get->json('data.online_payment_methods'));
        $this->assertStringNotContainsString('topsecret', $get->getContent());

        $put = $this->api('PUT', '/invoices/settings', ['invoice_prefix' => 'lux', 'base_currency' => 'usd', 'rates' => ['zar' => 0.054], 'remind_enabled' => true, 'remind_max' => 2, 'banking_details' => ['bank' => 'FNB']])->assertOk();
        $this->assertSame('LUX', $put->json('data.invoice_prefix'));
        $this->assertEquals(['ZAR' => 0.054], $put->json('data.rates'));
        $this->assertTrue($put->json('data.reminders.enabled'));
        $this->assertSame('LUX-' . date('Y') . '-001', $this->make()['number']);
        $this->api('PUT', '/invoices/settings', ['rates' => ['ZAR' => 0]])->assertStatus(422);
        $this->api('PUT', '/invoices/settings', ['invoice_prefix' => 'bad prefix!'])->assertStatus(422);
        $this->api('PUT', '/invoices/settings', ['gateways' => ['stripe' => ['secret_key' => 'sk_evil']]])->assertOk();
        $this->assertSame('sk_live_topsecret', Setting::forVendor($this->vendor->id)->gateway('stripe')['secret_key'], 'keys cannot be set through the API');
        $this->assertSame('INV', $this->apiGet('/invoices/settings', [], $this->otherKey)->json('data.invoice_prefix'), 'another vendor has its own');
    }

    public function test_reports_match_their_documentation(): void
    {
        $i = $this->issued(['tax_lines' => [['name' => 'VAT', 'rate' => 10]]]);
        $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 400, 'method' => 'bank'])->assertCreated();
        $this->assertMatchesDocs($this->apiGet('/invoices/reports/receivables'), 'GET /invoices/reports/receivables');
        $this->assertMatchesDocs($this->apiGet('/invoices/reports/revenue'), 'GET /invoices/reports/revenue');
        $this->assertMatchesDocs($this->apiGet('/invoices/reports/tax'), 'GET /invoices/reports/tax');
        $this->assertMatchesDocs($this->apiGet('/invoices/reports/statement', ['client' => 'ann@example.com']), 'GET /invoices/reports/statement');
        $this->assertEquals(700, $this->apiGet('/invoices/reports/receivables')->json('data.0.total'), '1100 - 400');
        $this->assertEquals(100, $this->apiGet('/invoices/reports/tax')->json('data.0.tax'));
        $this->apiGet('/invoices/reports/statement')->assertStatus(422);
        $this->assertSame([], $this->apiGet('/invoices/reports/receivables', [], $this->otherKey)->json('data'));
    }

    public function test_supplier_bills_end_to_end_with_profit_and_walls(): void
    {
        $bk = DB::table('bc_bookings')->insertGetId(['code' => 'PRF1', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => 1, 'total_guests' => 2, 'total' => 1000, 'currency' => 'USD', 'status' => 'confirmed', 'email' => 'a@b.co', 'start_date' => now()->addDays(9), 'created_at' => now(), 'updated_at' => now()]);
        $theirBk = DB::table('bc_bookings')->insertGetId(['code' => 'THEIRS', 'vendor_id' => $this->other->id, 'object_model' => 'tour', 'object_id' => 1, 'total_guests' => 1, 'total' => 5, 'status' => 'confirmed', 'email' => 'x@y.co', 'start_date' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $b = $this->api('POST', '/bills', ['supplier_name' => 'Intercape', 'currency' => 'usd', 'total' => 300, 'bill_date' => now()->toDateString(), 'due_date' => now()->addDays(5)->toDateString(), 'booking_id' => $bk, 'reference' => 'IC-1'])->assertCreated();
        $this->assertMatchesDocs($b, 'POST /bills', 201);
        $id = $b->json('data.id');
        $this->assertSame('USD', $b->json('data.currency'));
        $this->api('POST', '/bills', ['supplier_name' => 'X', 'currency' => 'USD', 'total' => 5, 'bill_date' => now()->toDateString(), 'booking_id' => $theirBk])->assertNotFound();
        $this->api('POST', '/bills', ['currency' => 'USD', 'total' => 5, 'bill_date' => now()->toDateString()])->assertStatus(422);

        $profit = $this->apiGet('/bookings/PRF1/profit')->assertOk();
        $this->assertMatchesDocs($profit, 'GET /bookings/{code}/profit');
        $this->assertEquals(700, $profit->json('data.profit'));
        $this->apiGet('/bookings/THEIRS/profit')->assertNotFound();

        $this->api('POST', "/bills/{$id}/payments", ['amount' => 100, 'method' => 'bank'])->assertCreated()->assertJsonPath('data.status', 'part_paid');
        $this->api('POST', "/bills/{$id}/payments", ['amount' => 900, 'method' => 'bank'])->assertStatus(422)->assertJsonPath('error.code', 'exceeds_balance');
        $this->assertMatchesDocs($this->apiGet('/bills'), 'GET /bills');
        $this->assertEquals(['USD' => 200], $this->apiGet('/bills')->json('meta.owed'));
        $this->assertSame([$id], array_column($this->apiGet('/bills', ['booking_id' => $bk, 'status' => 'part_paid'])->json('data'), 'id'));
        $this->api('DELETE', "/bills/{$id}")->assertStatus(409)->assertJsonPath('error.code', 'has_payments');

        $this->api('POST', "/bills/{$id}/void")->assertOk()->assertJsonPath('data.status', 'void');
        $this->assertEquals(0, $this->apiGet('/bookings/PRF1/profit')->json('data.cost'), 'a voided bill does not count');
        $this->api('PUT', "/bills/{$id}", ['notes' => 'x'])->assertStatus(409)->assertJsonPath('error.code', 'bill_void');

        $this->apiGet("/bills/{$id}", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/bills/{$id}/void", [], $this->otherKey)->assertNotFound();
        $this->assertSame([], $this->apiGet('/bills', [], $this->otherKey)->json('data'));
    }

    public function test_scopes_still_guard_the_new_endpoints(): void
    {
        $ro = \Modules\Vendor\Models\VendorApiKey::generate($this->vendor, 'reader', 100000, null, 'secret', 'test', ['invoices:read'])->key;
        $this->apiGet('/bills', [], $ro)->assertOk();
        $this->apiGet('/invoices/reports/tax', [], $ro)->assertOk();
        $this->api('POST', '/bills', ['supplier_name' => 'X', 'currency' => 'USD', 'total' => 5, 'bill_date' => now()->toDateString()], $ro)->assertForbidden();
        $this->api('PUT', '/invoices/settings', ['invoice_prefix' => 'X'], $ro)->assertForbidden();
        $bk = \Modules\Vendor\Models\VendorApiKey::generate($this->vendor, 'bookings only', 100000, null, 'secret', 'test', ['bookings:read'])->key;
        $this->apiGet('/bills', [], $bk)->assertForbidden();
        $this->apiGet('/bookings/NOPE/profit', [], $bk)->assertNotFound();
    }
}
