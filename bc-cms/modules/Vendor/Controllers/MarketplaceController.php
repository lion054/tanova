<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        $tours = Tour::where('author_id', $vendorId)
            ->where('status', 'publish')
            ->orderByDesc('id')->paginate(30);

        // Which of this vendor's tours are currently visible on the marketplace.
        $visible = MarketplaceListing::where('object_model', 'tour')
            ->whereIn('object_id', $tours->pluck('id'))
            ->where('visible', true)
            ->pluck('object_id')->flip();

        return view('vendor.marketplace.index', [
            'tours'      => $tours,
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
