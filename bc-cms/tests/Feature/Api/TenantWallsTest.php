<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Vendor\Events\VendorTeamRequestCreatedEvent;
use Modules\Vendor\Models\VendorAllowedOrigin;
use Modules\Vendor\Models\VendorApiKey;
use Modules\Vendor\Models\VendorWebhook;
use Tests\ApiTestCase;

/** The models that are queried with explicit filters instead of the automatic tenant scope: each screen that touches them must stay inside one business. */
class TenantWallsTest extends ApiTestCase
{
    public function test_a_vendor_cannot_re_send_another_vendors_team_invitation(): void
    {
        Event::fake([VendorTeamRequestCreatedEvent::class]);
        $member = $this->makeVendor('Team Member');
        $mine = DB::table('vendor_team')->insertGetId(['vendor_id' => $this->vendor->id, 'member_id' => $member->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $theirMember = $this->makeVendor('Their Member');   // nobody works for two companies
        $theirs = DB::table('vendor_team')->insertGetId(['vendor_id' => $this->other->id, 'member_id' => $theirMember->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->vendor);

        $this->get(route('vendor.team.re-send-request', $theirs));
        Event::assertNotDispatched(VendorTeamRequestCreatedEvent::class);
        $this->get(route('vendor.team.re-send-request', $mine));
        Event::assertDispatched(VendorTeamRequestCreatedEvent::class);
    }

    public function test_keys_origins_and_webhooks_of_another_vendor_are_out_of_reach_in_the_portal(): void
    {
        $theirKey = VendorApiKey::generate($this->other, 'theirs', 100, null, 'secret', 'test');
        $theirOrigin = VendorAllowedOrigin::create(['vendor_id' => $this->other->id, 'origin' => 'https://theirs.example']);
        $theirHook = VendorWebhook::generate($this->other->id, 'https://theirs.example/hook', ['booking.paid']);
        $this->actingAs($this->vendor);

        $this->post("/vendor/api-keys/{$theirKey->id}/revoke")->assertNotFound();
        $this->post("/vendor/api-keys/{$theirKey->id}/rotate")->assertNotFound();
        $this->delete("/vendor/api-keys/origins/{$theirOrigin->id}")->assertNotFound();
        $this->delete("/vendor/api-keys/webhooks/{$theirHook->id}")->assertNotFound();
        $this->assertTrue((bool) VendorApiKey::find($theirKey->id)->active);
        $this->assertNotNull(VendorAllowedOrigin::find($theirOrigin->id));
        $this->assertNotNull(VendorWebhook::find($theirHook->id));
        $this->get('/vendor/api-keys')->assertOk()->assertDontSee('theirs.example', false);
    }

    public function test_the_api_never_returns_another_vendors_concierge_conversation(): void
    {
        $theirs = DB::table('bc_concierge_conversations')->insertGetId(['vendor_id' => $this->other->id, 'guest_name' => 'Zed Other', 'status' => 'open', 'channel' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame([], $this->apiGet('/concierge/conversations')->json('data'));
        $this->apiGet("/concierge/conversations/{$theirs}/messages")->assertNotFound();
        $this->api('POST', "/concierge/conversations/{$theirs}/close")->assertNotFound();
        $this->assertSame(0, $this->apiGet('/concierge/statistics')->json('data.summary.total_conversations'));
    }
}
