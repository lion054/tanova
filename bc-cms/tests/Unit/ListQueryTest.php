<?php

namespace Tests\Unit;

use App\Support\FilterBar;
use App\Support\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Search, sort, page size and the filter bar's description of what is switched on. */
class ListQueryTest extends TestCase
{
    public function test_words_are_cleaned_and_limited(): void
    {
        $this->assertSame([], ListQuery::words('   '));
        $this->assertSame(['vic', 'falls'], ListQuery::words("  vic \t falls  "));
        $this->assertCount(ListQuery::MAX_WORDS, ListQuery::words('a b c d e f g h i j'));
        $this->assertSame(ListQuery::MAX_TERM, mb_strlen(ListQuery::words(str_repeat('x', 500))[0]));
    }

    public function test_wildcards_typed_by_the_user_are_searched_for_not_obeyed(): void
    {
        $this->assertSame('%50\\%%', ListQuery::like('50%'));
        $this->assertSame('%a\\_b%', ListQuery::like('a_b'));
        $this->assertSame('%a\\\\b%', ListQuery::like('a\\b'));
    }

    public function test_every_word_must_match_some_column(): void
    {
        $q = ListQuery::search(DB::table('t'), 'vic falls', ['title', 'address']);

        $this->assertSame('select * from `t` where (`title` like ? or `address` like ?) and (`title` like ? or `address` like ?)', $q->toSql());
        $this->assertSame(['%vic%', '%vic%', '%falls%', '%falls%'], $q->getBindings());
    }

    public function test_a_number_also_matches_the_id_and_nothing_typed_changes_nothing(): void
    {
        $q = ListQuery::search(DB::table('t'), '42', ['title'], 't.id');
        $this->assertStringContainsString('or `t`.`id` = ?', $q->toSql());
        $this->assertSame(['%42%', 42], $q->getBindings());

        $this->assertSame('select * from `t`', ListQuery::search(DB::table('t'), '   ', ['title'])->toSql());
    }

    public function test_an_injection_attempt_is_only_ever_a_bound_value(): void
    {
        $q = ListQuery::search(DB::table('t'), "'; DROP TABLE t;--", ['title']);

        $this->assertStringNotContainsString('DROP', $q->toSql());
        $this->assertContains("%';%", $q->getBindings());
    }

    public function test_sort_only_accepts_declared_choices_and_falls_back(): void
    {
        $map = ['newest' => ['t.id', 'desc'], 'title' => ['t.title', 'asc']];

        $q = DB::table('t');
        $this->assertSame('title', ListQuery::sort($q, 'title', $map, 'newest'));
        $this->assertSame('select * from `t` order by `t`.`title` asc, `t`.`id` desc', $q->toSql());

        foreach (['hacked', '', null, 'title; drop table t', 'price'] as $bad) {
            $q = DB::table('t');
            $this->assertSame('newest', ListQuery::sort($q, $bad, $map, 'newest'));
            $this->assertSame('select * from `t` order by `t`.`id` desc', $q->toSql());
        }
    }

    public function test_page_size_only_accepts_the_offered_sizes(): void
    {
        $this->assertSame(50, ListQuery::perPage(Request::create('/x?per_page=50')));
        $this->assertSame(20, ListQuery::perPage(Request::create('/x?per_page=7')));
        $this->assertSame(20, ListQuery::perPage(Request::create('/x?per_page=100000')));
        $this->assertSame(30, ListQuery::perPage(Request::create('/x'), [30, 60]));
    }

    public function test_dates_must_be_real_dates(): void
    {
        $this->assertSame('2026-10-05', ListQuery::date('2026-10-05'));
        foreach (['', null, '05/10/2026', '2026-13-45', "2026-10-05'; --", 'tomorrow'] as $bad) {
            $this->assertSame('', ListQuery::date($bad), (string) $bad);
        }
    }

    private function bar(string $qs): array
    {
        return FilterBar::make(Request::create('/user/tour' . $qs))
            ->search('s', 'Search')
            ->select('status', 'Status', ['publish' => 'Published', 'draft' => 'Hidden'])
            ->select('partners', 'Partners', ['1' => 'Partners only'])
            ->dates('from', 'to', 'Trip')
            ->sort(['newest' => 'Newest', 'title' => 'Name'], 'newest')->perPage()->noun('tours')->keep(['tab'])
            ->total(3, 10)->toArray();
    }

    public function test_the_bar_lists_what_is_switched_on_and_how_to_remove_each(): void
    {
        $fb = $this->bar('?s=vic+falls&status=draft&from=2026-10-01&to=2026-10-31&sort=title&per_page=50&tab=x&page=3');

        $this->assertSame(3, $fb['active']);                      // search, status, dates: sort and page size are not filters
        $labels = array_column($fb['chips'], 'label');
        $this->assertSame(['“vic falls”', 'Status: Hidden', 'Trip: 2026-10-01 → 2026-10-31'], $labels);

        parse_str(parse_url($fb['chips'][1]['remove'], PHP_URL_QUERY), $q);   // removing the status chip
        $this->assertArrayNotHasKey('status', $q);
        $this->assertArrayNotHasKey('page', $q);
        $this->assertSame(['s' => 'vic falls', 'from' => '2026-10-01', 'to' => '2026-10-31', 'sort' => 'title', 'per_page' => '50', 'tab' => 'x'], $q);

        parse_str(parse_url($fb['clear'], PHP_URL_QUERY), $c);     // clearing keeps sort, page size and the page's own tab
        $this->assertSame(['sort' => 'title', 'per_page' => '50', 'tab' => 'x'], $c);
        $this->assertSame(['tab' => 'x'], $fb['kept']);
    }

    public function test_nonsense_in_the_address_is_ignored_not_shown_as_a_filter(): void
    {
        $fb = $this->bar('?status=bogus&sort=hack&per_page=7&from=nope&s=');

        $this->assertSame(0, $fb['active']);
        $this->assertSame([], $fb['chips']);
        $this->assertSame('newest', $fb['sort']['value']);
        $this->assertSame(20, $fb['per_page_value']);
        $this->assertSame('', $fb['selects'][0]['value']);
    }

    public function test_a_one_choice_filter_shows_just_the_choice_and_an_empty_list_is_not_a_no_match(): void
    {
        $this->assertSame('Partners only', $this->bar('?partners=1')['chips'][0]['label']);

        $empty = FilterBar::make(Request::create('/x?s=abc'))->search('s', 'Search')->total(0, 0)->toArray();
        $this->assertSame(0, $empty['active']);   // nothing at all to narrow: the page says "empty", not "nothing matches"
    }
}
