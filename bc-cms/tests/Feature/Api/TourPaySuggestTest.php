<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorCustomer;
use Modules\Vendor\Models\VendorUpsell;
use Tests\ApiTestCase;

/** Type-ahead on the invoice form: this business's customers and services only, matched on what was typed. */
class TourPaySuggestTest extends ApiTestCase
{
    private function names(string $what, string $q): array
    {
        return array_column($this->getJson(route('tourpay.vendor.suggest', $what) . '?q=' . urlencode($q))->assertOk()->json('results'), 'name');
    }

    public function test_customers_are_suggested_from_the_customer_list_and_past_invoices_and_stay_inside_one_business(): void
    {
        VendorCustomer::create(['vendor_id' => $this->vendor->id, 'first_name' => 'Alice', 'last_name' => 'Wonder', 'email' => 'alice@example.com', 'phone' => '+263 77 111', 'nationality' => 'ZW']);
        VendorCustomer::create(['vendor_id' => $this->other->id, 'first_name' => 'Alicia', 'last_name' => 'Elsewhere', 'email' => 'alicia@other.example']);
        DB::table('bc_tourpay_invoices')->insert(['vendor_id' => $this->vendor->id, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-S1', 'currency' => 'USD', 'client_name' => 'Alan Walker', 'client_email' => 'alan@example.com', 'client_country' => 'GB', 'total' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tourpay_invoices')->insert(['vendor_id' => $this->other->id, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-S2', 'currency' => 'USD', 'client_name' => 'Alan Theirs', 'total' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->vendor);

        $this->assertSame(['Alice Wonder', 'Alan Walker'], $this->names('customers', 'al'));
        $this->assertSame(['Alice Wonder'], $this->names('customers', 'alice@'));
        $this->assertSame(['Alice Wonder'], $this->names('customers', '111'));
        $this->assertSame([], $this->names('customers', '%'), 'a wildcard character is text, not a pattern');
        $hit = $this->getJson(route('tourpay.vendor.suggest', 'customers') . '?q=alice')->json('results.0');
        $this->assertSame('alice@example.com', $hit['email']);
        $this->assertNotEmpty($hit['customer_id']);
        $this->assertContains('Alice Wonder', $this->names('customers', ''), 'focusing the empty box offers recent people');
    }

    public function test_services_come_from_the_catalogue_add_ons_and_lines_billed_before(): void
    {
        DB::table('bc_tours')->insert(['title' => 'Victoria Falls Sunset Cruise', 'slug' => 'vf-cruise-' . uniqid(), 'price' => 80, 'sale_price' => 65, 'status' => 'publish', 'author_id' => $this->vendor->id, 'create_user' => $this->vendor->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tours')->insert(['title' => 'Victoria Falls Draft', 'slug' => 'vf-draft-' . uniqid(), 'price' => 10, 'status' => 'draft', 'author_id' => $this->vendor->id, 'create_user' => $this->vendor->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tours')->insert(['title' => 'Victoria Falls Rival Tour', 'slug' => 'vf-rival-' . uniqid(), 'price' => 10, 'status' => 'publish', 'author_id' => $this->other->id, 'create_user' => $this->other->id, 'created_at' => now(), 'updated_at' => now()]);
        VendorUpsell::create(['vendor_id' => $this->vendor->id, 'name' => 'Victoria Falls Photo Pack', 'price' => 25, 'status' => 'publish']);
        $inv = DB::table('bc_tourpay_invoices')->insertGetId(['vendor_id' => $this->vendor->id, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-S3', 'currency' => 'USD', 'client_name' => 'X', 'total' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tourpay_invoice_items')->insert(['vendor_id' => $this->vendor->id, 'invoice_id' => $inv, 'name' => 'Victoria Falls airport transfer', 'description' => 'Return, 4 pax', 'quantity' => 1, 'unit_price' => 45, 'total' => 45, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->vendor);

        $rows = $this->getJson(route('tourpay.vendor.suggest', 'services') . '?q=victoria')->assertOk()->json('results');
        $byName = collect($rows)->keyBy('name');
        $this->assertEqualsCanonicalizing(['Victoria Falls Sunset Cruise', 'Victoria Falls Photo Pack', 'Victoria Falls airport transfer'], $byName->keys()->all());
        $this->assertEquals(65, $byName['Victoria Falls Sunset Cruise']['price'], 'the sale price wins');
        $this->assertEquals(45, $byName['Victoria Falls airport transfer']['price']);
        $this->assertSame('Return, 4 pax', $byName['Victoria Falls airport transfer']['description']);
        $this->assertSame('Billed before', $byName['Victoria Falls airport transfer']['group']);
        $this->assertSame([], $this->names('services', 'zzzz-nothing'));
    }

    public function test_the_form_uses_type_ahead_and_the_endpoint_needs_a_signed_in_vendor(): void
    {
        $this->get(route('tourpay.vendor.suggest', 'customers'))->assertRedirect();
        $this->actingAs($this->vendor);
        $this->get(route('tourpay.vendor.suggest', 'nonsense'))->assertNotFound();
        $this->get('/user/tourpay/create')->assertOk()->assertSee('data-suggest="customers"', false)->assertSee('data-suggest="services"', false);
    }
}
