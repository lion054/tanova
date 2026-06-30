<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        return view('vendor.campaigns.index', [
            'rows'          => VendorCampaign::orderByDesc('id')->paginate(20),
            'audienceCount' => $this->recipients('all_customers')->count(),
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
        SendCampaignJob::dispatch($campaign->id);

        return back()->with('success', __('Campaign queued for sending.'));
    }

    public function destroy(VendorCampaign $campaign)
    {
        $campaign->delete();

        return back()->with('success', __('Campaign deleted.'));
    }

    /** Distinct customer emails drawn from the current vendor's bookings. */
    private function recipients(string $audience)
    {
        $query = Booking::where('vendor_id', resolve_current_vendor_id())
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($audience === 'completed') {
            $query->where('status', Booking::COMPLETED);
        } elseif ($audience === 'upcoming') {
            $query->whereDate('start_date', '>=', now()->toDateString());
        }

        return $query->pluck('email')->unique()->values();
    }
}
