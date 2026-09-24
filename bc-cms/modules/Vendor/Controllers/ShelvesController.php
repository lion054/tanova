<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\VendorShelfPin;
use Modules\Vendor\Services\Shelves;

/** Trending and Bestsellers: what the numbers say, and the vendor's own picks on top. */
class ShelvesController extends Controller
{
    public function index(Shelves $shelves)
    {
        $vendor = resolve_current_vendor_id();

        return view('vendor.shelves.index', [
            'trending'    => $shelves->shelf('trending', $vendor, 10),
            'bestsellers' => $shelves->shelf('bestseller', $vendor, 10),
            'tours'       => Tour::forVendor($vendor)->where('status', 'publish')->orderBy('title')->get(['id', 'title']),
            'pinned'      => VendorShelfPin::get()->groupBy('shelf')->map(fn ($g) => $g->pluck('object_id')->all()),
            'page_title'  => __('Trending & Bestsellers'),
        ]);
    }

    public function pin(Request $request, Shelves $shelves)
    {
        $data = $request->validate([
            'shelf'   => ['required', 'in:' . implode(',', array_keys(VendorShelfPin::SHELVES))],
            'tour_id' => ['required', 'integer'],
            'on'      => ['required', 'in:0,1'],
        ]);
        $vendor = resolve_current_vendor_id();
        if (!Tour::forVendor($vendor)->whereKey($data['tour_id'])->exists()) {
            abort(404);
        }
        $shelves->pin($data['shelf'], $vendor, (int) $data['tour_id'], $data['on'] === '1');

        return back()->with('success', $data['on'] === '1' ? __('Pinned to the top.') : __('Unpinned.'));
    }
}
