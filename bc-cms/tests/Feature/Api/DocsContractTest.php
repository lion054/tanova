<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

/** The documentation is a promise: real responses must carry the fields it lists, with the types it names. */
class DocsContractTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Http::fake();
    }

    private function tour(): int
    {
        return DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gs' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish', 'max_people' => 10, 'price' => 100, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_identity_and_analytics(): void
    {
        $this->assertMatchesDocs($this->apiGet('/me'), 'GET /me');
        $this->assertMatchesDocs($this->apiGet('/analytics/summary'), 'GET /analytics/summary');
        $this->assertMatchesDocs($this->apiGet('/analytics/revenue'), 'GET /analytics/revenue');
        $this->assertMatchesDocs($this->apiGet('/analytics/api-usage'), 'GET /analytics/api-usage');
    }

    public function test_waitlist_and_customers(): void
    {
        $t = $this->tour();
        $w = $this->api('POST', '/waitlist', ['customer_name' => 'Ann Ray', 'customer_email' => 'ann@example.com', 'tour_id' => $t, 'party_size' => 2, 'preferred_date' => now()->addDays(5)->toDateString()]);
        $this->assertMatchesDocs($w, 'POST /waitlist');
        $this->assertMatchesDocs($this->apiGet('/waitlist'), 'GET /waitlist');
        $this->assertMatchesDocs($this->apiGet('/waitlist/' . $w->json('data.id')), 'GET /waitlist/{id}');

        $c = $this->api('POST', '/crm/customers', ['first_name' => 'Bob', 'last_name' => 'Moyo', 'email' => 'bob@example.com']);
        $this->assertMatchesDocs($c, 'POST /crm/customers');
        $this->assertMatchesDocs($this->apiGet('/crm/customers'), 'GET /crm/customers');
    }

    public function test_suppliers_webhooks_and_concierge(): void
    {
        $s = $this->api('POST', '/suppliers', ['name' => 'Intercape', 'type' => 'transport']);
        $this->assertMatchesDocs($s, 'POST /suppliers');
        $id = $s->json('data.id');
        $this->assertMatchesDocs($this->apiGet("/suppliers/{$id}"), 'GET /suppliers/{id}');
        $this->assertMatchesDocs($this->api('POST', "/suppliers/{$id}/routes", ['origin' => 'A', 'destination' => 'B']), 'POST /suppliers/{id}/routes');
        $this->assertMatchesDocs($this->apiGet('/suppliers'), 'GET /suppliers');

        \Modules\Vendor\Services\WebhookDeliverer::$resolver = fn () => ['93.184.216.34'];
        $w = $this->api('POST', '/webhooks', ['url' => 'https://example.com/hook', 'events' => ['booking.paid']]);
        $this->assertMatchesDocs($w, 'POST /webhooks');
        $this->assertMatchesDocs($this->apiGet('/webhooks'), 'GET /webhooks');
        $this->assertMatchesDocs($this->apiGet('/webhooks/events'), 'GET /webhooks/events');
        $this->assertMatchesDocs($this->api('POST', '/webhooks/' . $w->json('data.id') . '/test'), 'POST /webhooks/{id}/test');
        $this->assertMatchesDocs($this->apiGet('/webhooks/' . $w->json('data.id') . '/deliveries'), 'GET /webhooks/{id}/deliveries');
        \Modules\Vendor\Services\WebhookDeliverer::$resolver = null;

        $c = $this->api('POST', '/concierge/conversations', ['message' => 'Hello', 'guest_name' => 'Ann']);
        $this->assertMatchesDocs($c, 'POST /concierge/conversations');
        $cid = $c->json('data.id');
        $this->assertMatchesDocs($this->apiGet('/concierge/conversations'), 'GET /concierge/conversations');
        $this->assertMatchesDocs($this->apiGet("/concierge/conversations/{$cid}"), 'GET /concierge/conversations/{conversation}');
        $this->assertMatchesDocs($this->apiGet("/concierge/conversations/{$cid}/messages"), 'GET /concierge/conversations/{id}/messages');
        $this->assertMatchesDocs($this->apiGet('/concierge/statistics'), 'GET /concierge/statistics');
        $this->assertMatchesDocs($this->api('POST', "/concierge/conversations/{$cid}/close"), 'POST /concierge/conversations/{id}/close');
    }

    public function test_listings_and_seats(): void
    {
        $t = $this->tour();
        $this->assertMatchesDocs($this->apiGet("/services/tours/{$t}"), 'GET /services/tours/{id}');
        $this->assertMatchesDocs($this->apiGet('/services/tours'), 'GET /services/tours');
        $this->assertMatchesDocs($this->apiGet("/services/tours/{$t}/departures"), 'GET /services/tours/{id}/departures');
        $this->assertMatchesDocs($this->apiGet('/upsells'), 'GET /upsells');
        $this->assertMatchesDocs($this->apiGet('/destinations'), 'GET /destinations');
    }
}
