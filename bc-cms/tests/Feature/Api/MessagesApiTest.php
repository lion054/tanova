<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\ApiTestCase;

class MessagesApiTest extends ApiTestCase
{
    private function msg(array $more = []): array
    {
        return $this->api('POST', '/messages/scheduled', array_merge(['name' => 'Reminder', 'trigger' => 'pre_trip', 'offset_days' => -2, 'channel' => 'email', 'subject' => 'S', 'body' => 'Hi {name}'], $more))->assertCreated()->json('data');
    }

    public function test_options_list_the_triggers_channels_and_placeholders(): void
    {
        $o = $this->apiGet('/messages/options')->assertOk()->json('data');

        $this->assertContains('guest_form', array_column($o['triggers'], 'key'));
        $this->assertContains('email', $o['channels']);
        $this->assertContains('{guest_form_link}', array_column($o['placeholders'], 'key'));
    }

    public function test_crud_pause_search_and_filters(): void
    {
        $a = $this->msg(['name' => 'Balance reminder', 'trigger' => 'payment_due', 'body' => 'Owing {balance}']);
        $b = $this->msg(['name' => 'Welcome home', 'trigger' => 'welcome_home', 'active' => false, 'offset_days' => 1]);
        $this->assertTrue($a['active']);
        $this->assertFalse($b['active']);

        $ids = fn (array $q) => array_column($this->apiGet('/messages/scheduled', $q)->assertOk()->json('data'), 'id');
        $this->assertSame([$a['id']], $ids(['state' => 'active']));
        $this->assertSame([$b['id']], $ids(['trigger' => 'welcome_home']));
        $this->assertSame([$a['id']], $ids(['q' => 'owing']));
        $this->assertSame([$a['id'], $b['id']], $ids(['sort' => 'name']));

        $this->api('PUT', "/messages/scheduled/{$a['id']}", ['subject' => 'New subject', 'offset_days' => -14])->assertOk()->assertJsonPath('data.subject', 'New subject')->assertJsonPath('data.offset_days', -14)->assertJsonPath('data.name', 'Balance reminder');
        $this->api('PATCH', "/messages/scheduled/{$a['id']}", ['active' => false])->assertOk()->assertJsonPath('data.active', false);
        $this->api('PATCH', "/messages/scheduled/{$a['id']}", [])->assertStatus(422);
        $this->api('POST', '/messages/scheduled', ['name' => 'X', 'trigger' => 'nonsense', 'channel' => 'pigeon', 'body' => 'x', 'offset_days' => 999])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['trigger', 'channel', 'offset_days']]]);

        $this->api('DELETE', "/messages/scheduled/{$b['id']}")->assertNoContent();
        $this->apiGet("/messages/scheduled/{$b['id']}")->assertNotFound();
    }

    public function test_the_starter_pack_is_paused_and_only_adds_what_is_missing(): void
    {
        $first = $this->api('POST', '/messages/scheduled/starter')->assertCreated();
        $this->assertSame(9, $first->json('meta.added'));
        $this->assertSame([false], array_values(array_unique(array_column($first->json('data'), 'active'))), 'nothing goes out until they are switched on');

        $this->assertSame(0, $this->api('POST', '/messages/scheduled/starter')->json('meta.added'));
        $this->api('DELETE', '/messages/scheduled/' . $first->json('data.0.id'))->assertNoContent();
        $this->assertSame(1, $this->api('POST', '/messages/scheduled/starter')->json('meta.added'));
    }

    public function test_the_log_shows_what_was_sent(): void
    {
        $m = $this->msg();
        DB::table('bc_vendor_scheduled_message_logs')->insert([
            ['vendor_id' => $this->vendor->id, 'scheduled_message_id' => $m['id'], 'booking_id' => 5, 'channel' => 'email', 'recipient' => 'ann@x.com', 'status' => 'sent', 'error' => null, 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['vendor_id' => $this->vendor->id, 'scheduled_message_id' => $m['id'], 'booking_id' => 6, 'channel' => 'whatsapp', 'recipient' => null, 'status' => 'skipped', 'error' => 'whatsapp_not_connected', 'sent_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $all = $this->apiGet('/messages/scheduled/log')->assertOk();
        $this->assertSame(2, $all->json('meta.total'));
        $this->assertSame('Reminder', $all->json('data.0.message'));
        $this->assertSame(['whatsapp_not_connected'], array_column($this->apiGet('/messages/scheduled/log', ['status' => 'skipped'])->json('data'), 'error'));
        $this->assertSame(2, $this->apiGet("/messages/scheduled/{$m['id']}")->json('data.sent_count'));
        $this->assertSame(0, $this->apiGet('/messages/scheduled/log', [], $this->otherKey)->json('meta.total'));
    }

    public function test_campaigns_go_only_to_this_vendors_own_guests_and_only_once(): void
    {
        Mail::fake();
        $tour = DB::table('bc_tours')->insertGetId(['title' => 'T', 'slug' => 't', 'author_id' => $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        $mk = fn (int $vendor, string $email, string $status, int $days) => DB::table('bc_bookings')->insert(['code' => uniqid(), 'vendor_id' => $vendor, 'object_model' => 'tour', 'object_id' => $tour, 'email' => $email, 'first_name' => 'X', 'total' => 1, 'status' => $status, 'start_date' => now()->addDays($days)->toDateString() . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
        $mk($this->vendor->id, 'Ann@X.com', 'completed', -30);
        $mk($this->vendor->id, 'ann@x.com', 'confirmed', 10);        // the same person twice
        $mk($this->vendor->id, 'bob@x.com', 'confirmed', 20);
        $mk($this->vendor->id, 'not-an-email', 'confirmed', 5);
        $mk($this->other->id, 'theirs@x.com', 'confirmed', 5);

        $aud = collect($this->apiGet('/messages/campaigns/audiences')->json('data'))->keyBy('key');
        $this->assertSame([2, 1, 2], [$aud['all_customers']['recipients'], $aud['completed']['recipients'], $aud['upcoming']['recipients']]);

        $c = $this->api('POST', '/messages/campaigns', ['subject' => 'Hello', 'body' => '<p>Hi</p>', 'audience' => 'all_customers'])->assertCreated()->json('data');
        $this->assertSame('draft', $c['status']);
        $this->api('PUT', "/messages/campaigns/{$c['id']}", ['subject' => 'Hello again'])->assertOk()->assertJsonPath('data.subject', 'Hello again');
        $this->api('POST', '/messages/campaigns', ['subject' => 'X', 'body' => 'x', 'audience' => 'everyone'])->assertStatus(422);

        // A test key only says how many it would reach.
        $sim = $this->api('POST', "/messages/campaigns/{$c['id']}/send")->assertOk()->json('data');
        $this->assertSame([2, true, 'draft'], [$sim['recipients'], $sim['simulated'], $sim['status']]);
        Mail::assertNothingSent();

        $sent = $this->api('POST', "/messages/campaigns/{$c['id']}/send", [], $this->liveKey)->assertStatus(202)->json('data');
        $this->assertSame(2, $sent['recipients']);
        $done = $this->apiGet("/messages/campaigns/{$c['id']}", [], $this->liveKey)->json('data');
        $this->assertSame(['sent', 2], [$done['status'], $done['sent_count']]);
        $this->api('POST', "/messages/campaigns/{$c['id']}/send", [], $this->liveKey)->assertStatus(409)->assertJsonPath('error.code', 'already_sent');
        $this->api('PUT', "/messages/campaigns/{$c['id']}", ['subject' => 'x'], $this->liveKey)->assertStatus(409)->assertJsonPath('error.code', 'not_a_draft');
    }

    public function test_isolation_scopes_and_read_only_keys(): void
    {
        $m = $this->msg();
        $c = $this->api('POST', '/messages/campaigns', ['subject' => 'S', 'body' => 'b', 'audience' => 'all_customers'])->json('data');

        $this->apiGet("/messages/scheduled/{$m['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('PUT', "/messages/scheduled/{$m['id']}", ['name' => 'x'], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/messages/scheduled/{$m['id']}", [], $this->otherKey)->assertNotFound();
        $this->apiGet("/messages/campaigns/{$c['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/messages/campaigns/{$c['id']}/send", [], $this->otherKey)->assertNotFound();
        $this->assertSame(0, $this->apiGet('/messages/scheduled', [], $this->otherKey)->json('meta.total'));

        $this->apiGet('/messages/scheduled', [], $this->pk)->assertOk();
        $this->api('POST', "/messages/campaigns/{$c['id']}/send", [], $this->pk)->assertForbidden();
    }
}
