<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Describes a list's search and filters for the shared filter bar
 * (resources/views/vendor/partials/filterbar.blade.php).
 *
 *   $fb = FilterBar::make($request)->search('s', 'Search tours')
 *       ->select('status', 'Status', ['publish' => 'Published', 'draft' => 'Hidden'])
 *       ->sort(['newest' => 'Newest', 'title' => 'Name A-Z'], 'newest')
 *       ->dates('from', 'to', 'Created')->perPage()->noun('tours');
 *
 * The bar works out which filters are switched on, so it can show them as removable chips
 * and a "clear" link. A sort other than the default and a non-default page size are not
 * "filters": they are kept in the address but do not count as narrowing the list.
 */
class FilterBar
{
    private array $cfg = ['search' => null, 'selects' => [], 'sort' => null, 'dates' => null, 'per_page' => null, 'noun' => 'results', 'total' => null, 'keep' => []];

    private function __construct(private Request $request) {}

    public static function make(Request $request): self
    {
        return new self($request);
    }

    public function search(string $name, string $placeholder): self
    {
        $this->cfg['search'] = ['name' => $name, 'placeholder' => $placeholder];

        return $this;
    }

    /** @param array<string,string> $options value => label (an "all" choice is added) */
    public function select(string $name, string $label, array $options, ?string $allLabel = null): self
    {
        $this->cfg['selects'][] = ['name' => $name, 'label' => $label, 'options' => $options, 'all' => $allLabel ?? $label];

        return $this;
    }

    /** @param array<string,string> $options key => label */
    public function sort(array $options, string $default, string $name = 'sort'): self
    {
        $this->cfg['sort'] = ['name' => $name, 'options' => $options, 'default' => $default];

        return $this;
    }

    public function dates(string $from, string $to, string $label): self
    {
        $this->cfg['dates'] = ['from' => $from, 'to' => $to, 'label' => $label];

        return $this;
    }

    public function perPage(array $sizes = [20, 50, 100]): self
    {
        $this->cfg['per_page'] = ['sizes' => $sizes, 'value' => ListQuery::perPage($this->request, $sizes)];

        return $this;
    }

    /** Query values that belong to the page, not the filters (a chosen tab or tour), carried through every submit. */
    public function keep(array $names): self
    {
        $this->cfg['keep'] = $names;

        return $this;
    }

    public function noun(string $plural): self
    {
        $this->cfg['noun'] = $plural;

        return $this;
    }

    /** How many rows there are with the filters applied, and (optionally) without them. */
    public function total(int $matching, ?int $all = null): self
    {
        $this->cfg['total'] = ['matching' => $matching, 'all' => $all];

        return $this;
    }

    public function toArray(): array
    {
        $r = $this->request;
        $c = $this->cfg;
        $chips = [];

        if ($c['search']) {
            $term = trim(mb_substr((string) $r->query($c['search']['name'], ''), 0, ListQuery::MAX_TERM));
            $c['search']['value'] = $term;
            if ($term !== '') {
                $chips[] = ['key' => [$c['search']['name']], 'label' => '“' . $term . '”'];
            }
        }
        foreach ($c['selects'] as $i => $s) {
            $v = (string) $r->query($s['name'], '');
            $c['selects'][$i]['value'] = isset($s['options'][$v]) ? $v : '';
            if ($c['selects'][$i]['value'] !== '') {
                $chips[] = ['key' => [$s['name']], 'label' => count($s['options']) === 1 ? $s['options'][$v] : $s['label'] . ': ' . $s['options'][$v]];
            }
        }
        if ($c['dates']) {
            $from = ListQuery::date($r->query($c['dates']['from']));
            $to = ListQuery::date($r->query($c['dates']['to']));
            $c['dates']['from_value'] = $from;
            $c['dates']['to_value'] = $to;
            if ($from || $to) {
                $chips[] = ['key' => [$c['dates']['from'], $c['dates']['to']], 'label' => $c['dates']['label'] . ': ' . ($from ?: '…') . ' → ' . ($to ?: '…')];
            }
        }
        if ($c['sort']) {
            $k = (string) $r->query($c['sort']['name'], '');
            $c['sort']['value'] = isset($c['sort']['options'][$k]) ? $k : $c['sort']['default'];
        }

        $keep = array_diff(array_keys($r->query()), ['page']);
        $c['chips'] = array_map(function ($chip) use ($r, $keep) {
            $chip['remove'] = $r->url() . self::qs(array_diff_key($r->query(), array_flip($chip['key']), ['page' => 1]));

            return $chip;
        }, $chips);
        // With nothing in the list at all there is nothing to narrow, so it is "empty", not "no match".
        $c['active'] = ($c['total']['all'] ?? 1) === 0 ? 0 : count($chips);
        $c['clear'] = $r->url() . self::qs(array_intersect_key($r->query(), array_flip(array_filter(array_merge([$c['sort']['name'] ?? null, $c['per_page'] ? 'per_page' : null], $c['keep'])))));
        $c['per_page_value'] = $c['per_page']['value'] ?? null;
        $c['action'] = $r->url();
        $c['kept'] = array_filter(array_intersect_key($r->query(), array_flip($c['keep'])), fn ($v) => is_scalar($v) && $v !== '');

        return $c;
    }

    private static function qs(array $q): string
    {
        return $q ? '?' . http_build_query($q) : '';
    }
}
