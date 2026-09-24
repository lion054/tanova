<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Jobs\SendCampaignJob;
use Modules\Vendor\Models\VendorCampaign;

/**
 * Phase 3 — Bulk email campaigns to the vendor's past customers.
 * Recipients are resolved from the vendor's own bookings at send time, so a vendor
 * can never email another vendor's customers. VendorCampaign uses BelongsToVendor.
 */
class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $base = VendorCampaign::query();
        $everything = (clone $base)->count();
        ListQuery::search($base, $request->query('s'), ['subject']);
        $states = ['draft' => __('Draft'), 'sending' => __('Sending'), 'sent' => __('Sent')];
        if (isset($states[(string) $request->query('status')])) { $base->where('status', $request->query('status')); }
        ListQuery::sort($base, $request->query('sort'), ['newest' => ['id', 'desc'], 'oldest' => ['id', 'asc'], 'reach' => ['sent_count', 'desc']], 'newest');
        $fb = FilterBar::make($request)->search('s', __('Search subject'))->select('status', __('Status'), $states, __('Any status'))
            ->sort(['newest' => __('Newest first'), 'oldest' => __('Oldest first'), 'reach' => __('Most sent')], 'newest')->perPage()->noun(__('campaigns'))->total((clone $base)->reorder()->count(), $everything)->toArray();
        $rows = $base->paginate(ListQuery::perPage($request))->withQueryString();

        return view('vendor.campaigns.index', [
            'rows'          => $rows,
            'fb'            => $fb,
            'audienceCount' => app(\Modules\Vendor\Services\CampaignAudience::class)->emails((int) resolve_current_vendor_id(), 'all_customers')->count(),
            'page_title'    => __('Email Campaigns'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'  => ['required', 'string', 'max:191'],
            'body'     => ['required', 'string'],
            'audience' => ['required', 'in:all_customers,completed,upcoming'],
        ]);

        $data['status'] = VendorCampaign::STATUS_DRAFT;
        VendorCampaign::create($data);

        return back()->with('success', __('Campaign saved as draft.'));
    }

    public function send(VendorCampaign $campaign)
    {
        if ($campaign->status === VendorCampaign::STATUS_SENT) {
            return back()->with('error', __('Campaign already sent.'));
        }

        // Mark in-flight and hand off to a queued job so the request returns fast
        // and large recipient lists don't time out.
        $campaign->update(['status' => VendorCampaign::STATUS_SENDING]);
        SendCampaignJob::dispatchAfterResponse($campaign->id);

        return back()->with('success', __('Campaign queued for sending.'));
    }

    public function destroy(VendorCampaign $campaign)
    {
        $campaign->delete();

        return back()->with('success', __('Campaign deleted.'));
    }
}
