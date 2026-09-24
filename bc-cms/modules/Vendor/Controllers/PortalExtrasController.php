<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\MarketplaceListing;
use Modules\Vendor\Models\ScheduledMessage;
use Modules\Vendor\Models\VendorPricingTier;

/**
 * Phase 6 — portal extras: Go-Live readiness checklist and the Operations manual.
 */
class PortalExtrasController extends Controller
{
    public function goLive()
    {
        $vendorId = resolve_current_vendor_id();

        $checklist = [
            [
                'label' => __('Publish at least one experience'),
                'done'  => Tour::where('author_id', $vendorId)->where('status', 'publish')->exists(),
                'hint'  => __('Create and publish a tour or activity.'),
            ],
            [
                'label' => __('List on the Tanova marketplace'),
                'done'  => MarketplaceListing::where('visible', true)->exists(),
                'hint'  => __('Toggle at least one experience visible under Marketplace.'),
            ],
            [
                'label' => __('Define a pricing tier'),
                'done'  => VendorPricingTier::exists(),
                'hint'  => __('Set up at least one pricing tier.'),
            ],
            [
                'label' => __('Set up a scheduled message'),
                'done'  => ScheduledMessage::exists(),
                'hint'  => __('Automate at least one lifecycle message.'),
            ],
        ];

        $done  = count(array_filter($checklist, fn ($i) => $i['done']));
        $total = count($checklist);

        return view('vendor.go-live.index', [
            'checklist'  => $checklist,
            'done'       => $done,
            'total'      => $total,
            'percent'    => $total ? (int) round($done / $total * 100) : 0,
            'page_title' => __('Go Live'),
        ]);
    }

    public function help()
    {
        return view('vendor.help.index', ['page_title' => __('Operations Manual')]);
    }

    public function apiDocs(\Illuminate\Http\Request $request)
    {
        $vendorId = resolve_current_vendor_id();

        return view('vendor.docs.index', [
            'page_title' => __('API & Website Docs'),
            'base_url'   => rtrim(url('/api/v'), '/'),
            'key_count'  => \Modules\Vendor\Models\VendorApiKey::where('vendor_id', $vendorId)->where('active', true)->count(),
            'tour_count' => Tour::where('author_id', $vendorId)->where('status', 'publish')->count(),
        ] + \Modules\Api\Docs\Reference::page((string) $request->query('section', '')));
    }
}
