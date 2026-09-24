<?php

namespace Tests\Feature\Api;

use Laravel\Sanctum\Sanctum;
use Modules\Vendor\Models\VendorApiKey;
use Tests\ApiTestCase;

class KeyScopesTest extends ApiTestCase
{
    private function make(array $body): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->vendor);

        return $this->postJson('/api/vendor/api-keys', $body + ['name' => 'Website', 'mode' => 'test']);
    }

    public function test_a_key_made_with_scopes_can_only_use_those_areas(): void
    {
        $r = $this->make(['type' => 'secret', 'scopes' => ['bookings:read', 'waitlist:write']])->assertCreated();
        $this->assertEqualsCanonicalizing(['bookings:read', 'waitlist:write'], $r->json('data.scopes'));
        $key = $r->json('data.key');

        $this->apiGet('/bookings', [], $key)->assertOk();
        $this->apiGet('/waitlist', [], $key)->assertOk();          // write includes read
        $this->apiGet('/invoices', [], $key)->assertForbidden()->assertJsonPath('error.code', 'insufficient_scope')->assertJsonPath('error.scope', 'invoices:read');
        $this->api('PATCH', '/bookings/ABC/status', ['status' => 'confirmed'], $key)->assertForbidden()->assertJsonPath('error.scope', 'bookings:write');
    }

    public function test_no_scopes_means_full_access_and_a_publishable_key_stays_read_only(): void
    {
        $full = $this->make(['type' => 'secret'])->assertCreated();
        $this->assertNull($full->json('data.scopes'));
        $this->apiGet('/invoices', [], $full->json('data.key'))->assertOk();

        $pub = $this->make(['type' => 'publishable', 'scopes' => ['invoices:write']])->assertCreated();
        $this->assertSame(['*:read'], $pub->json('data.scopes'));
    }

    public function test_an_unknown_scope_is_refused(): void
    {
        $this->make(['type' => 'secret', 'scopes' => ['everything:write']])->assertStatus(422);
        $this->make(['type' => 'secret', 'scopes' => 'bookings:read'])->assertStatus(422);
    }

    public function test_scopes_survive_a_rotation(): void
    {
        $r = $this->make(['type' => 'secret', 'scopes' => ['loyalty:read']])->assertCreated();
        Sanctum::actingAs($this->vendor);
        $new = $this->postJson('/api/vendor/api-keys/' . $r->json('data.id') . '/rotate')->assertOk()->json('data.key');
        $this->apiGet('/loyalty/rule', [], $new)->assertOk();
        $this->apiGet('/bookings', [], $new)->assertForbidden();
        $this->assertSame(['loyalty:read'], VendorApiKey::find($r->json('data.id'))->scopes);
    }
}
