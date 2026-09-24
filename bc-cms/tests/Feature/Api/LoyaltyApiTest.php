<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class LoyaltyApiTest extends ApiTestCase
{
    public function test_the_rule_can_be_read_and_changed(): void
    {
        $this->apiGet('/loyalty/rule')->assertOk()->assertJsonPath('data.enabled', true)->assertJsonPath('data.spend_per_point', 10);

        $this->api('PUT', '/loyalty/rule', ['spend_per_point' => 5, 'enabled' => false])->assertOk()->assertJsonPath('data.spend_per_point', 5)->assertJsonPath('data.enabled', false);
        $this->apiGet('/loyalty/rule')->assertJsonPath('data.spend_per_point', 5);
        $this->api('PUT', '/loyalty/rule', ['spend_per_point' => 0])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')->assertJsonStructure(['error' => ['fields' => ['spend_per_point']]]);
    }

    public function test_tiers_and_members_move_together(): void
    {
        $bronze = $this->api('POST', '/loyalty/tiers', ['name' => 'Bronze', 'min_points' => 0])->assertCreated()->json('data');
        $gold = $this->api('POST', '/loyalty/tiers', ['name' => 'Gold', 'min_points' => 100, 'earn_multiplier' => 1.5])->assertCreated()->json('data');
        $this->assertEquals(1, $bronze['earn_multiplier']);

        $m = $this->api('POST', '/loyalty/adjustments', ['email' => 'Ann@Example.com', 'name' => 'Ann Ray', 'points' => 120, 'reason' => 'Welcome'])->assertCreated()->json('data');
        $this->assertSame('ann@example.com', $m['email']);
        $this->assertSame('Gold', $m['tier']['name']);
        $this->assertNull($m['next_tier']);

        // Taking points away moves the tier and never goes below zero.
        $low = $this->api('POST', '/loyalty/adjustments', ['email' => 'ann@example.com', 'points' => -500])->assertCreated()->json('data');
        $this->assertSame(0, $low['points']);
        $this->assertSame('Bronze', $low['tier']['name']);
        $this->assertSame('Gold', $low['next_tier']['name']);
        $this->assertSame(100, $low['next_tier']['points_needed']);

        // Deleting a tier re-tiers everyone in it.
        $this->api('POST', '/loyalty/adjustments', ['email' => 'ann@example.com', 'points' => 150])->assertCreated();
        $this->api('DELETE', "/loyalty/tiers/{$gold['id']}")->assertNoContent();
        $this->assertSame('Bronze', $this->apiGet("/loyalty/members/{$m['id']}")->json('data.tier.name'));
    }

    public function test_members_search_filter_sort_and_history(): void
    {
        $this->api('POST', '/loyalty/tiers', ['name' => 'Silver', 'min_points' => 50])->assertCreated();
        foreach ([['ann@x.com', 'Ann Ray', 120], ['bob@x.com', 'Bob Moyo', 10], ['cy@x.com', 'Cy Dube', 60]] as [$e, $n, $p]) {
            $this->api('POST', '/loyalty/adjustments', ['email' => $e, 'name' => $n, 'points' => $p])->assertCreated();
        }

        $all = $this->apiGet('/loyalty/members')->assertOk();
        $this->assertSame(['ann@x.com', 'cy@x.com', 'bob@x.com'], array_column($all->json('data'), 'email'));
        $this->assertSame(3, $all->json('meta.total'));

        $this->assertSame(['cy@x.com', 'ann@x.com'], array_column($this->apiGet('/loyalty/members', ['q' => 'x.com', 'tier' => (string) $this->apiGet('/loyalty/tiers')->json('data.0.id'), 'sort' => 'least'])->json('data'), 'email'));
        $this->assertSame(['bob@x.com'], array_column($this->apiGet('/loyalty/members', ['tier' => 'none'])->json('data'), 'email'));
        $this->assertSame(['bob@x.com'], array_column($this->apiGet('/loyalty/members', ['q' => 'moyo bob'])->json('data'), 'email'));
        $this->assertCount(3, $this->apiGet('/loyalty/members', ['per_page' => 10, 'q' => 'x.com'])->json('data'));

        $id = $all->json('data.0.id');
        $one = $this->apiGet("/loyalty/members/{$id}")->assertOk();
        $this->assertSame(120, $one->json('data.history.0.points'));
    }

    public function test_the_api_and_the_portal_agree_on_a_completed_trip(): void
    {
        $this->api('POST', '/loyalty/tiers', ['name' => 'Base', 'min_points' => 0])->assertCreated();
        $tourId = DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gorge-swing', 'author_id' => $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_bookings')->insert(['code' => 'ZZTOP1234', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $tourId, 'first_name' => 'Di', 'last_name' => 'N', 'email' => 'di@x.com', 'total' => 300, 'paid' => 300, 'status' => 'confirmed', 'start_date' => '2026-11-10 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
        $booking = \Modules\Booking\Models\Booking::where('code', 'ZZTOP1234')->first();

        app(\Modules\Vendor\Services\BookingStatusFlow::class)->move($booking, 'completed');

        $m = $this->apiGet('/loyalty/members', ['q' => 'di@x.com'])->json('data.0');
        $this->assertSame(30, $m['points']);
        $this->assertSame(1, $m['trips']);
        $this->assertEquals(300, $m['spent']);
        $this->assertSame('2026-11-10', $m['last_trip']);
    }

    public function test_it_is_isolated_scoped_and_read_only_for_publishable_keys(): void
    {
        $this->api('POST', '/loyalty/adjustments', ['email' => 'mine@x.com', 'points' => 10])->assertCreated();
        $this->api('POST', '/loyalty/adjustments', ['email' => 'theirs@x.com', 'points' => 99], $this->otherKey)->assertCreated();
        $mine = $this->apiGet('/loyalty/members')->json('data');
        $this->assertSame(['mine@x.com'], array_column($mine, 'email'));
        $theirId = $this->apiGet('/loyalty/members', [], $this->otherKey)->json('data.0.id');
        $this->apiGet("/loyalty/members/{$theirId}")->assertNotFound()->assertJsonPath('error.code', 'not_found');

        $this->apiGet('/loyalty/members', [], $this->pk)->assertOk();
        $this->api('POST', '/loyalty/adjustments', ['email' => 'a@x.com', 'points' => 1], $this->pk)->assertForbidden()->assertJsonPath('error.code', 'read_only_key');
    }

    public function test_a_key_limited_to_other_areas_is_refused(): void
    {
        DB::table('bc_vendor_api_keys')->where('key_hash', hash_hmac('sha256', $this->key, config('app.key')))->update(['scopes' => json_encode(['bookings:read'])]);

        $this->apiGet('/loyalty/members')->assertForbidden()->assertJsonPath('error.code', 'insufficient_scope')->assertJsonPath('error.scope', 'loyalty:read');

        DB::table('bc_vendor_api_keys')->where('key_hash', hash_hmac('sha256', $this->key, config('app.key')))->update(['scopes' => json_encode(['loyalty:read'])]);
        $this->apiGet('/loyalty/members')->assertOk();
        $this->api('POST', '/loyalty/adjustments', ['email' => 'a@x.com', 'points' => 1])->assertForbidden()->assertJsonPath('error.scope', 'loyalty:write');
    }
}
