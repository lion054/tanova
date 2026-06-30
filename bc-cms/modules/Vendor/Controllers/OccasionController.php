<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Models\VendorOccasion;

/**
 * Phase 3 — Customer occasions (birthday / anniversary) CRUD.
 * VendorOccasion uses BelongsToVendor (auto-scoped + auto-stamped).
 */
class OccasionController extends Controller
{
    public function index(Request $request)
    {
        return view('vendor.occasions.index', [
            'rows'       => VendorOccasion::orderBy('occasion_date')->paginate(20),
            'types'      => VendorOccasion::TYPES,
            'page_title' => __('Occasions'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'  => ['required', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'type'           => ['required', 'in:' . implode(',', VendorOccasion::TYPES)],
            'occasion_date'  => ['required', 'date'],
            'notes'          => ['nullable', 'string'],
        ]);

        VendorOccasion::create($data);

        return back()->with('success', __('Occasion added.'));
    }

    public function destroy(VendorOccasion $occasion)
    {
        $occasion->delete();

        return back()->with('success', __('Occasion removed.'));
    }
}
