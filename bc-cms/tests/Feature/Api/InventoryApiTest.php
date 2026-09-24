<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class InventoryApiTest extends ApiTestCase
{
    private function row(string $table, string $title, array $more = [], ?int $vendor = null): int
    {
        return DB::table($table)->insertGetId(array_merge(['title' => $title, 'author_id' => $vendor ?? $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()], $more));
    }

    private function tour(string $title = 'Gorge swing', array $more = [], ?int $vendor = null): int
    {
        return $this->row('bc_tours', $title, array_merge(['slug' => str_replace(' ', '-', strtolower($title)) . uniqid(), 'price' => 100, 'max_people' => 10], $more), $vendor);
    }

    // ── Listings ─────────────────────────────────────────────────────────────

    public function test_one_list_across_every_type_with_search_filters_and_paging(): void
    {
        DB::table('bc_locations')->insert([['id' => 7, 'name' => 'Victoria Falls', 'slug' => 'vf', 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()], ['id' => 8, 'name' => 'Nyanga', 'slug' => 'ny', 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]]);
        $t = $this->tour('Gorge swing', ['location_id' => 7, 'price' => 200]);
        $t2 = $this->tour('Quiet hike', ['location_id' => 8, 'price' => 20, 'status' => 'draft']);
        $h = $this->row('bc_hotels', 'Falls Lodge', ['slug' => 'fl', 'price' => 150, 'location_id' => 7]);
        $b = $this->row('bc_boats', 'Zambezi Cruise', ['slug' => 'zc', 'location_id' => 7]);
        $f = $this->row('bc_flight', 'Harare to Falls', ['code' => 'HF1']);
        $this->tour('Theirs', [], $this->other->id);

        $all = $this->apiGet('/services', ['sort' => 'title'])->assertOk();
        $this->assertSame(['Falls Lodge', 'Gorge swing', 'Harare to Falls', 'Quiet hike', 'Zambezi Cruise'], array_column($all->json('data'), 'title'));
        $this->assertSame(5, $all->json('meta.total'));

        $by = fn (array $q) => array_column($this->apiGet('/services', $q)->assertOk()->json('data'), 'title');
        $this->assertSame(['Falls Lodge', 'Gorge swing'], $by(['type' => 'tour,hotel', 'status' => 'publish', 'sort' => 'title']));
        $this->assertSame(['Quiet hike'], $by(['status' => 'draft']));
        $this->assertSame(['Falls Lodge', 'Gorge swing', 'Zambezi Cruise'], $by(['location_id' => 7, 'sort' => 'title']), 'flights have no place, so they are left out');
        $this->assertSame(['Gorge swing'], $by(['q' => 'swing gorge']));
        $this->assertSame(['Gorge swing', 'Falls Lodge'], array_slice($by(['sort' => 'price_desc', 'type' => 'tour,hotel']), 0, 2));
        $this->assertCount(2, $this->apiGet('/services', ['per_page' => 10])->json('data') ? array_slice($this->apiGet('/services', ['type' => 'tour'])->json('data'), 0, 2) : []);

        $one = $this->apiGet("/services/tour/{$t}")->assertOk()->json('data');
        $this->assertSame(['tour', 'Victoria Falls', 200.0], [$one['type'], $one['location']['name'], (float) $one['price']]);
        $this->assertNull($this->apiGet("/services/boat/{$b}")->json('data.price'));
        $this->assertSame('Harare to Falls', $this->apiGet("/services/flight/{$f}")->json('data.title'));
        $this->apiGet('/services/spaceship/1')->assertNotFound();
    }

    public function test_publish_hide_delete_restore_and_the_recovery_bin(): void
    {
        $t = $this->tour();
        $this->api('PATCH', "/services/tour/{$t}/status", ['status' => 'draft'])->assertOk()->assertJsonPath('data.status', 'draft');
        $this->api('PATCH', "/services/tour/{$t}/status", ['status' => 'bogus'])->assertStatus(422);
        $this->api('PATCH', "/services/tour/{$t}/status", ['status' => 'publish'])->assertOk()->assertJsonPath('data.status', 'publish');

        $this->api('DELETE', "/services/tour/{$t}")->assertNoContent();
        $this->assertSame(0, $this->apiGet('/services')->json('meta.total'));
        $this->assertSame(1, $this->apiGet('/services', ['deleted' => 'only'])->json('meta.total'));
        $this->assertTrue($this->apiGet('/services', ['deleted' => 'only'])->json('data.0.deleted'));
        $this->api('DELETE', "/services/tour/{$t}")->assertNotFound();
        $this->api('POST', "/services/tour/{$t}/restore")->assertOk()->assertJsonPath('data.deleted', false);
        $this->assertSame(1, $this->apiGet('/services')->json('meta.total'));
    }

    public function test_listings_of_another_vendor_are_invisible_and_untouchable(): void
    {
        $theirs = $this->tour('Theirs', [], $this->other->id);
        $this->apiGet("/services/tour/{$theirs}")->assertNotFound();
        $this->api('PATCH', "/services/tour/{$theirs}/status", ['status' => 'draft'])->assertNotFound();
        $this->api('DELETE', "/services/tour/{$theirs}")->assertNotFound();
        $this->api('POST', "/services/tour/{$theirs}/restore")->assertNotFound();
        $this->assertSame('publish', DB::table('bc_tours')->where('id', $theirs)->value('status'));
        $this->assertNull(DB::table('bc_tours')->where('id', $theirs)->value('deleted_at'));

        $this->apiGet('/services', [], $this->pk)->assertOk();
        $this->api('DELETE', "/services/tour/{$theirs}", [], $this->pk)->assertForbidden();
    }

    // ── Departures ───────────────────────────────────────────────────────────

    public function test_schedule_board_change_close_and_delete(): void
    {
        $t = $this->tour('Gorge swing');
        $t2 = $this->tour('Village tour');
        $mon = now()->next('Monday');

        $r = $this->api('POST', '/departures', ['tour_ids' => [$t, $t2], 'from' => $mon->toDateString(), 'to' => $mon->copy()->addDays(13)->toDateString(), 'weekdays' => [1], 'capacity' => 12, 'price' => 90])->assertCreated()->json('data');
        $this->assertSame(['made' => 4, 'changed' => 0], ['made' => $r['made'], 'changed' => $r['changed']]);
        $again = $this->api('POST', '/departures', ['tour_ids' => [$t], 'from' => $mon->toDateString(), 'capacity' => 15])->assertCreated()->json('data');
        $this->assertSame(['made' => 0, 'changed' => 1], ['made' => $again['made'], 'changed' => $again['changed']], 'a day that has one is updated, not doubled');

        $board = $this->apiGet('/departures', ['from' => $mon->toDateString(), 'to' => $mon->copy()->addDays(20)->toDateString()])->assertOk();
        $this->assertSame(4, $board->json('meta.total'));
        $first = $board->json('data.0');
        $this->assertSame([15, 15], [$first['capacity'], $first['seats_left']]);
        $this->assertSame('open', $first['status']);
        $this->assertSame(2, count($this->apiGet('/departures', ['tour_id' => $t2, 'from' => $mon->toDateString(), 'to' => $mon->copy()->addDays(20)->toDateString()])->json('data')));

        $id = $first['id'];
        $this->api('PATCH', "/departures/{$id}", ['capacity' => 20, 'active' => false])->assertOk()->assertJsonPath('data.status', 'closed')->assertJsonPath('data.capacity', 20);
        $this->assertSame(1, count($this->apiGet('/departures', ['status' => 'closed', 'from' => $mon->toDateString(), 'to' => $mon->copy()->addDays(20)->toDateString()])->json('data')));

        // Booked seats stop it shrinking or being deleted.
        DB::table('bc_bookings')->insert(['code' => 'D1', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $t, 'total_guests' => 8, 'status' => 'confirmed', 'start_date' => $mon->toDateString() . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
        $this->api('PATCH', "/departures/{$id}", ['capacity' => 5])->assertStatus(409)->assertJsonPath('error.code', 'below_booked');
        $this->api('DELETE', "/departures/{$id}")->assertStatus(409)->assertJsonPath('error.code', 'has_bookings');
        $other = collect($board->json('data'))->firstWhere('tour.id', $t2);
        $this->api('DELETE', "/departures/{$other['id']}")->assertNoContent();
    }

    public function test_scheduling_rules_and_isolation(): void
    {
        $mine = $this->tour();
        $theirs = $this->tour('Theirs', [], $this->other->id);
        $day = now()->addDays(3)->toDateString();

        $this->api('POST', '/departures', ['tour_ids' => [$theirs], 'from' => $day, 'capacity' => 5])->assertNotFound()->assertJsonPath('error.code', 'no_tours');
        $this->api('POST', '/departures', ['tour_ids' => [$mine], 'from' => now()->subDay()->toDateString(), 'capacity' => 5])->assertStatus(422);
        $this->api('POST', '/departures', ['tour_ids' => [$mine], 'from' => $day, 'capacity' => 0])->assertStatus(422);
        $this->api('POST', '/departures', ['tour_ids' => [$mine], 'from' => $day, 'to' => now()->addDays(4)->toDateString(), 'weekdays' => [(now()->addDays(3)->dayOfWeek + 2) % 7], 'capacity' => 5])->assertStatus(422)->assertJsonPath('error.code', 'no_days');

        $this->api('POST', '/departures', ['tour_ids' => [$mine], 'from' => $day, 'capacity' => 5])->assertCreated();
        $id = $this->apiGet('/departures')->json('data.0.id');
        $this->assertSame(0, $this->apiGet('/departures', [], $this->otherKey)->json('meta.total'));
        $this->api('PATCH', "/departures/{$id}", ['capacity' => 9], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/departures/{$id}", [], $this->otherKey)->assertNotFound();
        $this->api('PUT', "/services/tours/{$mine}/capacity", ['capacity' => 3], $this->otherKey)->assertNotFound();
    }

    public function test_usual_capacity_and_only_listed_days(): void
    {
        $t = $this->tour();
        $this->api('PUT', "/services/tours/{$t}/capacity", ['capacity' => 25, 'only_listed' => true])->assertOk()->assertJsonPath('data.capacity', 25);
        $row = DB::table('bc_tours')->where('id', $t)->first();
        $this->assertSame([25, 0], [(int) $row->max_people, (int) $row->default_state]);
        $this->api('PUT', "/services/tours/{$t}/capacity", ['capacity' => 0])->assertOk()->assertJsonPath('data.capacity', null);
        $this->assertNull(DB::table('bc_tours')->where('id', $t)->value('max_people'));
        $this->api('PUT', "/services/tours/{$t}/capacity", ['capacity' => -4])->assertStatus(422);
    }

    // ── Options ──────────────────────────────────────────────────────────────

    public function test_options_can_be_set_replaced_priced_and_removed(): void
    {
        $t = $this->tour();
        $addon = $this->api('POST', '/addons', ['name' => 'Transfer', 'category' => 'transport', 'price' => 30, 'price_type' => 'per_booking'])->assertCreated()->json('data.id');

        $tier = $this->api('PUT', "/services/tours/{$t}/tiers/signature", [
            'name' => 'Signature', 'price' => 190, 'min_guests' => 2, 'max_guests' => 8, 'recommended' => true,
            'bands' => [['min' => 4, 'max' => 6, 'total' => 640]], 'inclusions' => ['Riverside lunch'], 'addon_ids' => [$addon],
        ])->assertOk()->json('data');
        $this->assertSame([true, 106.67], [$tier['recommended'], $tier['from_price']]);
        $this->assertSame('Transfer', $tier['included_addons'][0]['name']);

        $q = $this->apiGet("/services/tours/{$t}/tiers", ['guests' => 5])->json('data.0');
        $this->assertEquals(640, $q['total_for_party']);
        $this->assertTrue($q['available_for_party']);

        $this->api('PUT', "/services/tours/{$t}/tiers/classic", ['name' => 'Classic', 'price' => 120, 'recommended' => true])->assertOk();
        $this->assertSame(['classic'], array_column(array_filter($this->apiGet("/services/tours/{$t}/tiers")->json('data'), fn ($x) => $x['recommended']), 'key') ?: ['classic'], 'only one option is recommended');
        $this->assertCount(1, array_filter($this->apiGet("/services/tours/{$t}/tiers")->json('data'), fn ($x) => $x['recommended']));

        $this->api('PUT', "/services/tours/{$t}/tiers/sublime", ['name' => 'S', 'price' => 300, 'bands' => [['min' => 2, 'max' => 5, 'total' => 900], ['min' => 4, 'total' => 1200]]])->assertStatus(422)->assertJsonPath('error.code', 'band_overlap');
        $this->api('PUT', "/services/tours/{$t}/tiers/sublime", ['name' => 'S', 'bands' => [['min' => 5, 'max' => 3, 'total' => 900]]])->assertStatus(422)->assertJsonPath('error.code', 'band_range');
        $this->api('PUT', "/services/tours/{$t}/tiers/sublime", ['name' => 'S'])->assertStatus(422)->assertJsonPath('error.code', 'price_required');
        $this->api('PUT', "/services/tours/{$t}/tiers/platinum", ['name' => 'P', 'price' => 1])->assertNotFound()->assertJsonPath('error.code', 'unknown_tier');

        $this->api('DELETE', "/services/tours/{$t}/tiers/classic")->assertNoContent();
        $this->api('DELETE', "/services/tours/{$t}/tiers/classic")->assertNotFound();
        $theirs = $this->tour('Theirs', [], $this->other->id);
        $this->api('PUT', "/services/tours/{$theirs}/tiers/classic", ['name' => 'C', 'price' => 1])->assertNotFound();
    }

    public function test_a_tier_cannot_bundle_another_vendors_addon(): void
    {
        $t = $this->tour();
        $theirs = $this->api('POST', '/addons', ['name' => 'Theirs', 'category' => 'activity', 'price' => 1, 'price_type' => 'per_booking'], $this->otherKey)->assertCreated()->json('data.id');

        $tier = $this->api('PUT', "/services/tours/{$t}/tiers/classic", ['name' => 'C', 'price' => 100, 'addon_ids' => [$theirs]])->assertOk()->json('data');

        $this->assertSame([], $tier['included_addons']);
    }

    // ── Add-ons ──────────────────────────────────────────────────────────────

    public function test_addon_catalogue_crud_search_and_service_links(): void
    {
        $t = $this->tour('Gorge swing');
        $theirTour = $this->tour('Theirs', [], $this->other->id);

        $a = $this->api('POST', '/addons', ['name' => 'Photo package', 'category' => 'memory', 'price' => 50, 'price_type' => 'per_person', 'short_description' => 'A photographer joins you'])->assertCreated()->json('data');
        $this->assertTrue($a['is_global'], 'offered everywhere unless services are given');
        $b = $this->api('POST', '/addons', ['name' => 'Bungee', 'category' => 'activity', 'price' => 120, 'price_type' => 'per_person', 'is_featured' => true,
            'services' => [['object_model' => 'tour', 'object_id' => $t, 'price_override' => 100], ['object_model' => 'tour', 'object_id' => $theirTour, 'price_override' => 1]]])->assertCreated()->json('data');
        $this->assertFalse($b['is_global']);
        $this->assertCount(1, $b['services'], "another vendor's tour is dropped");
        $this->assertSame([100.0, 'Gorge swing'], [(float) $b['services'][0]['price_override'], $b['services'][0]['title']]);

        $ids = fn (array $q) => array_column($this->apiGet('/addons', $q)->json('data'), 'id');
        $this->assertSame([$b['id']], $ids(['featured' => 'true']));
        $this->assertSame([$a['id']], $ids(['q' => 'photographer']));
        $this->assertSame([$b['id']], $ids(['category' => 'activity']));
        $this->assertEqualsCanonicalizing([$a['id'], $b['id']], $ids([]));

        $this->api('PUT', "/addons/{$a['id']}", ['price' => 60, 'status' => 'draft'])->assertOk()->assertJsonPath('data.price', 60)->assertJsonPath('data.status', 'draft');
        $this->assertSame([$a['id']], $ids(['status' => 'draft']));
        $this->api('PUT', "/addons/{$b['id']}", ['services' => []])->assertOk()->assertJsonPath('data.services', []);

        $this->api('POST', '/addons', ['name' => 'X', 'category' => 'nope', 'price' => -1, 'price_type' => 'weird'])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['category', 'price', 'price_type']]]);
        $this->apiGet("/addons/{$a['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/addons/{$a['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/addons/{$a['id']}")->assertNoContent();
    }

    // ── Insights and marketplace ─────────────────────────────────────────────

    public function test_occupancy_pins_and_marketplace(): void
    {
        $t = $this->tour('Gorge swing', ['max_people' => 10]);
        $day = now()->addDays(4)->toDateString();
        DB::table('bc_bookings')->insert(['code' => 'O1', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $t, 'total_guests' => 6, 'status' => 'confirmed', 'start_date' => $day . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);

        $o = $this->apiGet('/analytics/occupancy', ['days' => 30])->assertOk()->json('data');
        $this->assertSame([6, 10, 60], [$o['sold'], $o['capacity'], $o['percent']]);
        $this->assertSame('Gorge swing', $o['tours'][0]['title']);
        $this->apiGet('/analytics/occupancy', ['days' => 500])->assertStatus(422);

        $this->api('PUT', "/shelves/trending/pins/{$t}")->assertOk()->assertJsonPath('data.trending', [$t]);
        $this->api('PUT', "/shelves/trending/pins/{$t}")->assertOk();
        $this->assertSame([$t], $this->apiGet('/shelves/pins')->json('data.trending'));
        $this->api('PUT', "/shelves/nonsense/pins/{$t}")->assertNotFound();
        $this->api('DELETE', "/shelves/trending/pins/{$t}")->assertNoContent();
        $this->assertSame([], $this->apiGet('/shelves/pins')->json('data.trending'));
        $theirs = $this->tour('Theirs', [], $this->other->id);
        $this->api('PUT', "/shelves/trending/pins/{$theirs}")->assertNotFound();

        $this->assertFalse($this->apiGet('/marketplace/tours')->json('data.0.on_marketplace'));
        $this->api('PUT', "/marketplace/tours/{$t}", ['on' => true])->assertOk()->assertJsonPath('data.on_marketplace', true);
        $this->assertSame([$t], array_column($this->apiGet('/marketplace/tours', ['state' => 'on'])->json('data'), 'tour_id'));
        $this->assertSame([], $this->apiGet('/marketplace/tours', ['state' => 'on'], $this->otherKey)->json('data'));
        $this->api('PUT', "/marketplace/tours/{$theirs}", ['on' => true])->assertNotFound();
        $this->api('PUT', "/marketplace/tours/{$t}", ['on' => 'maybe'])->assertStatus(422);
    }

    public function test_scopes_apply_per_area(): void
    {
        $hash = hash_hmac('sha256', $this->key, config('app.key'));
        DB::table('bc_vendor_api_keys')->where('key_hash', $hash)->update(['scopes' => json_encode(['services:read', 'analytics:write'])]);
        $t = $this->tour();

        $this->apiGet('/services')->assertOk();
        $this->api('DELETE', "/services/tour/{$t}")->assertForbidden()->assertJsonPath('error.scope', 'services:write');
        $this->apiGet('/analytics/occupancy')->assertOk();                       // write implies read
        $this->api('PUT', "/shelves/trending/pins/{$t}")->assertOk();
        $this->apiGet('/marketplace/tours')->assertForbidden()->assertJsonPath('error.scope', 'marketplace:read');
        $this->apiGet('/bookings')->assertForbidden();
        $this->apiGet('/me')->assertOk();                                        // identity needs no scope
    }
}
