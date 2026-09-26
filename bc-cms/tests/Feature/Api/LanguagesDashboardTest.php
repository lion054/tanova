<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Settings;
use Modules\Vendor\Models\VendorTeam;
use Modules\Vendor\Services\ServiceTranslations as ST;
use Tests\ApiTestCase;

/** The language dashboard: a company writes its own services in other languages, and only its own. */
class LanguagesDashboardTest extends ApiTestCase
{
    private int $tour;
    private int $theirs;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::store('site_enable_multi_lang', 1);
        Settings::store('site_locale', 'en');
        DB::table('core_languages')->where('locale', 'fr')->delete();
        DB::table('core_languages')->insert(['locale' => 'fr', 'name' => 'French', 'flag' => 'fr', 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        Cache::forget('locale_active_0');
        Cache::forget('locale_active_1');
        $mk = fn (int $vendor, string $title) => DB::table('bc_tours')->insertGetId(['title' => $title, 'slug' => 's' . uniqid(), 'author_id' => $vendor, 'status' => 'publish', 'short_desc' => 'A short line', 'content' => '<p>First paragraph.</p><p>Second &amp; last.</p>', 'created_at' => now(), 'updated_at' => now()]);
        $this->tour = $mk($this->vendor->id, 'Sunset cruise');
        $this->theirs = $mk($this->other->id, 'Their secret tour');
    }

    private function row(int $tour, string $locale = 'fr')
    {
        return DB::table('bc_tour_translations')->where('origin_id', $tour)->where('locale', $locale)->first();
    }

    private function save(array $t, array $extra = [])
    {
        return $this->actingAs($this->vendor)->post('/vendor/languages', ['lang' => 'fr', 'type' => 'tour', 't' => $t] + $extra);
    }

    public function test_the_dashboard_lists_the_companys_services_and_the_other_languages_only(): void
    {
        $page = $this->actingAs($this->vendor)->get('/vendor/languages?type=tour')->assertOk()->getContent();
        $this->assertStringContainsString('Sunset cruise', $page);
        $this->assertStringContainsString('First paragraph.', $page, 'the description as text');
        $this->assertStringNotContainsString('<p>First paragraph.</p>', $page);
        $this->assertStringNotContainsString('Their secret tour', $page, 'never another company\'s service');
        $this->assertStringContainsString('French', $page);
        $this->assertStringContainsString('To translate', $page);
        $this->assertStringNotContainsString('lang=en', $page, 'the default language is not a target');
    }

    public function test_saving_writes_the_translation_and_leaves_the_original_alone(): void
    {
        $this->save([$this->tour => ['title' => 'Croisière au coucher du soleil', 'content' => "Premier paragraphe.\n\nDeuxième & dernier."]])->assertRedirect();

        $row = $this->row($this->tour);
        $this->assertSame('Croisière au coucher du soleil', $row->title);
        $this->assertSame("<p>Premier paragraphe.</p>\n<p>Deuxième &amp; dernier.</p>", $row->content);
        $this->assertSame('A short line', $row->short_desc, 'what was not translated falls back to the original text, never blank');
        $orig = DB::table('bc_tours')->find($this->tour);
        $this->assertSame('Sunset cruise', $orig->title, 'the default language is untouched');
        $this->assertSame('<p>First paragraph.</p><p>Second &amp; last.</p>', $orig->content);

        $page = $this->actingAs($this->vendor)->get('/vendor/languages?type=tour&lang=fr')->assertOk()->getContent();
        $this->assertStringContainsString('Croisière au coucher du soleil', $page);
        $this->assertStringContainsString('In progress', $page, 'the short description is still to do');
        $this->save([$this->tour => ['short_desc' => 'Une courte phrase']]);
        $this->assertSame('Une courte phrase', $this->row($this->tour)->short_desc);
        $this->assertStringContainsString('Translated', $this->actingAs($this->vendor)->get('/vendor/languages?type=tour&lang=fr')->getContent());
        $this->assertSame(1, ST::summary(ST::typesFor($this->vendor), $this->vendor->id, 'fr')['tour']['done']);
    }

    public function test_writing_nothing_creates_nothing_and_emptying_a_field_puts_the_original_back(): void
    {
        $this->save([$this->tour => ['title' => '', 'content' => '', 'short_desc' => '']]);
        $this->assertNull($this->row($this->tour), 'no row for an untouched service');

        $this->save([$this->tour => ['title' => 'Croisière']]);
        $this->assertSame('Croisière', $this->row($this->tour)->title);
        $this->save([$this->tour => ['title' => '']]);
        $this->assertSame('Sunset cruise', $this->row($this->tour)->title, 'falls back to the original');
    }

    public function test_a_company_cannot_translate_anothers_service_or_use_a_language_that_is_not_on(): void
    {
        $this->save([$this->theirs => ['title' => 'Hacked']]);
        $this->assertNull($this->row($this->theirs));
        $this->assertSame('Their secret tour', DB::table('bc_tours')->find($this->theirs)->title);

        $this->actingAs($this->vendor)->post('/vendor/languages', ['lang' => 'en', 't' => [$this->tour => ['title' => 'x']], 'type' => 'tour'])->assertSessionHas('danger');
        $this->actingAs($this->vendor)->post('/vendor/languages', ['lang' => 'zz', 't' => [$this->tour => ['title' => 'x']], 'type' => 'tour'])->assertSessionHas('danger');
        $this->assertSame('Sunset cruise', DB::table('bc_tours')->find($this->tour)->title, 'the default language text is never overwritten from here');
        $this->assertNull($this->row($this->tour, 'zz'));
    }

    public function test_with_languages_switched_off_nothing_can_be_written(): void
    {
        Settings::store('site_enable_multi_lang', 0);
        $this->save([$this->tour => ['title' => 'Croisière']]);
        $this->assertSame('Sunset cruise', DB::table('bc_tours')->find($this->tour)->title, 'without several languages a translation would be the service itself');
        $this->assertNull($this->row($this->tour));
        $this->actingAs($this->vendor)->get('/vendor/languages')->assertOk()->assertSee('switched off');
    }

    public function test_only_the_kinds_of_service_the_company_operates_are_offered(): void
    {
        DB::table('vendor_company_os')->where('vendor_id', $this->vendor->id)->where('os_key', '!=', 'stay')->delete();   // a lodge on Hana
        $page = $this->actingAs($this->vendor)->get('/vendor/languages')->assertOk()->getContent();
        $this->assertStringContainsString('<b>Hotels</b>', $page);
        $this->assertStringNotContainsString('<b>Tours</b>', $page);
        $this->save([$this->tour => ['title' => 'Croisière']]);
        $this->assertNull($this->row($this->tour), 'tours are not part of what it operates');
    }

    public function test_staff_need_the_catalogue_to_open_it(): void
    {
        $mk = function (array $modules) {
            $u = $this->makeVendor('Staff ' . uniqid());
            DB::table('users')->where('id', $u->id)->update(['role_id' => (int) DB::table('core_roles')->where('code', 'vendor_staff')->value('id')]);
            $t = new VendorTeam();
            $t->vendor_id = $this->vendor->id; $t->member_id = $u->id; $t->status = VendorTeam::STATUS_PUBLISH; $t->permissions = $modules; $t->save();

            return \App\User::find($u->id);
        };
        $this->actingAs($mk(['catalog']))->get('/vendor/languages?type=tour')->assertOk()->assertSee('Sunset cruise');
        $this->actingAs($mk(['finance']))->get('/vendor/languages')->assertRedirect('/user/dashboard');
    }

    private function richTour(): int
    {
        return DB::table('bc_tours')->insertGetId(['title' => 'Chobe day', 'slug' => 'c' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish',
            'faqs' => json_encode([['title' => 'Is lunch included?', 'content' => 'Yes, a packed lunch.'], ['title' => 'Kids?', 'content' => 'From 6 years.']]),
            'include' => json_encode([['title' => 'Guide'], ['title' => 'Park fees']]),
            'exclude' => json_encode([['title' => 'Tips']]),
            'itinerary' => json_encode([['title' => 'Day 1: Chobe', 'desc' => 'River and game', 'content' => "Breakfast\nBoat cruise", 'image_id' => 440]]),
            'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_lists_are_translated_word_by_word_and_keep_their_structure(): void
    {
        $t = $this->richTour();
        $page = $this->actingAs($this->vendor)->get('/vendor/languages?type=tour&lang=fr')->assertOk()->getContent();
        foreach (['Is lunch included?', 'Guide', 'Day 1: Chobe', 'Questions and answers', 'What is included', 'Itinerary'] as $seen) {
            $this->assertStringContainsString($seen, $page);
        }

        $this->save([$t => [
            'faqs' => ['0|title' => 'Le déjeuner est-il inclus ?', '0|content' => 'Oui, un panier-repas.', '1|title' => '', '1|content' => ''],
            'include' => ['0|title' => 'Guide (fr)'],
            'itinerary' => ['0|title' => 'Jour 1 : Chobe', '0|content' => "Petit-déjeuner\nCroisière"],
            'bogus' => ['0|title' => 'x'],
        ]])->assertRedirect();

        $row = $this->row($t);
        $faqs = json_decode($row->faqs, true);
        $this->assertSame('Le déjeuner est-il inclus ?', $faqs[0]['title']);
        $this->assertSame('Oui, un panier-repas.', $faqs[0]['content']);
        $this->assertSame('Kids?', $faqs[1]['title'], 'what was left empty falls back to the original');
        $this->assertSame([['title' => 'Guide (fr)'], ['title' => 'Park fees']], json_decode($row->include, true));
        $it = json_decode($row->itinerary, true);
        $this->assertSame('Jour 1 : Chobe', $it[0]['title']);
        $this->assertSame('River and game', $it[0]['desc'], 'untouched words stay as the original');
        $this->assertSame(440, $it[0]['image_id'], 'pictures and settings always come from the original');
        $this->assertSame([['title' => 'Tips']], json_decode($row->exclude, true), 'a list never touched still has its words');

        $orig = DB::table('bc_tours')->find($t);
        $this->assertSame('Is lunch included?', json_decode($orig->faqs, true)[0]['title'], 'the original language is untouched');

        // Progress counts words, and the page shows what was written.
        $progress = ST::progress(\Modules\Tour\Models\Tour::find($t), \Modules\Tour\Models\TourTranslation::where('origin_id', $t)->where('locale', 'fr')->first(), ['faqs', 'include', 'exclude', 'itinerary']);
        $this->assertSame('partial', $progress['state']);
        $this->assertEquals(0.5, $progress['fields']['faqs'] > 0.49 && $progress['fields']['faqs'] < 0.51 ? 0.5 : $progress['fields']['faqs']);
        $this->assertEquals(0.0, $progress['fields']['exclude']);
        $page = $this->actingAs($this->vendor)->get('/vendor/languages?type=tour&lang=fr')->getContent();
        $this->assertStringContainsString('Le déjeuner est-il inclus ?', $page);
        $this->assertStringContainsString('Jour 1 : Chobe', $page);
    }

    public function test_a_list_follows_the_original_when_the_original_changes(): void
    {
        $t = $this->richTour();
        $this->save([$t => ['include' => ['0|title' => 'Guide (fr)', '1|title' => 'Frais (fr)']]]);
        DB::table('bc_tours')->where('id', $t)->update(['include' => json_encode([['title' => 'Guide'], ['title' => 'Park fees'], ['title' => 'Snacks']])]);
        $this->save([$t => ['include' => ['0|title' => 'Guide (fr)', '1|title' => 'Frais (fr)', '2|title' => 'Encas']]]);
        $this->assertSame([['title' => 'Guide (fr)'], ['title' => 'Frais (fr)'], ['title' => 'Encas']], json_decode($this->row($t)->include, true), 'a new item in the original gets a place to translate');
    }

    public function test_hotel_policies_and_boat_specs_are_lists_too(): void
    {
        $h = DB::table('bc_hotels')->insertGetId(['title' => 'Falls Lodge', 'slug' => 'h' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish', 'policy' => json_encode([['title' => 'Check-in', 'content' => 'From 14:00']]), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->vendor)->post('/vendor/languages', ['lang' => 'fr', 'type' => 'hotel', 't' => [$h => ['policy' => ['0|title' => 'Arrivée', '0|content' => 'À partir de 14 h']]]])->assertRedirect();
        $row = DB::table('bc_hotel_translations')->where('origin_id', $h)->where('locale', 'fr')->first();
        $this->assertSame([['title' => 'Arrivée', 'content' => 'À partir de 14 h']], json_decode($row->policy, true));
        $this->assertSame([['title' => 'Check-in', 'content' => 'From 14:00']], json_decode(DB::table('bc_hotels')->find($h)->policy, true));
        $this->assertContains('policy', ST::typesFor($this->vendor)['hotel']['fields']);
        $this->assertContains('specs', ST::typesFor($this->vendor)['boat']['fields']);
    }

    public function test_text_and_html_convert_both_ways(): void
    {
        $this->assertSame("One.\n\nTwo & three\nnext line", ST::toText('<p>One.</p><p>Two &amp; three<br>next line</p>'));
        $this->assertSame("<p>One.</p>\n<p>Two &lt;b&gt; three<br>\nnext</p>", ST::toHtml("One.\n\nTwo <b> three\nnext"), 'typed markup is shown as typed, never run');
        $this->assertSame('', ST::toHtml("  \n "));
    }

    public function test_the_menu_has_languages_on_its_own_not_under_money(): void
    {
        $html = $this->actingAs($this->vendor)->get('/user/dashboard')->assertOk()->getContent();
        preg_match('#data-group="overview">(.*?)data-group="bookings"#s', $html, $home);
        preg_match('#data-group="finance">(.*?)data-group="settings"#s', $html, $money);
        $this->assertStringContainsString('vendor/languages', $home[1] ?? '', 'with Dashboard, Today and Team');
        $this->assertStringNotContainsString('vendor/languages', $money[1] ?? '', 'not inside Money');
    }

    public function test_a_language_can_be_added_from_the_catalogue_and_only_from_it(): void
    {
        DB::table('core_languages')->where('locale', 'sw')->delete();
        config(['services.deepseek.key' => null]);   // adding a language writes its interface words with AI afterwards: never call the real service from a test
        $this->actingAs($this->vendor)->post('/vendor/languages/add', ['locale' => 'sw'])->assertRedirect();
        $this->assertSame('publish', DB::table('core_languages')->where('locale', 'sw')->value('status'));
        $this->assertSame('Swahili', DB::table('core_languages')->where('locale', 'sw')->value('name'));
        $this->actingAs($this->vendor)->get('/vendor/languages?lang=sw')->assertOk()->assertSee('Swahili');
        $this->actingAs($this->vendor)->post('/vendor/languages/add', ['locale' => 'zz'])->assertSessionHas('danger');
        $this->assertSame(0, DB::table('core_languages')->where('locale', 'zz')->count());
        $this->actingAs($this->vendor)->post('/vendor/languages/add', ['locale' => 'sw']);
        $this->assertSame(1, DB::table('core_languages')->where('locale', 'sw')->count(), 'adding twice does not duplicate');
        // The service can now be translated into it.
        $this->actingAs($this->vendor)->post('/vendor/languages', ['lang' => 'sw', 'type' => 'tour', 't' => [$this->tour => ['title' => 'Safari ya jioni']]]);
        $this->assertSame('Safari ya jioni', $this->row($this->tour, 'sw')->title);
    }

    private function fakeAi(): void
    {
        config(['services.deepseek.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.deepseek.com/*' => function ($req) {
            $in = json_decode($req['messages'][1]['content'], true);

            return \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => json_encode(array_map(fn ($t) => 'FR:' . $t, $in), JSON_UNESCAPED_UNICODE)]]]]);
        }]);
    }

    private function ai(string $what, array $body)
    {
        return $this->actingAs($this->vendor)->postJson('/vendor/languages/ai/' . $what, $body + ['lang' => 'fr']);
    }

    public function test_ai_fills_only_what_is_missing_and_never_replaces_words_a_person_wrote(): void
    {
        $this->fakeAi();
        $t = $this->richTour();
        $this->save([$t => ['title' => 'Journée à Chobe (écrit à la main)']]);

        $queue = $this->ai('queue', ['scope' => 'tour'])->assertOk()->json();
        $ids = array_column($queue['items'], 'id');
        $this->assertContains($t, $ids);
        $this->assertContains($this->tour, $ids);
        $this->assertNotContains($this->theirs, $ids, 'never another company\'s service');

        $r = $this->ai('run', ['items' => [['type' => 'tour', 'id' => $t], ['type' => 'tour', 'id' => $this->theirs]]])->assertOk()->json();
        $this->assertSame(1, $r['done'], 'only the company\'s own service was worked on');
        $this->assertGreaterThan(5, $r['written']);

        $row = $this->row($t);
        $this->assertSame('Journée à Chobe (écrit à la main)', $row->title, 'a hand-written translation is kept');
        $this->assertSame('FR:Is lunch included?', json_decode($row->faqs, true)[0]['title']);
        $this->assertSame('FR:Guide', json_decode($row->include, true)[0]['title']);
        $this->assertSame(440, json_decode($row->itinerary, true)[0]['image_id']);
        $this->assertNull($this->row($this->theirs), 'nothing written for someone else\'s service');
        $this->assertSame('Chobe day', DB::table('bc_tours')->find($t)->title, 'the original is untouched');

        // Done now: it leaves the queue, and running again writes nothing new.
        $again = $this->ai('queue', ['scope' => 'tour'])->json();
        $this->assertNotContains($t, array_column($again['items'], 'id'));
        $this->assertSame(0, $this->ai('run', ['items' => [['type' => 'tour', 'id' => $t]]])->json('written'));
    }

    public function test_ai_errors_are_reported_and_nothing_is_half_written(): void
    {
        config(['services.deepseek.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.deepseek.com/*' => \Illuminate\Support\Facades\Http::response(['error' => 'overloaded'], 529)]);
        $r = $this->ai('run', ['items' => [['type' => 'tour', 'id' => $this->tour]]]);
        $r->assertStatus(502);
        $this->assertNotEmpty($r->json('error'));
        $this->assertNull($this->row($this->tour));
    }

    public function test_ai_needs_a_key_and_a_real_language(): void
    {
        config(['services.deepseek.key' => null]);
        $this->ai('queue', ['scope' => 'all'])->assertStatus(422);
        $this->fakeAi();
        $this->ai('queue', ['scope' => 'all', 'lang' => 'en'])->assertStatus(422);   // the default language is not a target
        $this->ai('run', ['lang' => 'zz', 'items' => [['type' => 'tour', 'id' => $this->tour]]])->assertStatus(422);
    }

    public function test_a_name_that_reads_the_same_in_the_language_still_counts_as_translated(): void
    {
        $t = DB::table('bc_tours')->insertGetId(['title' => 'Victoria Falls Hotel', 'slug' => 'n' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        $state = fn () => ST::summary(['tour' => ST::typesFor($this->vendor)['tour']], $this->vendor->id, 'fr')['tour'];
        $before = $state();

        // Typing the name as it is is a decision, not a gap.
        $this->save([$t => ['title' => 'Victoria Falls Hotel']]);
        $after = $state();
        $this->assertSame($before['done'] + 1, $after['done']);
        $this->assertSame($before['missing'] - 1, $after['missing']);
        $this->assertSame(1, DB::table('vendor_translation_marks')->where(['kind' => 'tour', 'origin_id' => $t, 'locale' => 'fr', 'piece' => 'title'])->count());
        $this->assertNull($this->row($t), 'no row is needed: the guest already reads the same words');

        // Emptying the box takes it back to still-to-do.
        $this->save([$t => ['title' => '']]);
        $this->assertSame($before['done'], $state()['done']);
        $this->assertSame(0, DB::table('vendor_translation_marks')->where(['origin_id' => $t, 'piece' => 'title'])->count());
    }

    public function test_ai_leaving_a_name_as_it_is_marks_it_done_and_it_is_not_asked_again(): void
    {
        config(['services.deepseek.key' => 'test-key']);
        $calls = 0;
        \Illuminate\Support\Facades\Http::fake(['api.deepseek.com/*' => function ($req) use (&$calls) {
            $calls++;
            $in = json_decode($req['messages'][1]['content'], true);

            return \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['content' => json_encode($in, JSON_UNESCAPED_UNICODE)]]]]);   // returns every text unchanged
        }]);
        $t = DB::table('bc_tours')->insertGetId(['title' => 'Elephant Hills', 'slug' => 'e' . uniqid(), 'author_id' => $this->vendor->id, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(1, $this->ai('run', ['items' => [['type' => 'tour', 'id' => $t]]])->json('written'));
        $this->assertSame(1, DB::table('vendor_translation_marks')->where(['origin_id' => $t, 'piece' => 'title'])->count());
        $queue = $this->ai('queue', ['scope' => 'tour'])->json();
        $this->assertNotContains($t, array_column($queue['items'], 'id'), 'it is finished, not queued forever');
        $before = $calls;
        $this->ai('run', ['items' => [['type' => 'tour', 'id' => $t]]]);
        $this->assertSame($before, $calls, 'nothing left to send, so no call');
    }
}
