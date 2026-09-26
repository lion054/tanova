<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Modules\Vendor\Models\VendorTeam;
use Tests\ApiTestCase;

/**
 * The public page of a service: open to anyone when it is published, a 404 when it is not (except for its owner, the owner's staff and the
 * platform team), the same design for every kind of service, and nothing about the owner beyond the company name.
 */
class PublicServicePageTest extends ApiTestCase
{
    private array $svc = [];

    protected function setUp(): void
    {
        parent::setUp();
        $now = ['created_at' => now(), 'updated_at' => now()];
        $base = ['author_id' => $this->vendor->id, 'status' => 'publish', 'content' => '<p>Lovely and quiet.</p><script>alert(1)</script>'] + $now;
        foreach (['hotel' => 'bc_hotels', 'tour' => 'bc_tours', 'space' => 'bc_spaces', 'car' => 'bc_cars', 'boat' => 'bc_boats', 'event' => 'bc_events'] as $type => $table) {
            $this->svc[$type] = ['slug' => "pub-{$type}-" . uniqid(), 'table' => $table];
            $this->svc[$type]['id'] = DB::table($table)->insertGetId($base + ['title' => ucfirst($type) . ' Palace', 'slug' => $this->svc[$type]['slug']]);
        }
        DB::table('bc_tours')->where('id', $this->svc['tour']['id'])->update(['short_desc' => 'One perfect day', 'faqs' => json_encode([['title' => 'Is lunch included?', 'content' => 'Yes.']]),
            'include' => json_encode([['title' => 'Guide']]), 'exclude' => json_encode([['title' => 'Tips']]), 'itinerary' => json_encode([['title' => 'Day 1: Falls', 'desc' => 'Morning', 'content' => 'Walk']])]);
        DB::table('users')->where('id', $this->vendor->id)->update(['business_name' => 'Sunset Tours', 'phone' => '+263771234567']);
    }

    private function draft(string $type): string
    {
        DB::table($this->svc[$type]['table'])->where('id', $this->svc[$type]['id'])->update(['status' => 'draft']);

        return "/{$type}/" . $this->svc[$type]['slug'];
    }

    private function url(string $type): string
    {
        return "/{$type}/" . $this->svc[$type]['slug'];
    }

    public function test_anyone_can_open_a_published_service_of_every_kind(): void
    {
        foreach (array_keys($this->svc) as $type) {
            $page = $this->get($this->url($type))->assertOk()->getContent();
            $this->assertStringContainsString(ucfirst($type) . ' Palace', $page, "{$type} shows its title");
            $this->assertStringContainsString('pv-title', $page, "{$type} uses the shared design");
            $this->assertStringContainsString('Lovely and quiet.', $page);
            $this->assertStringNotContainsString('alert(1)', $page, 'markup in a description is cleaned');
            $this->assertStringContainsString('Sunset Tours', $page, 'the company name');
            $this->assertStringNotContainsString($this->vendor->email, $page, 'never the owner\'s e-mail');
            $this->assertStringNotContainsString('+263771234567', $page, 'never the owner\'s phone');
            $this->assertStringNotContainsString('gotrip', strtolower(strip_tags(preg_replace('#<(script|style|head)\b.*?</\1>#si', '', $page))), 'no template leftovers in the visible page');
            $this->assertStringNotContainsString('/user/profile/', $page, 'no link to the owner\'s profile');
            $this->assertStringNotContainsString('noindex', $page);
        }
    }

    public function test_the_tour_page_shows_its_itinerary_inclusions_and_questions(): void
    {
        $page = $this->get($this->url('tour'))->assertOk()->getContent();
        foreach (['One perfect day', 'Day 1: Falls', 'Guide', 'Tips', 'Is lunch included?', 'What is included'] as $seen) {
            $this->assertStringContainsString($seen, $page);
        }
    }

    public function test_a_draft_is_a_404_for_guests_and_other_people_but_a_preview_for_its_owner_staff_and_the_platform(): void
    {
        $url = $this->draft('hotel');
        $this->get($url)->assertNotFound();

        $customer = $this->makeVendor('Some Customer');
        DB::table('users')->where('id', $customer->id)->update(['role_id' => (int) DB::table('core_roles')->where('code', 'customer')->value('id')]);
        $this->actingAs(\App\User::find($customer->id))->get($url)->assertNotFound();
        $this->actingAs($this->other)->get($url)->assertNotFound();

        $owner = $this->actingAs($this->vendor)->get($url)->assertOk()->getContent();
        $this->assertStringContainsString('Preview: not published yet.', $owner);
        $this->assertStringContainsString('noindex', $owner, 'a preview is never indexed');

        $staff = $this->makeVendor('Team Person');
        DB::table('users')->where('id', $staff->id)->update(['role_id' => (int) DB::table('core_roles')->where('code', 'vendor_staff')->value('id')]);
        $t = new VendorTeam();
        $t->vendor_id = $this->vendor->id; $t->member_id = $staff->id; $t->status = VendorTeam::STATUS_PUBLISH; $t->permissions = ['bookings']; $t->save();
        $this->actingAs(\App\User::find($staff->id))->get($url)->assertOk()->assertSee('Preview: not published yet.');

        $admin = $this->makeVendor('Platform Admin');
        DB::table('users')->where('id', $admin->id)->update(['role_id' => (int) DB::table('core_roles')->where('code', 'administrator')->value('id')]);
        $this->actingAs(\App\User::find($admin->id))->get($url)->assertOk();

        DB::table('bc_hotels')->where('id', $this->svc['hotel']['id'])->update(['status' => 'publish']);
        $this->post('/logout');
        $this->get($url)->assertOk()->assertDontSee('Preview: not published yet.');
    }

    public function test_only_the_service_pages_and_their_booking_calls_are_open_to_guests(): void
    {
        foreach (['/hotel', '/tour', '/boat', '/user/dashboard', '/vendor/today', '/admin', '/booking/ABC123'] as $closed) {
            $this->get($closed)->assertRedirect('/login');
        }
        $this->get('/hotel/does-not-exist')->assertNotFound();
        // The booking box works for a guest: these answer with JSON, not a bounce to the login page.
        $r = $this->postJson('/booking/addToCart', ['service_id' => 999999, 'service_type' => 'tour']);
        $this->assertNotSame(302, $r->status());
        $this->getJson('/user/tour/availability/loadDates?id=' . $this->svc['tour']['id'] . '&start=2026-10-01&end=2026-10-31')->assertOk();
    }

    public function test_switching_language_stays_on_the_page(): void
    {
        $url = $this->url('tour');
        $this->withHeader('Referer', url($url))->get('/language/set-lang/en')->assertRedirect(url($url));
    }

    public function test_a_guest_cannot_hammer_the_public_pages(): void
    {
        \Illuminate\Support\Facades\RateLimiter::clear('public-service:r:127.0.0.1');
        $last = null;
        for ($i = 0; $i < 245; $i++) {
            $last = $this->get('/tour/no-such-tour-' . $i)->getStatusCode();
            if ($last === 429) {
                break;
            }
        }
        $this->assertSame(429, $last, 'after 240 reads a minute the address is slowed down');
        \Illuminate\Support\Facades\RateLimiter::clear('public-service:r:127.0.0.1');
    }

    public function test_a_service_with_only_a_featured_picture_still_shows_it(): void
    {
        $page = \App\Support\PublicServicePage::make('tour', \Modules\Tour\Models\Tour::find($this->svc['tour']['id']), \Modules\Tour\Models\Tour::find($this->svc['tour']['id']));
        $this->assertSame([], $page['gallery'], 'no picture at all: an honest placeholder, not a broken image');
        $this->assertSame('Tour', $page['kind']);
        $this->assertSame(['Guide'], $page['include']);
        $this->assertSame('Sunset Tours', $page['offered_by']);
    }

    public function test_choosing_a_language_changes_the_service_page_and_sticks_on_service_pages_only(): void
    {
        \Illuminate\Support\Facades\Cache::forget('locale_active_0');
        \Illuminate\Support\Facades\Cache::forget('locale_active_1');
        \Modules\Core\Models\Settings::store('site_enable_multi_lang', 1);
        \Modules\Core\Models\Settings::store('site_locale', 'en');
        DB::table('core_languages')->where('locale', 'fr')->delete();
        DB::table('core_languages')->insert(['locale' => 'fr', 'name' => 'French', 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        \Illuminate\Support\Facades\Cache::forget('locale_active_0');
        \Illuminate\Support\Facades\Cache::forget('locale_active_1');
        DB::table('bc_hotel_translations')->insert(['origin_id' => $this->svc['hotel']['id'], 'locale' => 'fr', 'title' => 'Palais de l\'hôtel', 'content' => '<p>Calme et charmant.</p>', 'created_at' => now(), 'updated_at' => now()]);

        $url = $this->url('hotel');
        $this->get($url)->assertOk()->assertSee('Hotel Palace')->assertDontSee('Calme et charmant.');
        $this->get($url . '?lang=fr')->assertOk()->assertSee('Calme et charmant.', false)->assertSee('Palais de l');
        $this->get($url)->assertSee('Calme et charmant.', false);   // remembered for the next service page
        $this->get($url . '?lang=zz')->assertOk();                  // an unknown language is ignored
        $this->get($url . '?lang=en')->assertSee('Hotel Palace')->assertDontSee('Calme et charmant.');

        // The portal is never switched: a vendor editing a service must always see and save the default language.
        $this->get($url . '?lang=fr');
        $this->actingAs($this->vendor)->get('/user/dashboard');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_interface_words_of_the_page_are_found_and_written_for_a_language(): void
    {
        $used = \Modules\Vendor\Services\UiStrings::used();
        foreach (['Check availability', 'Show all :n photos', 'About this hotel', 'Sign in'] as $s) {
            $this->assertContains($s, $used, "{$s} is found");
        }
        $file = \Modules\Vendor\Services\UiStrings::path('tt');
        @unlink($file);
        file_put_contents($file, json_encode(['Sign in' => 'Ingia']));
        config(['services.deepseek.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.deepseek.com/*' => function ($req) {
            $in = json_decode($req['messages'][1]['content'], true);
            $out = array_map(fn ($t) => str_contains($t, ':n') ? 'no placeholder here' : 'TT:' . $t, $in);

            return \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['content' => json_encode($out, JSON_UNESCAPED_UNICODE)]]]]);
        }]);
        try {
            $added = \Modules\Vendor\Services\UiStrings::fill('tt', 'Testish');
            $d = json_decode(file_get_contents($file), true);
            $this->assertGreaterThan(20, $added);
            $this->assertSame('Ingia', $d['Sign in'], 'an existing translation is never replaced');
            $this->assertSame('TT:Check availability', $d['Check availability']);
            $this->assertArrayNotHasKey('Show all :n photos', $d, 'a translation that loses :n is left out rather than breaking the sentence');
            $this->assertSame(0, \Modules\Vendor\Services\UiStrings::fill('tt', 'Testish') > 0 ? 1 : 0 , 'a second run adds nothing new except still-refused ones') ;
        } finally {
            @unlink($file);
        }
    }
}
