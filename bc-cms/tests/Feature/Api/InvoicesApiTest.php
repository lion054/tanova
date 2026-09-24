<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;

class InvoicesApiTest extends ApiTestCase
{
    private function make(array $more = []): array
    {
        return $this->api('POST', '/invoices', array_merge(['bill_to_name' => 'Ann Ray', 'bill_to_email' => 'ann@x.com', 'lines' => [['description' => 'Swing', 'quantity' => 2, 'unit_price' => 200], ['description' => 'Transfer', 'quantity' => 1, 'unit_price' => 30]]], $more))->assertCreated()->json('data');
    }

    public function test_create_with_lines_numbers_sequentially_and_works_out_totals(): void
    {
        $a = $this->make(['tax_rate' => 10, 'discount' => 30]);
        $this->assertSame('INV-' . date('Y') . '-001', $a['number']);
        $this->assertSame('draft', $a['status']);
        $this->assertEquals(430, $a['subtotal']);
        $this->assertEqualsWithDelta(440, $a['total'], 0.001);   // (430 - 30) + 10 % tax
        $this->assertCount(2, $a['lines']);
        $this->assertSame('INV-' . date('Y') . '-002', $this->make()['number']);

        $this->api('POST', '/invoices', [])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->api('POST', '/invoices', ['bill_to_name' => 'X', 'due_date' => '2020-01-01', 'issue_date' => '2026-01-01'])->assertStatus(422);
        $this->api('POST', '/invoices', ['bill_to_name' => 'X', 'lines' => [['description' => 'x', 'quantity' => 0, 'unit_price' => 1]]])->assertStatus(422);
        $this->api('POST', '/invoices', ['bill_to_name' => 'X', 'customer_id' => 999999])->assertNotFound();
    }

    public function test_payments_drive_the_status_and_overpaying_is_refused(): void
    {
        $i = $this->make();
        $this->api('POST', "/invoices/{$i['id']}/issue")->assertOk()->assertJsonPath('data.status', 'sent');
        $p = $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 100, 'method' => 'bank', 'reference' => 'T1'])->assertCreated()->json('data');
        $this->assertSame('part_paid', $p['status']);
        $this->assertEquals(330, $p['balance']);

        $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 1000, 'method' => 'bank'])->assertStatus(422)->assertJsonPath('error.code', 'exceeds_balance');
        $paid = $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 330, 'method' => 'cash'])->assertCreated()->json('data');
        $this->assertSame('paid', $paid['status']);

        // Removing a payment brings the status back.
        $this->api('DELETE', "/invoices/{$i['id']}/payments/{$paid['payments'][1]['id']}")->assertNoContent();
        $this->assertSame('part_paid', $this->apiGet("/invoices/{$i['id']}")->json('data.status'));
    }

    public function test_drafts_can_be_edited_and_deleted_issued_ones_only_voided_and_paid_ones_are_locked(): void
    {
        $i = $this->make();
        $line = $this->api('POST', "/invoices/{$i['id']}/lines", ['description' => 'Extra', 'quantity' => 1, 'unit_price' => 50])->assertCreated()->json('data.lines.2.id');
        $this->api('DELETE', "/invoices/{$i['id']}/lines/{$line}")->assertNoContent();
        $this->api('PUT', "/invoices/{$i['id']}", ['notes' => 'Thanks', 'due_date' => '2001-01-01'])->assertStatus(422);
        $this->api('PUT', "/invoices/{$i['id']}", ['notes' => 'Thanks'])->assertOk()->assertJsonPath('data.notes', 'Thanks');

        $this->api('POST', "/invoices/{$i['id']}/issue")->assertOk();
        $this->api('POST', "/invoices/{$i['id']}/issue")->assertStatus(409)->assertJsonPath('error.code', 'not_a_draft');
        $this->api('DELETE', "/invoices/{$i['id']}")->assertStatus(409)->assertJsonPath('error.code', 'not_a_draft');

        $this->api('POST', "/invoices/{$i['id']}/payments", ['amount' => 430, 'method' => 'bank'])->assertCreated();
        $this->api('POST', "/invoices/{$i['id']}/lines", ['description' => 'Late', 'quantity' => 1, 'unit_price' => 1])->assertStatus(409)->assertJsonPath('error.code', 'invoice_locked');
        $this->api('PUT', "/invoices/{$i['id']}", ['notes' => 'x'])->assertStatus(409)->assertJsonPath('error.code', 'invoice_locked');

        $empty = $this->api('POST', '/invoices', ['bill_to_name' => 'Empty'])->json('data.id');
        $this->api('POST', "/invoices/{$empty}/issue")->assertStatus(409)->assertJsonPath('error.code', 'no_lines');
        $this->api('DELETE', "/invoices/{$empty}")->assertNoContent();

        $v = $this->make();
        $this->api('POST', "/invoices/{$v['id']}/void")->assertOk()->assertJsonPath('data.status', 'void');
        $this->api('POST', "/invoices/{$v['id']}/payments", ['amount' => 1, 'method' => 'cash'])->assertStatus(409);
    }

    public function test_list_search_filters_overdue_and_summary(): void
    {
        $old = $this->make(['bill_to_name' => 'Ann Ray', 'issue_date' => now()->subDays(60)->toDateString(), 'due_date' => now()->subDays(30)->toDateString()]);
        $this->api('POST', "/invoices/{$old['id']}/issue")->assertOk();
        $this->make(['bill_to_name' => 'Bob Moyo']);

        $ids = fn (array $q) => array_column($this->apiGet('/invoices', $q)->assertOk()->json('data'), 'id');
        $this->assertSame([$old['id']], $ids(['status' => 'overdue']));
        $this->assertSame([$old['id']], $ids(['q' => 'ann ray']));
        $this->assertSame([$old['id']], $ids(['q' => 'INV-' . date('Y') . '-001']));
        $this->assertSame([$old['id']], $ids(['to' => now()->subDays(30)->toDateString()]));
        $this->assertCount(2, $this->apiGet('/invoices', ['sort' => 'amount'])->json('data'));

        $r = $this->apiGet('/invoices', ['include' => 'lines'])->json();
        $this->assertCount(2, $r['data'][0]['lines']);
        $this->assertArrayNotHasKey('payments', $r['data'][0]);
        $this->assertSame(2, $r['meta']['summary']['count']);
        $this->assertEquals(860, $r['meta']['summary']['outstanding']);
        $this->assertTrue($this->apiGet("/invoices/{$old['id']}")->json('data.overdue'));
    }

    public function test_pdf_download_isolation_and_read_only_keys(): void
    {
        $i = $this->make();
        $r = $this->api('GET', "/invoices/{$i['id']}/pdf")->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $this->assertStringContainsString('INV-' . date('Y') . '-001.pdf', $r->headers->get('Content-Disposition'));

        $this->apiGet("/invoices/{$i['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('GET', "/invoices/{$i['id']}/pdf", [], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/invoices/{$i['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/invoices/{$i['id']}/void", [], $this->otherKey)->assertNotFound();
        $this->assertSame(0, $this->apiGet('/invoices', [], $this->otherKey)->json('meta.total'));

        $this->apiGet('/invoices', [], $this->pk)->assertOk();
        $this->api('POST', '/invoices', ['bill_to_name' => 'X'], $this->pk)->assertForbidden();
    }
}
