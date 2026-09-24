<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;

class ConciergeApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Http::fake();   // the AI draft is not what is under test, and must not call out
    }

    private function start(array $more = [], ?string $key = null): array
    {
        return $this->api('POST', '/concierge/conversations', array_merge(['message' => 'Can I move my booking?', 'guest_name' => 'Ann Ray', 'guest_email' => 'ann@example.com'], $more), $key)->assertCreated()->json('data');
    }

    public function test_start_list_show_and_read_messages(): void
    {
        $c = $this->start(['category' => 'booking', 'priority' => 'high', 'channel' => 'whatsapp']);
        $this->assertSame('open', $c['status']);
        $this->assertSame('booking', $c['category']);
        $this->assertSame($this->vendor->id, $c['vendor_id']);

        $this->assertSame([$c['id']], array_column($this->apiGet('/concierge/conversations')->assertOk()->json('data'), 'id'));
        $this->apiGet("/concierge/conversations/{$c['id']}")->assertOk()->assertJsonPath('data.guest_name', 'Ann Ray');

        $m = $this->apiGet("/concierge/conversations/{$c['id']}/messages")->assertOk();
        $this->assertSame('Can I move my booking?', $m->json('data.0.body'));
        $this->assertSame(1, $m->json('meta.total') - count(array_filter($m->json('data'), fn ($x) => $x['sender_type'] !== 'guest')));
        $this->assertSame([], $this->apiGet("/concierge/conversations/{$c['id']}/messages", ['sender_type' => 'staff'])->json('data'));
    }

    public function test_replying_and_closing(): void
    {
        $c = $this->start();
        $this->travel(5)->seconds();   // the reply limit is one per two seconds
        $r = $this->api('POST', "/concierge/conversations/{$c['id']}/send", ['body' => 'Thanks'])->assertOk();
        $this->assertSame('Thanks', $r->json('data.your_message.body'));
        $this->travel(5)->seconds();
        $this->api('POST', "/concierge/conversations/{$c['id']}/send", [])->assertStatus(422);

        $this->api('POST', "/concierge/conversations/{$c['id']}/close", ['reason' => 'Sorted by phone'])->assertOk()
            ->assertJsonPath('data.status', 'resolved')->assertJsonPath('data.resolution_reason', 'Sorted by phone');
        $this->assertSame([], $this->apiGet('/concierge/conversations')->json('data'), 'resolved ones leave the open list');
    }

    public function test_statistics(): void
    {
        $this->start(['channel' => 'web']);
        $b = $this->start(['channel' => 'whatsapp']);
        $this->api('POST', "/concierge/conversations/{$b['id']}/close")->assertOk();

        $s = $this->apiGet('/concierge/statistics')->assertOk()->json('data');
        $this->assertSame(2, $s['summary']['total_conversations']);
        $this->assertSame(['web' => 1, 'whatsapp' => 1], $s['by_channel']);
        $this->assertSame(['open' => 1, 'resolved' => 1], $s['by_status']);
        $this->assertSame([], $this->apiGet('/concierge/statistics', ['from' => now()->addDay()->toDateString()])->json('data.by_channel'));
        $this->apiGet('/concierge/statistics', ['from' => 'nonsense'])->assertStatus(422);
    }

    public function test_another_vendors_conversations_are_out_of_reach(): void
    {
        $c = $this->start();
        $this->api('POST', "/concierge/conversations/{$c['id']}/close", [], $this->otherKey)->assertNotFound();
        $this->apiGet("/concierge/conversations/{$c['id']}/messages", [], $this->otherKey)->assertNotFound();
        $this->assertSame([], $this->apiGet('/concierge/conversations', [], $this->otherKey)->json('data'));
        $this->assertSame(0, $this->apiGet('/concierge/statistics', [], $this->otherKey)->json('data.summary.total_conversations'));
        $this->apiGet("/concierge/conversations/{$c['id']}", [], $this->otherKey)->assertForbidden()->assertJsonPath('error.code', 'forbidden');
    }
}
