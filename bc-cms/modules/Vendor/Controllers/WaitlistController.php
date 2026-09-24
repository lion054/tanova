<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\VendorWaitlist;
use Modules\Vendor\Services\WaitlistNotifier;

/**
 * Phase 2 — Waitlist for sold-out / unavailable services.
 * VendorWaitlist uses BelongsToVendor (auto-scoped + auto-stamped).
 */
class WaitlistController extends Controller
{
    public function __construct(private WaitlistNotifier $notifier) {}

    public function index(Request $request)
    {
        $this->notifier->expirePast((int) resolve_current_vendor_id());

        $base = app(\Modules\Vendor\Services\WaitlistBoard::class)->query(['q' => $request->query('s'), 'status' => $request->query('status'), 'tour' => $request->query('tour'), 'from' => ListQuery::date($request->query('from')), 'to' => ListQuery::date($request->query('to')), 'sort' => $request->query('sort', 'queue')]);
        $all = VendorWaitlist::count();

        $tourOpts = Tour::whereIn('id', VendorWaitlist::whereNotNull('object_id')->distinct()->pluck('object_id'))->orderBy('title')->pluck('title', 'id')->all();
        $bar = FilterBar::make($request)->search('s', __('Search name, e-mail or phone'))
            ->select('status', __('Status'), $this->statuses(), __('Any status'));
        if (count($tourOpts) > 1) {
            $bar->select('tour', __('Experience'), array_map('strval', $tourOpts), __('Any experience'));
        }
        $bar->dates('from', 'to', __('Day'));

        $bar->sort(['queue' => __('Queue order'), 'newest' => __('Newest first'), 'day' => __('Day, soonest'), 'name' => __('Name A to Z'), 'party' => __('Biggest party')], 'queue')->perPage()->noun(__('guests'));
        $fb = $bar->total((clone $base)->reorder()->count(), $all)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request))->withQueryString();

        $tours = Tour::whereIn('id', $rows->pluck('object_id')->filter()->unique())->pluck('title', 'id');
        $free = [];
        foreach ($rows as $row) {
            $free[$row->id] = $this->notifier->freeFor($row);
        }

        return view('vendor.waitlist.index', [
            'rows'       => $rows,
            'tours'      => $tours,
            'free'       => $free,
            'tourList'   => Tour::where('status', 'publish')->orderBy('title')->get(['id', 'title']),
            'statuses'   => $this->statuses(),
            'fb'         => $fb,
            'openCount'  => VendorWaitlist::where('status', VendorWaitlist::STATUS_WAITING)->count(),
            'page_title' => __('Waitlist'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'  => ['required', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'party_size'     => ['nullable', 'integer', 'min:1', 'max:200'],
            'preferred_date' => ['nullable', 'date'],
            'object_id'      => ['nullable', 'integer'],
            'notes'          => ['nullable', 'string'],
        ]);
        if (empty($data['customer_email']) && empty($data['customer_phone'])) {
            return back()->withInput()->with('error', __('Add an e-mail or a phone number so the guest can be told.'));
        }

        // Only this vendor's own tours can be waited for.
        if (!empty($data['object_id']) && !Tour::forVendor(resolve_current_vendor_id())->whereKey($data['object_id'])->exists()) {
            return back()->withInput()->with('error', __('That tour is not one of yours.'));
        }

        $data['party_size'] = (int) ($data['party_size'] ?? 1);
        $data['status'] = VendorWaitlist::STATUS_WAITING;
        $data['source'] = 'vendor';
        $data['object_model'] = !empty($data['object_id']) ? 'tour' : null;

        VendorWaitlist::create($data);

        return redirect()->route('vendor.waitlist.index')->with('success', __('Added to waitlist.'));
    }

    /** Tell one guest that room has opened (optionally with the vendor's own words). */
    public function notify(Request $request, VendorWaitlist $waitlist)
    {
        $data = $request->validate(['message' => ['nullable', 'string', 'max:3000']]);
        $result = $this->notifier->notify($waitlist, $data['message'] ?? null, auth()->id());

        if (($result['status'] ?? '') !== 'sent') {
            $why = ($result['error'] ?? '') === 'no_email' ? __('This guest has no e-mail address.') : __('The message could not be sent (:why). Connect the channel under Integrations or contact the guest yourself.', ['why' => $result['error'] ?? $result['status']]);

            return back()->with('error', $why);
        }

        return back()->with('success', __(':name has been told.', ['name' => $waitlist->customer_name]));
    }

    /** Tell everyone whose whole party now fits, in the order they joined. */
    public function notifyAll()
    {
        $n = $this->notifier->notifyOpenings(null, null, (int) resolve_current_vendor_id());

        return back()->with($n ? 'success' : 'error', $n ? trans_choice(':n guest has been told.|:n guests have been told.', $n, ['n' => $n]) : __('Nobody on the list fits into the seats that are free right now.'));
    }

    public function update(Request $request, VendorWaitlist $waitlist)
    {
        $data = $request->validate([
            'status' => ['required', 'in:waiting,notified,converted,cancelled,expired'],
        ]);
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
            VendorWaitlist::STATUS_NOTIFIED  => __('Told'),
            VendorWaitlist::STATUS_CONVERTED => __('Booked'),
            VendorWaitlist::STATUS_CANCELLED => __('Cancelled'),
            VendorWaitlist::STATUS_EXPIRED   => __('Expired'),
        ];
    }
}
