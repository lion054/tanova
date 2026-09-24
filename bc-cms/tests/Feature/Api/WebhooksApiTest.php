<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Vendor\Models\VendorWebhook;
use Modules\Vendor\Models\VendorWebhookDelivery;
use Modules\Vendor\Services\WebhookDeliverer;
use Tests\ApiTestCase;

class WebhooksApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WebhookDeliverer::$resolver = fn (string $host) => match ($host) {
            'internal.example' => ['10.0.0.5'],
            'metadata.example' => ['169.254.169.254'],
            default => ['93.184.216.34'],
        };
    }

    protected function tearDown(): void
    {
        WebhookDeliverer::$resolver = null;
        parent::tearDown();
    }

    private function hook(array $events = ['*'], string $url = 'https://hooks.example.com/tsoka'): array
    {
        return $this->api('POST', '/webhooks', ['url' => $url, 'events' => $events])->assertCreated()->json('data');
    }

    public function test_register_shows_the_secret_once_and_lists_events(): void
    {
        $types = array_column($this->apiGet('/webhooks/events')->assertOk()->json('data'), 'type');
        $this->assertContains('booking.payment_received', $types);
        $this->assertContains('waitlist.joined', $types);

        $h = $this->hook(['booking.paid', 'booking.paid', 'invoice.paid']);
        $this->assertSame(32, strlen($h['secret']));
        $this->assertSame(['booking.paid', 'invoice.paid'], $h['events']);
        $this->assertArrayNotHasKey('secret', $this->apiGet("/webhooks/{$h['id']}")->json('data'));

        $rot = $this->api('POST', "/webhooks/{$h['id']}/rotate-secret")->assertOk()->json('data');
        $this->assertNotSame($h['secret'], $rot['secret']);
    }

    public function test_only_public_https_addresses_are_accepted(): void
    {
        foreach (['http://hooks.example.com/x', 'https://internal.example/x', 'https://metadata.example/x', 'https://10.1.2.3/x', 'https://127.0.0.1/x', 'https://[::1]/x', 'https://169.254.169.254/latest', 'https://user:pw@hooks.example.com/x', 'ftp://x.example.com', 'not a url'] as $bad) {
            $this->api('POST', '/webhooks', ['url' => $bad, 'events' => ['*']])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        }
        $this->api('POST', '/webhooks', ['url' => 'https://hooks.example.com/x', 'events' => ['booking.exploded']])->assertStatus(422);
        $this->api('POST', '/webhooks', ['url' => 'https://hooks.example.com/x', 'events' => []])->assertStatus(422);
        $h = $this->hook();
        $this->api('PUT', "/webhooks/{$h['id']}", ['url' => 'https://internal.example/x'])->assertStatus(422);
        $this->api('PUT', "/webhooks/{$h['id']}", ['active' => false, 'events' => ['invoice.created']])->assertOk()->assertJsonPath('data.active', false)->assertJsonPath('data.events', ['invoice.created']);
    }

    public function test_a_ping_is_signed_with_both_signatures_and_recorded(): void
    {
        Http::fake(['hooks.example.com/*' => Http::response('ok', 200)]);
        $h = $this->hook();

        $d = $this->api('POST', "/webhooks/{$h['id']}/test")->assertOk()->json('data');

        $this->assertSame([true, 200, 'ping'], [$d['success'], $d['status_code'], $d['event']]);
        Http::assertSent(function ($req) use ($h) {
            $sig = $req->header('X-Tsoka-Signature-256')[0];
            preg_match('/^t=(\d+),v1=([0-9a-f]{64}),v2=([0-9a-f]{64})$/', $sig, $m);
            $secret = DB::table('bc_vendor_webhooks')->where('id', $h['id'])->value('secret');
            $body = $req->body();

            return $m
                && hash_equals(hash_hmac('sha256', $body, $secret), $m[2])                       // v1: the body
                && hash_equals(hash_hmac('sha256', $m[1] . '.' . $body, $secret), $m[3])         // v2: the timestamp and the body
                && $req->header('X-Tsoka-Event')[0] === 'ping'
                && json_decode($body, true)['type'] === 'ping';
        });
    }

    public function test_an_event_is_sent_with_its_envelope_and_the_older_booking_keys(): void
    {
        Http::fake(['hooks.example.com/*' => Http::response('ok', 200)]);
        $this->hook(['booking.confirmed', 'waitlist.joined']);
        $tour = DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gs', 'author_id' => $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_bookings')->insert(['code' => 'WH0001', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $tour, 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'ann@example.com', 'total' => 300, 'paid' => 0, 'total_guests' => 2, 'status' => 'unpaid', 'start_date' => now()->addDays(9)->toDateString() . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);

        $this->api('PATCH', '/bookings/WH0001/status', ['status' => 'confirmed'])->assertOk();
        $this->api('POST', '/waitlist', ['customer_name' => 'Cy Dube', 'customer_email' => 'cy@example.com'])->assertCreated();

        $seen = [];
        Http::assertSent(function ($req) use (&$seen) { $seen[json_decode($req->body(), true)['type']] = json_decode($req->body(), true); return true; });
        $b = $seen['booking.confirmed'];
        $this->assertMatchesRegularExpression('/^evt_[a-z0-9]{24}$/', $b['id']);
        $this->assertSame(['WH0001', 'confirmed'], [$b['data']['object']['code'], $b['data']['object']['status']]);
        $this->assertSame('WH0001', $b['booking']['code'], 'the original keys are still there');
        $this->assertSame('booking.confirmed', $b['event']);
        $this->assertSame(\App\Http\Middleware\ApiVersion::CURRENT, $b['api_version']);
        $this->assertSame('Cy Dube', $seen['waitlist.joined']['data']['object']['name']);
        $this->assertArrayNotHasKey('booking.cancelled', $seen);
    }

    public function test_a_dead_endpoint_never_breaks_the_request_and_is_retried_with_backoff(): void
    {
        Http::fake(['hooks.example.com/*' => Http::sequence()->push('nope', 500)->push('ok', 200)]);
        $h = $this->hook(['invoice.created']);

        // The event that caused it still succeeds.
        $this->api('POST', '/invoices', ['bill_to_name' => 'Ann'])->assertCreated();

        $d = VendorWebhookDelivery::where('webhook_id', $h['id'])->first();
        $this->assertSame([false, 500, 1], [(bool) $d->success, $d->status_code, (int) $d->attempts]);
        $this->assertEqualsWithDelta(60, now()->diffInSeconds($d->next_attempt_at, false), 5, 'first retry in about a minute');

        // Nothing is retried before it is due, everything due is retried, and the endpoint can recover.
        $this->assertSame(0, app(WebhookDeliverer::class)->retryDue());
        $this->travel(2)->minutes();
        $this->assertSame(1, app(WebhookDeliverer::class)->retryDue());
        $d->refresh();
        $this->assertSame([true, 2, null], [(bool) $d->success, (int) $d->attempts, $d->next_attempt_at]);
    }

    public function test_after_six_attempts_it_stays_failed_and_a_network_error_is_recorded_not_thrown(): void
    {
        Http::fake(['hooks.example.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);
        $h = $this->hook(['invoice.created']);
        $this->api('POST', '/invoices', ['bill_to_name' => 'Ann'])->assertCreated();

        $d = VendorWebhookDelivery::where('webhook_id', $h['id'])->first();
        $this->assertStringContainsString('timed out', $d->response_body);
        for ($i = 0; $i < 8; $i++) {
            $this->travel(13)->hours();
            app(WebhookDeliverer::class)->retryDue();
        }
        $d->refresh();
        $this->assertSame([6, null, false], [(int) $d->attempts, $d->next_attempt_at, (bool) $d->success]);
    }

    public function test_redelivery_sends_the_same_event_again_and_deliveries_can_be_read(): void
    {
        Http::fake(['hooks.example.com/*' => Http::response('ok', 200)]);
        $h = $this->hook(['invoice.created']);
        $this->api('POST', '/invoices', ['bill_to_name' => 'Ann'])->assertCreated();

        $list = $this->apiGet("/webhooks/{$h['id']}/deliveries", ['success' => 'true', 'event' => 'invoice.created'])->assertOk();
        $this->assertSame(1, $list->json('meta.total'));
        $did = $list->json('data.0.id');
        $one = $this->apiGet("/webhooks/{$h['id']}/deliveries/{$did}")->json('data');
        $this->assertSame('INV-' . date('Y') . '-001', $one['payload']['data']['object']['number']);

        $again = $this->api('POST', "/webhooks/{$h['id']}/deliveries/{$did}/redeliver")->assertOk()->json('data');
        $this->assertSame([$one['event_id'], 2], [$again['event_id'], $again['attempts']]);
    }

    public function test_events_are_not_sent_to_the_wrong_vendor_or_an_inactive_or_unsubscribed_hook(): void
    {
        Http::fake();
        $mine = $this->hook(['invoice.created']);
        $this->api('POST', '/webhooks', ['url' => 'https://other.example.com/x', 'events' => ['*']], $this->otherKey)->assertCreated();
        $this->hook(['booking.paid']);
        $this->api('PUT', "/webhooks/{$mine['id']}", ['active' => false])->assertOk();

        $this->api('POST', '/invoices', ['bill_to_name' => 'Ann'])->assertCreated();

        Http::assertNothingSent();
        $this->assertSame(0, VendorWebhookDelivery::count());
    }

    public function test_isolation_and_scopes(): void
    {
        $h = $this->hook();
        $this->apiGet("/webhooks/{$h['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('PUT', "/webhooks/{$h['id']}", ['active' => false], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/webhooks/{$h['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/webhooks/{$h['id']}/test", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/webhooks/{$h['id']}/rotate-secret", [], $this->otherKey)->assertNotFound();
        $this->assertSame(0, $this->apiGet('/webhooks', [], $this->otherKey)->json('meta.total'));
        $this->apiGet('/webhooks', [], $this->pk)->assertOk();
        $this->api('POST', '/webhooks', ['url' => 'https://hooks.example.com/x', 'events' => ['*']], $this->pk)->assertForbidden();

        DB::table('bc_vendor_api_keys')->where('key_hash', hash_hmac('sha256', $this->key, config('app.key')))->update(['scopes' => json_encode(['bookings:write'])]);
        $this->apiGet('/webhooks')->assertForbidden()->assertJsonPath('error.scope', 'webhooks:read');
    }
}
