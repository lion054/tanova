<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Models\VendorWaitlist;

/**
 * Phase 2 — Waitlist for sold-out / unavailable services.
 * VendorWaitlist uses BelongsToVendor (auto-scoped + auto-stamped).
 */
class WaitlistController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorWaitlist::orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('vendor.waitlist.index', [
            'rows'       => $query->paginate(20),
            'statuses'   => $this->statuses(),
            'filters'    => ['status' => $request->input('status')],
            'page_title' => __('Waitlist'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'  => ['required', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'party_size'     => ['nullable', 'integer', 'min:1'],
            'preferred_date' => ['nullable', 'date'],
            'object_model'   => ['nullable', 'string', 'max:191'],
            'object_id'      => ['nullable', 'integer'],
            'notes'          => ['nullable', 'string'],
        ]);

        $data['party_size'] = (int) ($data['party_size'] ?? 1);
        $data['status']     = VendorWaitlist::STATUS_WAITING;

        VendorWaitlist::create($data);

        return redirect()->route('vendor.waitlist.index')
            ->with('success', __('Added to waitlist.'));
    }

    public function update(Request $request, VendorWaitlist $waitlist)
    {
        $data = $request->validate([
            'status' => ['required', 'in:waiting,notified,converted,cancelled'],
        ]);

        if ($data['status'] === VendorWaitlist::STATUS_NOTIFIED) {
            $data['notified_at'] = now();
        }

        $waitlist->update($data);

        return back()->with('success', __('Waitlist entry updated.'));
    }

    public function destroy(VendorWaitlist $waitlist)
    {
        $waitlist->delete();

        return back()->with('success', __('Waitlist entry removed.'));
    }

    private function statuses(): array
    {
        return [
            VendorWaitlist::STATUS_WAITING   => __('Waiting'),
            VendorWaitlist::STATUS_NOTIFIED  => __('Notified'),
            VendorWaitlist::STATUS_CONVERTED => __('Converted'),
            VendorWaitlist::STATUS_CANCELLED => __('Cancelled'),
        ];
    }
}
