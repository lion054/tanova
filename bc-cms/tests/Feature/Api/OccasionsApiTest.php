<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class OccasionsApiTest extends ApiTestCase
{
    public function test_create_list_filter_and_delete(): void
    {
        $soon = now()->addDays(5);
        $far = now()->addDays(200);
        $a = $this->api('POST', '/occasions', ['name' => 'Ann Ray', 'email' => 'ann@x.com', 'type' => 'birthday', 'date' => $soon->copy()->subYears(30)->toDateString()])->assertCreated()->json('data');
        $b = $this->api('POST', '/occasions', ['name' => 'Bob Moyo', 'type' => 'anniversary', 'date' => $far->copy()->subYears(5)->toDateString()])->assertCreated()->json('data');

        $this->assertSame($soon->toDateString(), $a['next_on']);
        $this->assertSame(5, $a['days_until']);
        $this->assertTrue($a['will_be_messaged']);
        $this->assertFalse($b['will_be_messaged']);

        $list = $this->apiGet('/occasions')->assertOk();
        $this->assertSame([$a['id'], $b['id']], array_column($list->json('data'), 'id'));
        $this->assertSame([$a['id']], array_column($this->apiGet('/occasions', ['within' => 30])->json('data'), 'id'));
        $this->assertSame([$b['id']], array_column($this->apiGet('/occasions', ['type' => 'anniversary'])->json('data'), 'id'));
        $this->assertSame([$b['id']], array_column($this->apiGet('/occasions', ['mail' => 'no'])->json('data'), 'id'));
        $this->assertSame([$a['id']], array_column($this->apiGet('/occasions', ['q' => 'ray ann'])->json('data'), 'id'));
        $this->assertSame([$a['id']], array_column($this->apiGet('/occasions', ['month' => $soon->month])->json('data'), 'id'));

        $this->api('DELETE', "/occasions/{$a['id']}")->assertNoContent();
        $this->assertSame(1, $this->apiGet('/occasions')->json('meta.total'));
    }

    public function test_import_reads_birthdays_from_customers_and_is_repeatable(): void
    {
        DB::table('bc_vendor_customers')->insert(['vendor_id' => $this->vendor->id, 'first_name' => 'Ann', 'last_name' => 'Ray', 'email' => 'ann@x.com', 'date_of_birth' => '1990-06-15', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_vendor_customers')->insert(['vendor_id' => $this->other->id, 'first_name' => 'Zed', 'last_name' => 'Other', 'email' => 'z@x.com', 'date_of_birth' => '1980-01-01', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame(1, $this->api('POST', '/occasions/import')->assertOk()->json('data.changed'));
        $this->assertSame(0, $this->api('POST', '/occasions/import')->json('data.changed'));
        $this->assertSame(['Ann Ray'], array_column($this->apiGet('/occasions')->json('data'), 'name'));
        $this->assertSame(0, $this->apiGet('/occasions', [], $this->otherKey)->json('meta.total'), "the other vendor's customer was not read into this vendor's list");
    }

    public function test_validation_isolation_and_read_only_keys(): void
    {
        $this->api('POST', '/occasions', ['name' => 'X', 'type' => 'party', 'date' => 'not a date'])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['type', 'date']]]);
        $id = $this->api('POST', '/occasions', ['name' => 'Mine', 'type' => 'birthday', 'date' => '1990-01-01'])->json('data.id');
        $this->api('DELETE', "/occasions/{$id}", [], $this->otherKey)->assertNotFound();
        $this->apiGet('/occasions', [], $this->pk)->assertOk();
        $this->api('POST', '/occasions', ['name' => 'X', 'type' => 'birthday', 'date' => '1990-01-01'], $this->pk)->assertForbidden();
    }
}
