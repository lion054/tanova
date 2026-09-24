<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\ApiTestCase;

/** A test key skips the plan check and never sends anything to a guest: no e-mail, no message, no payment, no "told" status. */
class TestModeTest extends ApiTestCase
{
    private int $tour;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tour = DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gs', 'author_id' => $this->vendor->id, 'status' => 'publish', 'max_people' => 10, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function booking(): string
    {
        DB::table('bc_bookings')->insert(['code' => 'TM0001', 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $this->tour, 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'ann@example.com', 'total' => 300, 'paid' => 0, 'total_guests' => 2, 'status' => 'unpaid', 'start_date' => now()->addDays(10)->toDateString() . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);

        return 'TM0001';
    }

    public function test_the_mode_is_announced_on_every_response(): void
    {
        $this->assertSame('test', $this->apiGet('/me')->headers->get('X-Tsoka-Mode'));
        $this->assertSame('live', $this->apiGet('/me', [], $this->liveKey)->headers->get('X-Tsoka-Mode'));
    }

    public function test_a_test_key_needs_no_plan_but_a_live_key_does(): void
    {
        $this->vendor->vendor_plan_expires_at = now()->subDay();
        $this->vendor->save();

        $this->apiGet('/me')->assertOk();
        $this->apiGet('/me', [], $this->liveKey)->assertStatus(402)->assertJsonPath('error.code', 'subscription_required');
    }

    public function test_a_trip_brief_with_a_test_key_is_simulated_and_writes_nothing(): void
    {
        Mail::fake();
        $code = $this->booking();

        $this->api('POST', "/bookings/{$code}/trip-brief", ['note' => 'x'])->assertOk()->assertJsonPath('data.simulated', true)->assertJsonPath('data.documents_linked', 0);

        Mail::assertNothingSent();
        $this->assertSame(0, $this->apiGet("/bookings/{$code}/timeline")->json('meta.total'));
    }

    public function test_telling_the_waitlist_with_a_test_key_changes_nobodys_status(): void
    {
        $day = now()->addDays(10)->toDateString();
        $e = $this->api('POST', '/waitlist', ['customer_name' => 'Cy Dube', 'customer_email' => 'cy@example.com', 'tour_id' => $this->tour, 'party_size' => 2, 'preferred_date' => $day])->assertCreated()->json('data');

        $one = $this->api('POST', "/waitlist/{$e['id']}/notify")->assertOk()->json('data');
        $this->assertSame([false, true, 'simulated'], [$one['sent'], $one['simulated'], $one['result']]);
        $this->assertSame('waiting', $this->apiGet("/waitlist/{$e['id']}")->json('data.status'));

        // The dry run still says how many would be told.
        $all = $this->api('POST', '/waitlist/notify-openings', ['tour_id' => $this->tour])->assertOk()->json('data');
        $this->assertSame([1, true], [$all['told'], $all['simulated']]);
        $this->assertSame('waiting', $this->apiGet("/waitlist/{$e['id']}")->json('data.status'));
    }

    public function test_a_test_key_forces_the_mailer_to_capture_and_a_live_key_does_not(): void
    {
        $code = $this->booking();
        config(['mail.default' => 'log']);        // stand in for a real mailer

        $this->api('PATCH', "/bookings/{$code}/status", ['status' => 'confirmed'])->assertOk();
        $this->assertSame('array', config('mail.default'), 'e-mail from a test key is captured, never delivered');

        config(['mail.default' => 'log']);
        $this->apiGet('/me', [], $this->liveKey)->assertOk();
        $this->assertSame('log', config('mail.default'), 'a live key leaves the mailer alone');
    }

    public function test_starting_a_payment_is_refused_with_a_test_key(): void
    {
        $this->api('POST', '/customer/bookings/ANYCODE/pay', [], $this->key)->assertStatus(409)->assertJsonPath('error.code', 'test_mode');
        $this->api('POST', '/customer/bookings/pay', ['codes' => ['A']], $this->key)->assertStatus(409)->assertJsonPath('error.code', 'test_mode');
    }
}
