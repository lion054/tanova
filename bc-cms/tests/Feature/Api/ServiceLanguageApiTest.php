<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Settings;
use Tests\ApiTestCase;

/** The API serves a service in the language the client asks for (?lang= or Accept-Language), falling back to the default, never blank. */
class ServiceLanguageApiTest extends ApiTestCase
{
    private int $tour;
    private int $hotel;
    private int $room;
    private int $loc;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::store('site_enable_multi_lang', 1);
        Settings::store('site_locale', 'en');
        DB::table('core_languages')->whereIn('locale', ['fr', 'sw'])->delete();
        foreach (['fr' => 'French', 'sw' => 'Swahili'] as $c => $n) {
            DB::table('core_languages')->insert(['locale' => $c, 'name' => $n, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        }
        Cache::forget('locale_active_0');
        Cache::forget('locale_active_1');
        $now = ['created_at' => now(), 'updated_at' => now()];
        $this->loc = DB::table('bc_locations')->insertGetId(['name' => 'Victoria Falls', 'slug' => 'vf' . uniqid(), 'status' => 'publish'] + $now);
        $this->tour = DB::table('bc_tours')->insertGetId(['title' => 'Sunset cruise', 'slug' => 't' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish', 'location_id' => $this->loc, 'price' => 50,
            'short_desc' => 'Two hours on the water', 'content' => '<p>Glide along the river.</p>', 'faqs' => json_encode([['title' => 'Kids?', 'content' => 'From 6.']]),
            'include' => json_encode([['title' => 'Snacks']]), 'address' => 'Riverside'] + $now);
        DB::table('bc_tour_translations')->insert(['origin_id' => $this->tour, 'locale' => 'fr', 'title' => 'Croisière au coucher du soleil', 'short_desc' => '', 'content' => '<p>Glissez le long du fleuve.</p>',
            'faqs' => json_encode([['title' => 'Enfants ?', 'content' => 'Dès 6 ans.']]), 'include' => json_encode([['title' => 'Collations']]), 'address' => 'Berge'] + $now);
        $this->hotel = DB::table('bc_hotels')->insertGetId(['title' => 'Falls Lodge', 'slug' => 'h' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish', 'content' => '<p>Quiet.</p>', 'location_id' => $this->loc, 'price' => 90] + $now);
        DB::table('bc_hotel_translations')->insert(['origin_id' => $this->hotel, 'locale' => 'fr', 'title' => 'Pavillon des Chutes', 'content' => '<p>Calme.</p>'] + $now);
        $this->room = DB::table('bc_hotel_rooms')->insertGetId(['title' => 'River room', 'parent_id' => $this->hotel, 'price' => 100, 'status' => 'publish'] + $now);
        DB::table('bc_hotel_room_translations')->insert(['origin_id' => $this->room, 'locale' => 'fr', 'title' => 'Chambre sur le fleuve'] + $now);
    }

    private function fetch(string $path, array $headers = [])
    {
        return $this->api('GET', $path, [], null, $headers);
    }

    public function test_a_service_comes_in_the_asked_for_language_and_falls_back_field_by_field(): void
    {
        $d = $this->fetch("/services/tours/{$this->tour}?lang=fr")->assertOk()->json('data');
        $this->assertSame('Croisière au coucher du soleil', $d['title']);
        $this->assertSame('<p>Glissez le long du fleuve.</p>', $d['content']);
        $this->assertSame('Enfants ?', $d['faqs'][0]['title'], 'lists come translated and still as lists');
        $this->assertSame('Collations', $d['include'][0]['title']);
        $this->assertSame('Two hours on the water', $d['short_desc'], 'a field with no translation keeps the default text, never blank');
        $this->assertSame('fr', $d['language']);
        $this->assertSame(['fr'], $d['available_languages']);
        $this->assertSame(50, (int) $d['price'], 'numbers and settings are untouched');
    }

    public function test_without_a_language_or_with_one_that_is_not_translated_it_is_the_default(): void
    {
        $d = $this->fetch("/services/tours/{$this->tour}")->assertOk()->json('data');
        $this->assertSame('Sunset cruise', $d['title']);
        $this->assertSame('en', $d['language']);
        $this->assertSame(['fr'], $d['available_languages'], 'the client can see French exists');

        $sw = $this->fetch("/services/tours/{$this->tour}?lang=sw")->assertOk()->json('data');
        $this->assertSame('Sunset cruise', $sw['title'], 'Swahili is on but this tour has no Swahili');
        $this->assertSame('en', $sw['language'], 'and it says so');
        $this->assertSame('Sunset cruise', $this->fetch("/services/tours/{$this->tour}?lang=zz")->json('data.title'), 'an unknown language is ignored');
    }

    public function test_accept_language_works_like_the_lang_parameter(): void
    {
        $fr = $this->fetch("/services/tours/{$this->tour}", ['Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.5'])->json('data');
        $this->assertSame('Croisière au coucher du soleil', $fr['title']);
        $this->assertSame('Sunset cruise', $this->fetch("/services/tours/{$this->tour}", ['Accept-Language' => 'en-US,en;q=0.9'])->json('data.title'));
        $this->assertSame('Sunset cruise', $this->fetch("/services/tours/{$this->tour}", ['Accept-Language' => 'de-DE,de;q=0.9'])->json('data.title'));
        $this->assertSame('Croisière au coucher du soleil', $this->fetch("/services/tours/{$this->tour}", ['Accept-Language' => 'de;q=0.9,fr;q=0.8'])->json('data.title'), 'the first language the platform has');
        $this->assertSame('Sunset cruise', $this->fetch("/services/tours/{$this->tour}?lang=en", ['Accept-Language' => 'fr'])->json('data.title'), 'the parameter wins over the header');
    }

    public function test_lang_all_sends_every_language_beside_the_default_text(): void
    {
        $d = $this->fetch("/services/tours/{$this->tour}?lang=all")->assertOk()->json('data');
        $this->assertSame('Sunset cruise', $d['title']);
        $this->assertSame('Croisière au coucher du soleil', $d['translations']['fr']['title']);
        $this->assertSame('Enfants ?', $d['translations']['fr']['faqs'][0]['title']);
        $this->assertArrayNotHasKey('sw', $d['translations']);
        $this->assertArrayNotHasKey('translations', $this->fetch("/services/tours/{$this->tour}?lang=fr")->json('data'), 'only when asked for');
    }

    public function test_lists_and_hotels_with_their_rooms_are_translated_too(): void
    {
        $list = $this->fetch('/services/tours?lang=fr')->assertOk()->json('data.data');
        $this->assertContains('Croisière au coucher du soleil', array_column($list, 'title'));

        $h = $this->fetch("/services/hotels/{$this->hotel}?lang=fr")->assertOk()->json('data');
        $this->assertSame('Pavillon des Chutes', $h['title']);
        $this->assertSame('Chambre sur le fleuve', $h['rooms'][0]['title']);
    }

    public function test_the_unified_listing_and_the_app_catalogue_follow_the_language(): void
    {
        $rows = collect($this->fetch('/services?lang=fr')->assertOk()->json('data'))->keyBy(fn ($r) => $r['type'] . ':' . $r['id']);
        $this->assertSame('Croisière au coucher du soleil', $rows["tour:{$this->tour}"]['title']);
        $this->assertSame('fr', $rows["tour:{$this->tour}"]['language']);
        $this->assertSame(['fr'], $rows["tour:{$this->tour}"]['available_languages']);
        $this->assertSame('Sunset cruise', collect($this->fetch('/services')->json('data'))->firstWhere('id', $this->tour)['title']);

        $cat = $this->fetch("/catalogue?location_id={$this->loc}&lang=fr")->assertOk()->json('data');
        $this->assertSame('fr', $cat['language']);
        $names = array_merge(array_column($cat['activities'] ?? [], 'name'), array_column($cat['packages'] ?? [], 'name'), array_column($cat['stays'] ?? [], 'name'));
        $this->assertContains('Croisière au coucher du soleil', $names);
        $this->assertContains('Pavillon des Chutes', $names);
        $stay = collect($cat['stays'])->firstWhere('source_id', $this->hotel);
        $this->assertSame('Chambre sur le fleuve', $stay['rooms'][0]['name']);
        $this->assertSame('Calme.', $stay['description']);
        $en = $this->fetch("/catalogue?location_id={$this->loc}")->json('data');
        $this->assertSame('en', $en['language']);
        $this->assertContains('Falls Lodge', array_column($en['stays'], 'name'));
    }

    public function test_with_languages_switched_off_the_default_is_always_served(): void
    {
        Settings::store('site_enable_multi_lang', 0);
        $d = $this->fetch("/services/tours/{$this->tour}?lang=fr")->assertOk()->json('data');
        $this->assertSame('Sunset cruise', $d['title']);
        $this->assertSame([], $d['available_languages']);
    }

    public function test_another_companys_translations_never_appear(): void
    {
        $theirs = DB::table('bc_tours')->insertGetId(['title' => 'Their tour', 'slug' => 'x' . uniqid(), 'author_id' => $this->other->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bc_tour_translations')->insert(['origin_id' => $theirs, 'locale' => 'fr', 'title' => 'Leur circuit', 'created_at' => now(), 'updated_at' => now()]);
        $this->fetch("/services/tours/{$theirs}?lang=fr")->assertNotFound();
        $this->assertNotContains('Leur circuit', array_column($this->fetch('/services/tours?lang=fr')->json('data.data'), 'title'));
    }
}
