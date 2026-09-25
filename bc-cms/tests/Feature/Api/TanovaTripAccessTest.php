<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

/** A business works with every trip that belongs to it (its own and the marketplace's), never another's; staff can look across all. */
class TanovaTripAccessTest extends ApiTestCase
{
    private function trip(array $o): int
    {
        return DB::table('bc_tanova_trips')->insertGetId($o + ['title' => 'Trip', 'destination' => 'Victoria Falls', 'guests' => 2, 'trip_type' => 'leisure', 'status' => 'created', 'source' => 'test', 'itinerary' => '[]', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_a_business_sees_and_opens_its_own_and_marketplace_trips_but_not_anothers(): void
    {
        $own    = $this->trip(['user_id' => $this->vendor->id, 'vendor_id' => $this->vendor->id, 'title' => 'OWN-TRIP']);
        $market = $this->trip(['user_id' => null, 'vendor_id' => $this->vendor->id, 'title' => 'MARKET-TRIP', 'source' => 'mcp']);
        $theirs = $this->trip(['user_id' => $this->other->id, 'vendor_id' => $this->other->id, 'title' => 'THEIR-TRIP']);
        $theirM = $this->trip(['user_id' => null, 'vendor_id' => $this->other->id, 'title' => 'THEIR-MARKET']);
        $nobody = $this->trip(['user_id' => null, 'vendor_id' => null, 'title' => 'NOBODY-TRIP']);
        $this->actingAs($this->vendor);

        $list = $this->get('/user/tanova')->assertOk()->getContent();
        $this->assertStringContainsString('OWN-TRIP', $list);
        $this->assertStringContainsString('MARKET-TRIP', $list, 'a trip the marketplace made for this business is on its dashboard');
        foreach (['THEIR-TRIP', 'THEIR-MARKET', 'NOBODY-TRIP'] as $no) { $this->assertStringNotContainsString($no, $list); }

        $this->get("/user/tanova/{$own}")->assertOk();
        $this->get("/user/tanova/{$market}")->assertOk();
        foreach ([$theirs, $theirM, $nobody] as $id) { $this->get("/user/tanova/{$id}")->assertNotFound(); }
    }

    public function test_actions_on_another_businesss_trip_are_refused_whatever_the_route(): void
    {
        $theirs = $this->trip(['user_id' => $this->other->id, 'vendor_id' => $this->other->id]);
        $this->actingAs($this->vendor);
        $this->post("/user/tanova/{$theirs}/invoice")->assertNotFound();
        $this->post("/user/tanova/{$theirs}/move")->assertNotFound();
        $this->get("/user/tanova/{$theirs}/activities")->assertNotFound();
        $this->assertSame(0, DB::table('bc_tourpay_invoices')->where('tanova_trip_id', $theirs)->count());
    }

    public function test_an_invoice_from_a_trip_belongs_to_the_trips_business_even_when_staff_makes_it(): void
    {
        $trip = $this->trip(['user_id' => null, 'vendor_id' => $this->vendor->id, 'title' => 'FOR-VENDOR', 'estimated_price' => 500, 'guest_name' => 'Ann Guest', 'currency' => 'USD']);
        $staff = $this->makeVendor('Trip Staff');
        DB::table('users')->where('id', $staff->id)->update(['role_id' => 1]);
        $this->actingAs(\App\User::find($staff->id))->post("/user/tanova/{$trip}/invoice")->assertRedirect();

        $inv = DB::table('bc_tourpay_invoices')->where('tanova_trip_id', $trip)->first();
        $this->assertNotNull($inv);
        $this->assertSame($this->vendor->id, (int) $inv->vendor_id, 'the invoice is the trip business\'s, not the staff member\'s');
        $this->assertSame($this->vendor->id, (int) $inv->author_id);
    }

    public function test_staff_can_look_across_businesses_and_a_business_cannot_ask_for_that(): void
    {
        $theirs = $this->trip(['user_id' => $this->other->id, 'vendor_id' => $this->other->id, 'title' => 'ACROSS-1']);
        $staff = $this->makeVendor('Trip Overseer');
        DB::table('users')->where('id', $staff->id)->update(['role_id' => 1]);

        $all = $this->actingAs(\App\User::find($staff->id))->get('/user/tanova?all=1')->assertOk()->getContent();
        $this->assertStringContainsString('ACROSS-1', $all);
        $this->actingAs(\App\User::find($staff->id))->get("/user/tanova/{$theirs}")->assertOk();

        $this->actingAs($this->vendor);
        $this->assertStringNotContainsString('ACROSS-1', $this->get('/user/tanova?all=1')->getContent(), '?all=1 does nothing for a business');
    }
}
