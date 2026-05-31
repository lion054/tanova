<?php

namespace Modules\Vendor\Controllers;

use Illuminate\Support\Facades\Auth;
use Modules\FrontendController;
use Modules\Vendor\Models\VendorPlan;
use Modules\Vendor\Models\VendorSubscription;

class SubscriptionController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * Show the vendor's current subscription status.
     */
    public function index()
    {
        $this->checkPermission('dashboard_vendor_access');

        $user         = Auth::user();
        $subscription = VendorSubscription::activeForVendor($user->id);
        $history      = VendorSubscription::where('vendor_id', $user->id)
            ->with('plan')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $data = [
            'page_title'   => __('My Subscription'),
            'subscription' => $subscription,
            'history'      => $history,
            'currentUser'  => $user,
            'breadcrumbs'  => [
                ['name' => __('Vendor Dashboard'), 'url' => route('vendor.dashboard')],
                ['name' => __('Subscription'),     'class' => 'active'],
            ],
        ];

        return view('Vendor::frontend.subscription.index', $data);
    }

    /**
     * Show all available plans so the vendor can choose one.
     */
    public function plans()
    {
        $this->checkPermission('dashboard_vendor_access');

        $plans = VendorPlan::where('status', 'publish')
            ->with('meta')
            ->orderBy('price')
            ->get();

        $currentSubscription = VendorSubscription::activeForVendor(Auth::id());

        $data = [
            'page_title'          => __('Subscription Plans'),
            'plans'               => $plans,
            'currentSubscription' => $currentSubscription,
            'breadcrumbs'         => [
                ['name' => __('Vendor Dashboard'), 'url'   => route('vendor.dashboard')],
                ['name' => __('Subscription'),     'url'   => route('vendor.subscription.index')],
                ['name' => __('Plans'),            'class' => 'active'],
            ],
        ];

        return view('Vendor::frontend.subscription.plans', $data);
    }
}
