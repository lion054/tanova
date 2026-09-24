<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Modules\Vendor\Models\VendorOccasion;
use Modules\Vendor\Services\OccasionSync;

/**
 * Phase 3 — Customer occasions (birthday / anniversary) CRUD.
 * VendorOccasion uses BelongsToVendor (auto-scoped + auto-stamped).
 */
class OccasionController extends Controller
{
    public function index(Request $request)
    {
        $everything = VendorOccasion::count();
        $types = []; foreach (VendorOccasion::TYPES as $t) { $types[$t] = __(ucfirst($t)); }
        $months = []; for ($m = 1; $m <= 12; $m++) { $months[(string) $m] = now()->startOfYear()->month($m)->translatedFormat('F'); }
        $windows = ['7' => __('Next 7 days'), '30' => __('Next 30 days'), '90' => __('Next 90 days')];
        $all = app(\Modules\Vendor\Services\OccasionBoard::class)->all([
            'q' => $request->query('s'), 'type' => $request->query('type'), 'month' => $request->query('month'),
            'mail' => $request->query('mail'), 'within' => $request->query('within'), 'sort' => $request->query('sort', 'soonest'),
        ]);
        $fb = FilterBar::make($request)->search('s', __('Search name or e-mail'))->select('within', __('Coming up'), $windows, __('Any time'))
            ->select('type', __('Type'), $types, __('Any type'))->select('month', __('Month'), $months, __('Any month'))
            ->select('mail', __('E-mail'), ['yes' => __('Has e-mail'), 'no' => __('No e-mail (not sent)')], __('Any'))
            ->sort(['soonest' => __('Soonest first'), 'name' => __('Name A to Z')], 'soonest')->perPage([25, 50, 100])->noun(__('occasions'))->total($all->count(), $everything)->toArray();
        $per = ListQuery::perPage($request, [25, 50, 100]);
        $page = max(1, (int) $request->query('page', 1));
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($all->forPage($page, $per)->values(), $all->count(), $per, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('vendor.occasions.index', [
            'rows'       => $rows,
            'fb'         => $fb,
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

    /** Read birthdays from customers and guest forms into this list. */
    public function import(OccasionSync $sync)
    {
        $n = $sync->run(resolve_current_vendor_id());

        return back()->with('success', $n ? trans_choice(':n birthday added or updated.|:n birthdays added or updated.', $n, ['n' => $n]) : __('Nothing new: every birthday we know of is already here.'));
    }

    public function destroy(VendorOccasion $occasion)
    {
        $occasion->delete();

        return back()->with('success', __('Occasion removed.'));
    }
}
