<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class CustomersApiTest extends ApiTestCase
{
    private function add(array $d): array
    {
        return $this->api('POST', '/crm/customers', $d)->assertCreated()->json('data');
    }

    public function test_create_normalises_and_needs_a_way_to_reach_them(): void
    {
        $c = $this->add(['first_name' => 'Ann', 'last_name' => 'Ray', 'email' => ' ANN@Example.COM ', 'phone' => '077 000 0000', 'tags' => ['VIP', 'vip', ' early ', '']]);

        $this->assertSame('ann@example.com', $c['email']);
        $this->assertSame('Ann Ray', $c['name']);
        $this->assertSame(['VIP', 'vip', 'early'], $c['tags']);
        $this->assertSame('manual', $c['source']);

        $this->api('POST', '/crm/customers', ['first_name' => 'No', 'last_name' => 'Contact'])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['email']]]);
        $this->api('POST', '/crm/customers', ['email' => 'not-an-email'])->assertStatus(422);
        $this->api('POST', '/crm/customers', ['email' => 'a@x.com', 'date_of_birth' => now()->addDay()->toDateString()])->assertStatus(422);
    }

    public function test_update_is_partial_and_cannot_remove_every_way_to_reach_them(): void
    {
        $c = $this->add(['first_name' => 'Ann', 'email' => 'ann@x.com']);

        $this->api('PUT', "/crm/customers/{$c['id']}", ['notes' => 'Prefers mornings'])->assertOk()->assertJsonPath('data.notes', 'Prefers mornings')->assertJsonPath('data.email', 'ann@x.com');
        $this->api('PUT', "/crm/customers/{$c['id']}", ['email' => null])->assertStatus(422);
        $this->api('PUT', "/crm/customers/{$c['id']}", ['email' => null, 'phone' => '+263771111111'])->assertOk()->assertJsonPath('data.email', null);
    }

    public function test_search_filter_and_sort(): void
    {
        $a = $this->add(['first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'ann@x.com', 'tags' => ['vip']]);
        $b = $this->add(['first_name' => 'Bob', 'last_name' => 'Moyo', 'email' => 'bob@x.com']);
        DB::table('bc_vendor_customers')->where('id', $a['id'])->update(['bookings_count' => 3, 'total_spent' => 900]);
        DB::table('bc_vendor_customers')->where('id', $b['id'])->update(['bookings_count' => 1, 'total_spent' => 100]);

        $ids = fn (array $q) => array_column($this->apiGet('/crm/customers', $q)->json('data'), 'id');
        $this->assertSame([$a['id']], $ids(['q' => 'ray ann']));
        $this->assertSame([$a['id']], $ids(['type' => 'repeat']));
        $this->assertSame([$b['id']], $ids(['type' => 'once']));
        $this->assertSame([$a['id']], $ids(['tag' => 'vip']));
        $unknown = $ids(['tag' => 'nonexistent']);
        sort($unknown);
        $this->assertSame([$a['id'], $b['id']], $unknown, 'an unknown tag is ignored, it does not empty the list');
        $this->assertSame([$a['id'], $b['id']], $ids(['sort' => 'spent']));
        $this->assertSame([$a['id'], $b['id']], $ids(['sort' => 'name']));
        $this->assertSame([$b['id'], $a['id']], $ids(['sort' => 'newest']));
    }

    public function test_sync_builds_customers_from_bookings_and_their_bookings_list_matches(): void
    {
        $tour = DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gs', 'author_id' => $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['ANN@x.com', 200], ['ann@x.com', 300]] as $i => [$email, $total]) {
            DB::table('bc_bookings')->insert(['code' => "SYNC{$i}", 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $tour, 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => $email, 'total' => $total, 'paid' => $total, 'status' => 'confirmed', 'start_date' => '2026-11-10 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('bc_bookings')->insert(['code' => 'NOID', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $tour, 'first_name' => 'Ghost', 'total' => 1, 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);

        $r = $this->api('POST', '/crm/customers/sync')->assertOk()->json('data');
        $this->assertSame(['created' => 1, 'updated' => 0, 'skipped' => 1], $r);
        $again = $this->api('POST', '/crm/customers/sync')->json('data');
        $this->assertSame(0, $again['created'], 'running it again creates no duplicates');
        $this->assertSame(1, $again['skipped']);
        $this->assertSame(1, $this->apiGet('/crm/customers')->json('meta.total'));

        $c = $this->apiGet('/crm/customers')->json('data.0');
        $this->assertSame(2, $c['bookings_count']);
        $this->assertEquals(500, $c['total_spent']);
        $detail = $this->apiGet("/crm/customers/{$c['id']}")->assertOk()->json('data');
        $this->assertCount(2, $detail['recent_bookings']);
        $this->assertSame(2, $this->apiGet("/crm/customers/{$c['id']}/bookings")->json('meta.total'));
    }

    public function test_isolation_delete_and_read_only_keys(): void
    {
        $mine = $this->add(['email' => 'mine@x.com']);
        $theirs = $this->api('POST', '/crm/customers', ['email' => 'theirs@x.com'], $this->otherKey)->json('data');

        $this->assertSame(['mine@x.com'], array_column($this->apiGet('/crm/customers')->json('data'), 'email'));
        $this->apiGet("/crm/customers/{$theirs['id']}")->assertNotFound();
        $this->api('PUT', "/crm/customers/{$theirs['id']}", ['notes' => 'x'])->assertNotFound();
        $this->api('DELETE', "/crm/customers/{$theirs['id']}")->assertNotFound();
        $this->api('POST', '/crm/customers', ['email' => 'x@x.com'], $this->pk)->assertForbidden();

        $this->api('DELETE', "/crm/customers/{$mine['id']}")->assertNoContent();
        $this->assertSame(0, $this->apiGet('/crm/customers')->json('meta.total'));
    }
}
