<?php

namespace Pro\Tanova\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Modules\Hotel\Models\Hotel;
use Modules\Tour\Models\Tour;
use Pro\Tanova\Models\TanovaMeal;
use Pro\Tanova\Models\TanovaRestaurant;

/**
 * Tanova port, phase 2 — unified catalog view.
 *
 * One screen answering "what can an itinerary reference?", across the Tanova-owned
 * catalogs (meals, restaurants) and the bookable service modules (tours, hotels).
 * Read-only: each tab links to the screen that actually owns the records, so there
 * is exactly one place to edit each kind of thing.
 *
 * Tours and Hotels are owned by their vendor through `author_id`, not `vendor_id`,
 * so they are filtered explicitly rather than by the global VendorScope.
 */
class CatalogPortalController extends Controller
{
    public function index(Request $request)
    {
        $tab      = $request->query('tab', 'meals');
        $vendorId = resolve_current_vendor_id();

        $counts = [
            'meals'       => TanovaMeal::count(),
            'restaurants' => TanovaRestaurant::count(),
            'tours'       => $vendorId ? Tour::where('author_id', $vendorId)->count() : 0,
            'hotels'      => $vendorId ? Hotel::where('author_id', $vendorId)->count() : 0,
        ];

        $tab = in_array($tab, ['meals', 'restaurants', 'tours', 'hotels'], true) ? $tab : 'meals';
        $none = fn () => Tour::whereRaw('0 = 1');

        [$base, $cols, $shape] = match ($tab) {
            'restaurants' => [TanovaRestaurant::query(), ['name', 'cuisine', 'location'], fn ($r) => [
                'name' => $r->name, 'detail' => $r->cuisine ?: '—', 'meta' => $r->location ?: '—', 'badge' => $r->is_partner ? __('Partner') : null, 'status' => $r->status]],
            'tours' => [$vendorId ? Tour::where('author_id', $vendorId) : $none(), ['title', 'address'], fn ($t) => [
                'name' => $t->title, 'detail' => $t->duration ? __(':n days', ['n' => $t->duration]) : '—', 'meta' => $t->address ?? '—', 'badge' => null, 'status' => $t->status]],
            'hotels' => [$vendorId ? Hotel::where('author_id', $vendorId) : $none(), ['title', 'address'], fn ($h) => [
                'name' => $h->title, 'detail' => '—', 'meta' => $h->address ?? '—', 'badge' => null, 'status' => $h->status]],
            default => [TanovaMeal::query(), ['name', 'cuisine', 'location'], fn ($m) => [
                'name' => $m->name, 'detail' => __(ucfirst($m->meal_type)), 'meta' => $m->location ?: '—', 'badge' => $m->cuisine, 'status' => $m->status]],
        };
        $nameCol = in_array($tab, ['tours', 'hotels'], true) ? 'title' : 'name';

        $everything = (clone $base)->count();
        ListQuery::search($base, $request->query('s'), $cols);
        $statuses = ['publish' => __('Published'), 'draft' => __('Hidden')];
        if (isset($statuses[(string) $request->query('status')])) { $base->where('status', $request->query('status')); }
        ListQuery::sort($base, $request->query('sort'), ['name' => [$nameCol, 'asc'], 'name_desc' => [$nameCol, 'desc'], 'newest' => ['id', 'desc']], 'name');
        $fb = FilterBar::make($request)->keep(['tab'])->search('s', __('Search this catalog'))->select('status', __('Status'), $statuses, __('Any status'))
            ->sort(['name' => __('Name A to Z'), 'name_desc' => __('Name Z to A'), 'newest' => __('Newest first')], 'name')->perPage([30, 60, 100])->noun(__('records'))
            ->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request, [30, 60, 100]))->withQueryString()->through($shape);

        return view('vendor.catalogs.index', [
            'tab'        => $tab,
            'fb'         => $fb,
            'rows'       => $rows,
            'counts'     => $counts,
            'page_title' => __('Catalogs'),
        ]);
    }
}
