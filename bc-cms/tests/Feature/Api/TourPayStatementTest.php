<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

/** The Finance statement: invoice payments in, platform payouts, expenses, one running balance per currency, and only this business's own. */
class TourPayStatementTest extends ApiTestCase
{
    private function invoice(int $vendorId, string $cur, string $client): int
    {
        return DB::table('bc_tourpay_invoices')->insertGetId(['vendor_id' => $vendorId, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-' . uniqid(), 'currency' => $cur, 'client_name' => $client,
            'subtotal' => 500, 'total' => 500, 'issue_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_statement_lists_money_in_payouts_and_expenses_with_a_running_balance(): void
    {
        $inv = $this->invoice($this->vendor->id, 'USD', 'Alice Guest');
        $pay = fn ($inv, $amt, $day, $status = 'confirmed') => DB::table('bc_tourpay_payments')->insert(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv, 'amount' => $amt, 'method' => 'bank', 'paid_at' => $day, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
        $pay($inv, 300, now()->subDays(5)->toDateString());
        $pay($inv, 50, now()->subDays(4)->toDateString(), 'pending');   // not confirmed: not in the account
        $pay($inv, -40, now()->subDays(3)->toDateString());              // refund
        $bill = DB::table('bc_tourpay_bills')->insertGetId(['vendor_id' => $this->vendor->id, 'supplier_name' => 'Safari Lodge', 'currency' => 'USD', 'total' => 100, 'amount_paid' => 100, 'status' => 'paid', 'bill_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tourpay_bill_payments')->insert(['vendor_id' => $this->vendor->id, 'bill_id' => $bill, 'amount' => 100, 'method' => 'bank', 'paid_at' => now()->subDays(2)->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_payouts')->insert(['vendor_id' => $this->vendor->id, 'amount' => 75, 'status' => 'paid', 'payout_method' => 'bank', 'pay_date' => now()->subDay()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_payouts')->insert(['vendor_id' => $this->vendor->id, 'amount' => 999, 'status' => 'initial', 'payout_method' => 'bank', 'created_at' => now(), 'updated_at' => now()]);   // requested, not paid

        // Another business's money never appears.
        $theirs = $this->invoice($this->other->id, 'USD', 'Bob Other');
        DB::table('bc_tourpay_payments')->insert(['vendor_id' => $this->other->id, 'invoice_id' => $theirs, 'amount' => 777, 'method' => 'bank', 'paid_at' => now()->toDateString(), 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);

        // These rows were inserted straight into the tables, so load them into the ledger the way existing data is loaded.
        app(\Modules\TourPay\Services\MoneyBackfill::class)->run();
        $this->actingAs($this->vendor);
        $svc = app(\Modules\TourPay\Services\AccountStatement::class);
        $r = $svc->build(now()->subMonth()->startOfDay(), now()->endOfDay());
        $this->assertSame(['invoice', 'invoice', 'expense', 'payout'], array_column($r['entries'], 'kind'));
        $this->assertEquals([300, 260, 160, 235], array_map('floatval', array_column($r['entries'], 'balance')));
        $this->assertEquals(['invoice' => 260, 'booking' => 0, 'payout' => 75, 'expense' => 100, 'net' => 235], $r['totals']['USD']);

        $page = $this->get(route('tourpay.vendor.statement'));
        $page->assertOk()->assertSee('Safari Lodge')->assertSee('Alice Guest')->assertSee('Paid out by platform')->assertDontSee('Bob Other')->assertDontSee('777.00')->assertDontSee('999.00');
        $csv = $this->get(route('tourpay.vendor.statement.csv'))->streamedContent();
        $this->assertStringContainsString('Safari Lodge', $csv);
        $this->assertStringNotContainsString('Bob Other', $csv);

        // The period opens with what came before it.
        $later = $svc->build(now()->subDays(2)->startOfDay(), now()->endOfDay());
        $this->assertSame(260.0, $later['opening']['USD']);
    }

    public function test_the_vendor_sidebar_has_finance_with_tourpay_and_statement(): void
    {
        $this->actingAs($this->vendor);
        $html = $this->get('/user/tourpay')->assertOk()->getContent();
        $this->assertStringContainsString('href="' . route('tourpay.vendor.statement') . '"', $html);
        $this->assertStringContainsString('href="' . route('tourpay.vendor.index') . '"', $html);
        $this->assertMatchesRegularExpression('/Finance/i', $html);
    }
}
