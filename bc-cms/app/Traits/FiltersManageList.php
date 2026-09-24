<?php

namespace App\Traits;

use App\Support\FilterBar;
use App\Support\ListQuery;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;

/**
 * Search, filters, sort and page size for the vendor's "Manage tours / hotels / cars ..." lists.
 * One place, so all eight lists behave the same:
 *
 *   [$list, $fb, $perPage] = $this->manageFilters($request, $baseQuery, ['table' => 'bc_tours', 'noun' => __('tours')]);
 *   'rows' => $list->paginate($perPage)->appends($request->query()), 'fb' => $fb
 *
 * $baseQuery must already be limited to the signed-in vendor's own rows; nothing here widens it.
 * Options offered for location and category come only from that vendor's own rows.
 */
trait FiltersManageList
{
    /**
     * @param array{table:string,noun:string,status?:bool,price?:bool,category?:array{table:string,column?:string}} $o
     * @return array{0:mixed,1:array,2:int}
     */
    protected function manageFilters(Request $request, $query, array $o): array
    {
        $t = $o['table'];
        $own = clone $query;             // everything this vendor has here, for the counts and the option lists
        $all = (clone $own)->count();

        ListQuery::search($query, $request->query('s'), ["$t.title"], "$t.id");

        $bar = FilterBar::make($request)->search('s', __('Search :things by name or number', ['things' => $o['noun']]));

        if (!empty($o['status']) && $this->tableHas($t, 'status')) {
            $opts = ['publish' => __('Published'), 'draft' => __('Hidden'), 'pending' => __('Pending')];
            $bar->select('status', __('Status'), $opts, __('Any status'));
            $st = (string) $request->query('status', '');
            if (isset($opts[$st])) {
                $query->where("$t.status", $st);
            }
        }

        if ($this->tableHas($t, 'location_id')) {
            $ids = (clone $own)->whereNotNull("$t.location_id")->where("$t.location_id", '>', 0)->distinct()->pluck("$t.location_id")->all();
            if (count($ids) > 1) {
                $names = Location::whereIn('id', $ids)->get()->pluck('name', 'id')->filter()->sort()->all();
                $opts = [];
                foreach ($names as $id => $name) {
                    $opts[(string) $id] = (string) $name;
                }
                $bar->select('location', __('Location'), $opts, __('Any location'));
                $loc = (string) $request->query('location', '');
                if (isset($opts[$loc])) {
                    $query->where("$t.location_id", (int) $loc);
                }
            }
        }

        if (!empty($o['category'])) {
            $col = $o['category']['column'] ?? 'category_id';
            $ids = (clone $own)->whereNotNull("$t.$col")->where("$t.$col", '>', 0)->distinct()->pluck("$t.$col")->all();
            if (count($ids) > 1) {
                $opts = [];
                foreach (\DB::table($o['category']['table'])->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id') as $id => $name) {
                    $opts[(string) $id] = (string) $name;
                }
                $bar->select('category', __('Category'), $opts, __('Any category'));
                $cat = (string) $request->query('category', '');
                if (isset($opts[$cat])) {
                    $query->where("$t.$col", (int) $cat);
                }
            }
        }

        $sorts = ['newest' => __('Newest first'), 'oldest' => __('Oldest first'), 'title' => __('Name A to Z'), 'title_desc' => __('Name Z to A'), 'updated' => __('Recently updated')];
        $map = ['newest' => ["$t.id", 'desc'], 'oldest' => ["$t.id", 'asc'], 'title' => ["$t.title", 'asc'], 'title_desc' => ["$t.title", 'desc'], 'updated' => ["$t.updated_at", 'desc']];
        if (!empty($o['price']) && $this->tableHas($t, 'price')) {
            $sorts += ['price_asc' => __('Price, low to high'), 'price_desc' => __('Price, high to low')];
            $map += ['price_asc' => ["$t.price", 'asc'], 'price_desc' => ["$t.price", 'desc']];
        }
        ListQuery::sort($query, $request->query('sort'), $map, 'newest');
        $bar->sort($sorts, 'newest')->perPage()->noun($o['noun']);

        $matching = (clone $query)->reorder()->count();
        $fb = $bar->total($matching, $all)->toArray();

        return [$query, $fb, ListQuery::perPage($request)];
    }

    private function tableHas(string $table, string $column): bool
    {
        static $cache = [];

        return $cache["$table.$column"] ??= \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    }
}
