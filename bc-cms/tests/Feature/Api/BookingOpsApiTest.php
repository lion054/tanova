<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\ApiTestCase;

class BookingOpsApiTest extends ApiTestCase
{
    private int $tour;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tour = DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gs', 'author_id' => $this->vendor->id, 'status' => 'publish', 'max_people' => 10, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function booking(array $o = [], ?int $vendor = null): string
    {
        $code = $o['code'] ?? strtoupper(substr(md5(uniqid('', true)), 0, 12));
        DB::table('bc_bookings')->insert($o + [
            'code' => $code, 'vendor_id' => $vendor ?? $this->vendor->id, 'object_model' => 'tour', 'object_id' => $this->tour, 'first_name' => 'Ann', 'last_name' => 'Ray',
            'email' => 'ann@example.com', 'phone' => '+263770000000', 'total' => 500, 'total_before_fees' => 500, 'paid' => 0, 'total_guests' => 2, 'status' => 'unpaid',
            'start_date' => now()->addDays(20)->toDateString() . ' 09:00:00', 'end_date' => now()->addDays(20)->toDateString() . ' 17:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $code;
    }

    public function test_overview_says_what_where_who_and_how_much(): void
    {
        $code = $this->booking(['paid' => 200]);
        $r = $this->apiGet("/bookings/{$code}/overview")->assertOk()->json('data');

        $this->assertSame('Gorge swing', $r['service']['title']);
        $this->assertSame('Ann Ray', $r['customer']['name']);
        $this->assertEquals(300, $r['money']['balance']);
        $this->assertEquals(500, $r['money']['total']);
        $this->assertSame(['confirmed', 'cancelled'], $r['next_statuses']);
        $this->assertSame(['travellers' => 0, 'documents' => 0, 'timeline' => 0, 'addons' => 0], $r['counts']);
    }

    public function test_the_list_searches_by_code_and_filters_and_sorts_without_changing_its_shape(): void
    {
        $a = $this->booking(['code' => 'ALPHA0001', 'first_name' => 'Ann', 'total' => 100, 'start_date' => now()->addDays(30)->toDateString() . ' 09:00:00']);
        $b = $this->booking(['code' => 'BRAVO0002', 'first_name' => 'Bob', 'last_name' => 'Moyo', 'total' => 900, 'status' => 'confirmed', 'start_date' => now()->addDays(5)->toDateString() . ' 09:00:00']);
        $this->booking(['code' => 'OTHER0003'], $this->other->id);

        $codes = fn (array $q) => array_column($this->apiGet('/bookings', $q)->assertOk()->json('data.data'), 'code');
        $this->assertSame(['BRAVO0002', 'ALPHA0001'], $codes([]));
        $this->assertSame(['BRAVO0002'], $codes(['q' => 'bravo']));
        $this->assertSame(['BRAVO0002'], $codes(['q' => 'moyo bob']));
        $this->assertSame(['BRAVO0002'], $codes(['status' => 'confirmed']));
        $this->assertSame(['BRAVO0002', 'ALPHA0001'], $codes(['sort' => 'amount']));
        $this->assertSame(['BRAVO0002', 'ALPHA0001'], $codes(['sort' => 'trip']));
        $this->assertSame(['ALPHA0001'], $codes(['trip_from' => now()->addDays(20)->toDateString()]));
        $this->assertSame(['BRAVO0002'], $codes(['trip_to' => now()->addDays(10)->toDateString()]));
        $this->assertSame(2, $this->apiGet('/bookings')->json('data.total'), "the other vendor's booking is not counted");
    }

    public function test_status_goes_through_the_portal_flow_with_timeline_loyalty_and_transitions(): void
    {
        $code = $this->booking(['status' => 'paid', 'paid' => 500]);
        $this->api('PATCH', "/bookings/{$code}/status", ['status' => 'confirmed', 'reason' => 'Bank transfer seen'])->assertOk()->assertJsonPath('data.status', 'confirmed')->assertJsonPath('message', 'Booking status updated.');
        $this->assertSame('Bank transfer seen', $this->apiGet("/bookings/{$code}/timeline")->json('data.0.body'));

        $this->api('PATCH', "/bookings/{$code}/status", ['status' => 'confirmed'])->assertStatus(422)->assertJsonPath('error.code', 'invalid_transition');
        $this->api('PATCH', "/bookings/{$code}/status", ['status' => 'nonsense'])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $this->api('PATCH', "/bookings/{$code}/status", ['status' => 'completed'])->assertOk();
        $m = $this->apiGet('/loyalty/members', ['q' => 'ann@example.com'])->json('data.0');
        $this->assertSame(50, $m['points'], 'completing a paid trip earns points, which the old endpoint never did');
    }

    public function test_cancelling_tells_the_waitlist(): void
    {
        Mail::fake();
        $day = now()->addDays(20)->toDateString();
        $full = $this->booking(['total_guests' => 10, 'status' => 'confirmed']);
        $this->api('POST', '/waitlist', ['customer_name' => 'Cy Dube', 'customer_email' => 'cy@example.com', 'tour_id' => $this->tour, 'party_size' => 2, 'preferred_date' => $day])->assertCreated();

        $this->api('PATCH', "/bookings/{$full}/status", ['status' => 'cancelled'], $this->liveKey)->assertOk();

        $this->assertSame('notified', $this->apiGet('/waitlist', [], $this->liveKey)->json('data.0.status'));
    }

    public function test_notes_are_kept_and_a_note_is_always_internal(): void
    {
        $code = $this->booking();
        $n = $this->api('POST', "/bookings/{$code}/timeline", ['channel' => 'note', 'direction' => 'out', 'body' => 'Asked for pickup'])->assertCreated()->json('data');
        $this->assertSame('internal', $n['direction']);
        $c = $this->api('POST', "/bookings/{$code}/timeline", ['channel' => 'call', 'body' => 'Called back'])->assertCreated()->json('data');
        $this->assertSame('out', $c['direction']);
        $this->api('POST', "/bookings/{$code}/timeline", ['channel' => 'fax', 'body' => 'x'])->assertStatus(422);
        $this->assertSame(2, $this->apiGet("/bookings/{$code}/timeline")->json('meta.total'));
    }

    public function test_travellers_with_expected_and_filled_counts(): void
    {
        $code = $this->booking(['total_guests' => 3]);
        $lead = $this->api('POST', "/bookings/{$code}/travellers", ['name' => 'Ann Ray', 'is_lead' => true, 'date_of_birth' => '1990-06-15'])->assertCreated()->json('data');
        $this->api('POST', "/bookings/{$code}/travellers", ['name' => 'Tom Ray', 'dietary' => 'Vegetarian'])->assertCreated();

        $r = $this->apiGet("/bookings/{$code}/travellers")->assertOk();
        $this->assertSame(['expected' => 3, 'filled' => 2], $r->json('meta'));
        $this->assertSame('Ann Ray', $r->json('data.0.name'), 'the lead comes first');

        $this->api('PUT', "/bookings/{$code}/travellers/{$lead['id']}", ['nationality' => 'Zimbabwean'])->assertOk()->assertJsonPath('data.nationality', 'Zimbabwean')->assertJsonPath('data.name', 'Ann Ray');
        $this->api('POST', "/bookings/{$code}/travellers", ['name' => 'X', 'date_of_birth' => now()->addDay()->toDateString()])->assertStatus(422);
        $this->api('DELETE', "/bookings/{$code}/travellers/{$lead['id']}")->assertNoContent();
    }

    public function test_money_schedule_payments_status_following_and_guards(): void
    {
        $code = $this->booking();
        $plan = $this->api('PUT', "/bookings/{$code}/payment-plan", ['mode' => 'deposit', 'percent' => 40, 'balance_days' => 14])->assertOk()->json('data');
        $this->assertSame([200.0, 300.0], array_map('floatval', array_column($plan['plan'], 'amount')) === [200.0, 300.0] ? [200.0, 300.0] : array_column($plan['plan'], 'amount'));
        $this->assertCount(2, $plan['plan']);

        $p = $this->api('POST', "/bookings/{$code}/payments", ['amount' => 200, 'method' => 'bank', 'reference' => 'TXN-1'])->assertCreated()->json('data');
        $this->assertSame('partial_payment', $p['booking']['status']);
        $this->assertEquals(300, $p['booking']['balance']);
        $view = $this->apiGet("/bookings/{$code}/payments")->json('data');
        $this->assertSame('paid', $view['plan'][0]['status']);
        $this->assertSame('pending', $view['plan'][1]['status']);
        $this->assertSame('TXN-1', $view['ledger'][0]['reference']);

        $this->api('POST', "/bookings/{$code}/payments", ['amount' => 900, 'method' => 'cash'])->assertStatus(422)->assertJsonPath('error.code', 'exceeds_balance');
        $this->api('POST', "/bookings/{$code}/payments", ['amount' => 0, 'method' => 'cash'])->assertStatus(422);
        $this->api('POST', "/bookings/{$code}/payments", ['amount' => 5, 'method' => 'barter'])->assertStatus(422);

        $done = $this->api('POST', "/bookings/{$code}/payments", ['amount' => 300, 'method' => 'bank'])->assertCreated()->json('data');
        $this->assertSame('paid', $done['booking']['status']);
        $this->assertEquals(0, $done['booking']['balance']);

        $this->api('POST', "/bookings/{$code}/refunds", ['amount' => 900, 'method' => 'bank'])->assertStatus(422)->assertJsonPath('error.code', 'refund_not_allowed');
        $r = $this->api('POST', "/bookings/{$code}/refunds", ['amount' => 500, 'method' => 'bank', 'cancel' => true])->assertCreated()->json('data');
        $this->assertSame('cancelled', $r['booking']['status']);
        $this->api('DELETE', "/bookings/{$code}/payment-plan")->assertNoContent();
    }

    public function test_a_retried_payment_with_the_same_idempotency_key_is_recorded_once(): void
    {
        $code = $this->booking();
        $h = ['Idempotency-Key' => 'pay-once-1'];

        $first = $this->api('POST', "/bookings/{$code}/payments", ['amount' => 100, 'method' => 'bank'], null, $h)->assertCreated();
        $again = $this->api('POST', "/bookings/{$code}/payments", ['amount' => 100, 'method' => 'bank'], null, $h);

        $this->assertSame($first->json('data.id'), $again->json('data.id'));
        $this->assertCount(1, $this->apiGet("/bookings/{$code}/payments")->json('data.ledger'));
        $this->assertEquals(100, $this->apiGet("/bookings/{$code}/overview")->json('data.money.paid'));
    }

    public function test_documents_are_links_that_must_be_https(): void
    {
        $code = $this->booking();
        $d = $this->api('POST', "/bookings/{$code}/documents", ['name' => 'Voucher', 'url' => 'https://example.com/v.pdf'])->assertCreated()->json('data');
        $this->assertSame('https://example.com/v.pdf', $d['url']);
        $this->api('POST', "/bookings/{$code}/documents", ['name' => 'Bad', 'url' => 'http://example.com/v.pdf'])->assertStatus(422);
        $this->api('POST', "/bookings/{$code}/documents", ['name' => 'Bad', 'url' => 'javascript:alert(1)'])->assertStatus(422);
        $this->assertCount(1, $this->apiGet("/bookings/{$code}/documents")->json('data'));
        $this->api('DELETE', "/bookings/{$code}/documents/{$d['id']}")->assertNoContent();
    }

    public function test_trip_brief_and_guest_form_link(): void
    {
        Mail::fake();
        $code = $this->booking();
        $this->api('POST', "/bookings/{$code}/documents", ['name' => 'Voucher', 'url' => 'https://example.com/v.pdf'])->assertCreated();

        $this->api('POST', "/bookings/{$code}/trip-brief", ['note' => 'See you at 8.30'], $this->liveKey)->assertOk()->assertJsonPath('data.sent_to', 'ann@example.com')->assertJsonPath('data.documents_linked', 1);
        $this->assertSame('Trip brief sent', $this->apiGet("/bookings/{$code}/timeline")->json('data.0.subject'));

        $noMail = $this->booking(['email' => 'nope']);
        $this->api('POST', "/bookings/{$noMail}/trip-brief", [], $this->liveKey)->assertStatus(422)->assertJsonPath('error.code', 'no_email');

        $u1 = $this->apiGet("/bookings/{$code}/guest-form")->json('data.url');
        $this->assertMatchesRegularExpression('#/guest-form/[A-Za-z0-9]{40}$#', $u1);
        $this->assertSame($u1, $this->apiGet("/bookings/{$code}/guest-form")->json('data.url'), 'stable until regenerated');
        $this->assertNotSame($u1, $this->api('POST', "/bookings/{$code}/guest-form")->json('data.url'));
    }

    public function test_addons_change_the_total_and_can_be_taken_off(): void
    {
        $code = $this->booking();
        $up = DB::table('bc_vendor_upsells')->insertGetId(['vendor_id' => $this->vendor->id, 'name' => 'Photos', 'price' => 50, 'price_type' => 'per_person', 'status' => 'publish', 'is_global' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $a = $this->api('POST', "/bookings/{$code}/addons", ['upsell_id' => $up, 'qty' => 1])->assertCreated()->json('data');
        $this->assertEquals(100, $a['total'], 'per person: 2 guests x 50');
        $this->assertEquals(600, $a['booking']['total']);
        $this->assertEquals(100, $this->apiGet("/bookings/{$code}/overview")->json('data.money.addons_total'));

        $this->api('DELETE', "/bookings/{$code}/addons/{$a['id']}")->assertNoContent();
        $this->assertEquals(500, $this->apiGet("/bookings/{$code}/overview")->json('data.money.total'));

        $foreign = DB::table('bc_vendor_upsells')->insertGetId(['vendor_id' => $this->other->id, 'name' => 'Theirs', 'price' => 1, 'price_type' => 'fixed', 'status' => 'publish', 'is_global' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->api('POST', "/bookings/{$code}/addons", ['upsell_id' => $foreign])->assertNotFound();
    }

    public function test_invoice_from_booking_is_made_once_and_check_in_moves_through_its_states(): void
    {
        $code = $this->booking(['paid' => 200, 'status' => 'partial_payment']);
        $first = $this->api('POST', "/bookings/{$code}/invoice")->assertCreated()->json('data');
        $this->assertSame('part_paid', $first['status']);
        $this->assertEquals(200, $first['amount_paid']);
        $again = $this->api('POST', "/bookings/{$code}/invoice")->assertOk()->json('data');
        $this->assertSame($first['id'], $again['id']);
        $this->assertFalse($again['created']);

        $this->assertSame('expected', $this->apiGet("/bookings/{$code}/check-in")->json('data.status'));
        $this->api('POST', "/bookings/{$code}/check-in", ['guests_present' => 2])->assertOk()->assertJsonPath('data.status', 'checked_in')->assertJsonPath('data.guests_present', 2);
        $this->api('POST', "/bookings/{$code}/check-out")->assertOk()->assertJsonPath('data.status', 'checked_out');
        $this->api('POST', "/bookings/{$code}/no-show")->assertOk()->assertJsonPath('data.status', 'no_show');
        $this->assertSame('no_show', $this->apiGet("/bookings/{$code}/overview")->json('data.check_in.status'));
    }

    public function test_another_vendor_can_reach_none_of_it_and_publishable_keys_only_read(): void
    {
        $code = $this->booking();
        foreach (['GET' => ['overview', 'timeline', 'travellers', 'payments', 'documents', 'guest-form', 'addons', 'check-in'], 'POST' => ['timeline' => ['channel' => 'note', 'body' => 'x'], 'payments' => ['amount' => 1, 'method' => 'cash'], 'check-in' => [], 'invoice' => []]] as $method => $paths) {
            foreach ($paths as $k => $v) {
                $path = is_int($k) ? $v : $k;
                $this->api($method, "/bookings/{$code}/{$path}", is_int($k) ? [] : $v, $this->otherKey)->assertNotFound();
            }
        }
        $this->api('PATCH', "/bookings/{$code}/status", ['status' => 'cancelled'], $this->otherKey)->assertNotFound();

        $this->apiGet("/bookings/{$code}/overview", [], $this->pk)->assertOk();
        $this->api('POST', "/bookings/{$code}/payments", ['amount' => 1, 'method' => 'cash'], $this->pk)->assertForbidden()->assertJsonPath('error.code', 'read_only_key');
        $this->assertEquals(0, $this->apiGet("/bookings/{$code}/overview")->json('data.money.paid'));
    }
}
