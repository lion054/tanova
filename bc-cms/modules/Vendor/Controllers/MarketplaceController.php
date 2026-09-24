<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\MarketplaceListing;

/**
 * Phase 5 — vendor self-service control of which of their experiences appear on
 * the public Tanova marketplace. Listings + tours are both owned by this vendor
 * (author_id / BelongsToVendor), so a vendor can only toggle their own services.
 */
class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = resolve_current_vendor_id();

        $base = Tour::where('author_id', $vendorId)->where('status', 'publish');
        $everything = (clone $base)->count();
        ListQuery::search($base, $request->query('s'), ['title'], 'id');
        $on = MarketplaceListing::where('object_model', 'tour')->where('visible', true)->pluck('object_id')->all();
        $state = (string) $request->query('state', '');
        if ($state === 'on') { $base->whereIn('id', $on ?: [0]); } elseif ($state === 'off') { $base->whereNotIn('id', $on); }
        ListQuery::sort($base, $request->query('sort'), ['newest' => ['id', 'desc'], 'title' => ['title', 'asc'], 'price_asc' => ['price', 'asc'], 'price_desc' => ['price', 'desc']], 'newest');
        $fb = FilterBar::make($request)->search('s', __('Search experiences'))->select('state', __('On MCP'), ['on' => __('On MCP'), 'off' => __('Not on MCP')], __('Any'))
            ->sort(['newest' => __('Newest first'), 'title' => __('Name A to Z'), 'price_asc' => __('Price, low to high'), 'price_desc' => __('Price, high to low')], 'newest')
            ->perPage([30, 60, 100])->noun(__('experiences'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $tours = $base->paginate(ListQuery::perPage($request, [30, 60, 100]))->withQueryString();

        // Which of this vendor's tours are currently visible on the marketplace.
        $visible = MarketplaceListing::where('object_model', 'tour')
            ->whereIn('object_id', $tours->pluck('id'))
            ->where('visible', true)
            ->pluck('object_id')->flip();

        return view('vendor.marketplace.index', [
            'tours'      => $tours,
            'fb'         => $fb,
            'visible'    => $visible,
            'page_title' => __('Tanova Marketplace'),
        ]);
    }

    public function toggle(Request $request, $tourId)
    {
        $vendorId = resolve_current_vendor_id();

        // Confirm the tour belongs to this vendor before listing it.
        $tour = Tour::where('author_id', $vendorId)->findOrFail($tourId);

        $listing = MarketplaceListing::firstOrNew([
            'object_model' => 'tour',
            'object_id'    => $tour->id,
        ]);
        $listing->vendor_id = $vendorId;
        $listing->visible   = ! ($listing->exists ? $listing->visible : false);
        $listing->save();

        return back()->with('success', $listing->visible
            ? __('Deployed to MCP — AI assistants can now discover and book this experience.')
            : __('Removed from MCP — this experience is no longer visible to AI assistants.'));
    }
}
