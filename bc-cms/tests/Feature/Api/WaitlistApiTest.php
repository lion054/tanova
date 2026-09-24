<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\ApiTestCase;

class WaitlistApiTest extends ApiTestCase
{
    private int $tour;
    private string $day;

    protected function setUp(): void
    {
        parent::setUp();
        $this->day = now()->addDays(10)->toDateString();
        $this->tour = DB::table('bc_tours')->insertGetId(['title' => 'Gorge swing', 'slug' => 'gs', 'author_id' => $this->vendor->id, 'status' => 'publish', 'max_people' => 10, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function fill(int $guests): int
    {
        return DB::table('bc_bookings')->insertGetId(['code' => uniqid(), 'vendor_id' => $this->vendor->id, 'object_model' => 'tour', 'object_id' => $this->tour, 'total_guests' => $guests, 'status' => 'confirmed', 'email' => 'x@y.com', 'start_date' => $this->day . ' 09:00:00', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function add(string $name, int $party = 2, array $more = []): array
    {
        return $this->api('POST', '/waitlist', ['customer_name' => $name, 'customer_email' => strtolower(explode(' ', $name)[0]) . '@example.com', 'tour_id' => $this->tour, 'party_size' => $party, 'preferred_date' => $this->day] + $more)->assertCreated()->json('data');
    }

    public function test_add_list_search_filter_sort_and_remove(): void
    {
        $a = $this->add('Ann Ray', 2);
        $b = $this->add('Bob Moyo', 4);
        $this->api('PATCH', "/waitlist/{$b['id']}", ['status' => 'cancelled'])->assertOk()->assertJsonPath('data.status', 'cancelled');

        $all = $this->apiGet('/waitlist')->assertOk();
        $this->assertSame([$a['id'], $b['id']], array_column($all->json('data'), 'id'), 'waiting first');
        $this->assertSame(2, $all->json('meta.total'));
        $this->assertSame([$a['id']], array_column($this->apiGet('/waitlist', ['status' => 'waiting'])->json('data'), 'id'));
        $this->assertSame([$b['id']], array_column($this->apiGet('/waitlist', ['q' => 'moyo bob'])->json('data'), 'id'));
        $this->assertSame([$b['id'], $a['id']], array_column($this->apiGet('/waitlist', ['sort' => 'party'])->json('data'), 'id'));
        $this->assertCount(2, $this->apiGet('/waitlist', ['tour_id' => $this->tour, 'from' => $this->day, 'to' => $this->day])->json('data'));
        $this->assertCount(0, $this->apiGet('/waitlist', ['from' => now()->addDays(60)->toDateString()])->json('data'));

        $this->api('DELETE', "/waitlist/{$a['id']}")->assertNoContent();
        $this->apiGet("/waitlist/{$a['id']}")->assertNotFound();
    }

    public function test_free_seats_and_who_fits_are_worked_out_live(): void
    {
        $this->fill(8);
        $e = $this->add('Ann Ray', 2);
        $big = $this->add('Bob Moyo', 3);

        $rows = collect($this->apiGet('/waitlist')->json('data'))->keyBy('id');
        $this->assertSame(2, $rows[$e['id']]['seats_free']);
        $this->assertTrue($rows[$e['id']]['fits']);
        $this->assertFalse($rows[$big['id']]['fits']);
        $this->assertSame('Gorge swing', $rows[$e['id']]['tour']['title']);
    }

    public function test_notifying_tells_only_whoever_fits_and_only_marks_told_when_it_was_sent(): void
    {
        Mail::fake();
        $booking = $this->fill(10);
        $ann = $this->add('Ann Ray', 2);
        $bob = $this->add('Bob Moyo', 2);
        $this->assertSame(0, $this->api('POST', '/waitlist/notify-openings', ['tour_id' => $this->tour, 'date' => $this->day], $this->liveKey)->assertOk()->json('data.told'));

        DB::table('bc_bookings')->where('id', $booking)->update(['status' => 'cancelled']);   // ten seats free
        $this->assertSame(2, $this->api('POST', '/waitlist/notify-openings', ['tour_id' => $this->tour], $this->liveKey)->json('data.told'));
        $this->assertSame('notified', $this->apiGet("/waitlist/{$ann['id']}", [], $this->liveKey)->json('data.status'));

        // A guest with no e-mail cannot be told: they stay waiting and the answer says why.
        $nobody = $this->api('POST', '/waitlist', ['customer_name' => 'Di Ncube', 'customer_phone' => '+263 77 1', 'tour_id' => $this->tour, 'preferred_date' => $this->day])->json('data');
        $r = $this->api('POST', "/waitlist/{$nobody['id']}/notify", [], $this->liveKey)->assertOk()->json('data');
        $this->assertFalse($r['sent']);
        $this->assertSame('waiting', $r['entry']['status']);
    }

    public function test_bad_input_and_another_vendors_tour(): void
    {
        $this->api('POST', '/waitlist', ['customer_name' => 'No Contact', 'tour_id' => $this->tour])->assertStatus(422)->assertJsonPath('error.code', 'contact_required');
        $this->api('POST', '/waitlist', [])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $theirTour = DB::table('bc_tours')->insertGetId(['title' => 'Theirs', 'slug' => 't', 'author_id' => $this->other->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        $this->api('POST', '/waitlist', ['customer_name' => 'Ann', 'customer_email' => 'a@x.com', 'tour_id' => $theirTour])->assertNotFound();
        $e = $this->add('Ann Ray');
        $this->api('PATCH', "/waitlist/{$e['id']}", ['status' => 'nonsense'])->assertStatus(422);
    }

    public function test_isolation_between_vendors_and_scopes(): void
    {
        $e = $this->add('Ann Ray');
        $this->assertSame(0, $this->apiGet('/waitlist', [], $this->otherKey)->json('meta.total'));
        $this->apiGet("/waitlist/{$e['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('DELETE', "/waitlist/{$e['id']}", [], $this->otherKey)->assertNotFound();
        $this->api('POST', "/waitlist/{$e['id']}/notify", [], $this->otherKey)->assertNotFound();
        $this->api('POST', '/waitlist', ['customer_name' => 'X', 'customer_email' => 'x@x.com'], $this->pk)->assertForbidden();
        $this->assertSame(1, $this->apiGet('/waitlist')->json('meta.total'));
    }
}
