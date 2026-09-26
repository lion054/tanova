<?php

namespace Modules\Vendor\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Service;
use Modules\FrontendController;
use Modules\Vendor\Models\VendorPlan;
use Modules\Vendor\Models\VendorPlanOrder;
use Modules\Vendor\Models\VendorSubscription;
use Modules\Vendor\Services\CompanyOs;
use Modules\Vendor\Services\PlanBilling;
use Modules\Vendor\Services\PlanLimits;

/**
 * Plan & billing: the one place a company sees its plan, the Tanova OS it operates, what it has used, and where it changes or pays for any of it.
 * (Replaces "My Subscription" and "My Plans".) Owner only: staff never see it (see config/staff_access.php).
 */
class SubscriptionController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
    }

    public function index()
    {
        $this->checkPermission('dashboard_vendor_access');
        $user = Auth::user();
        $plan = CompanyOs::plan($user);

        $usage = [];
        if ($plan) {
            foreach ($user->vendorPlanData ?? [] as $type => $meta) {
                $os = CompanyOs::forType($type);
                if (!$meta['enable'] || ($os && !CompanyOs::has($user, $os))) {
                    continue;
                }
                $usage[$type] = ['used' => Service::where('author_id', $user->id)->where('object_model', $type)->count(), 'max' => (int) $meta['maximum_create']];
            }
        }
        $seatsUsed = DB::table('vendor_team')->where('vendor_id', $user->id)->count();

        return view('Vendor::frontend.subscription.index', [
            'page_title'   => __('Plan & billing'),
            'user'         => $user,
            'plan'         => $plan,
            'state'        => PlanLimits::state($user),
            'subscription' => VendorSubscription::activeForVendor($user->id),
            'onTrial'      => PlanBilling::onTrial($user),
            'osAll'        => CompanyOs::all(),
            'osChosen'     => CompanyOs::chosen($user),
            'osExtras'     => CompanyOs::extras($user),
            'osLimit'      => CompanyOs::baseLimit($user),
            'usage'        => $usage,
            'seatsUsed'    => $seatsUsed,
            'plans'        => VendorPlan::offered(),
            'orders'       => VendorPlanOrder::where('vendor_id', $user->id)->with('plan')->orderByDesc('id')->limit(15)->get(),
            'history'      => VendorSubscription::where('vendor_id', $user->id)->with('plan')->orderByDesc('id')->limit(10)->get(),
            'instructions' => (string) setting_item('vendor_plan_payment_instructions'),
            'breadcrumbs'  => [['name' => __('Plan & billing'), 'class' => 'active']],
        ]);
    }

    /** The old "My Plans" page: plans are on the one Plan & billing page now. */
    public function plans()
    {
        return redirect()->route('vendor.subscription.index');
    }

    /** Choose which OS the plan covers. */
    public function chooseOs(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        $data = $request->validate(['os' => ['nullable', 'array'], 'os.*' => ['string', 'max:20']]);
        $problem = CompanyOs::choose(Auth::user(), (array) ($data['os'] ?? []));

        return redirect(route('vendor.subscription.index') . '#os')->with($problem ? 'danger' : 'success', $problem ?: __('Saved. Your menu now shows what you operate.'));
    }

    /** Ask for a plan (start, renew or change), or for one extra OS. */
    public function order(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        $user = Auth::user();
        $cycle = $request->input('cycle') === 'yearly' ? 'yearly' : 'monthly';

        if ($request->input('kind') === 'addon') {
            $result = PlanBilling::orderAddon($user, (string) $request->input('os_key'), $cycle);
            if (is_string($result)) {
                return redirect(route('vendor.subscription.index') . '#os')->with('danger', $result);
            }
            if ($result === null) {
                return redirect(route('vendor.subscription.index') . '#os')->with('success', __('Added. It is free during your trial; the first payment will include it.'));
            }
        } else {
            $plan = VendorPlan::find((int) $request->input('plan_id'));
            if (!$plan) {
                return back()->with('danger', __('Choose a plan.'));
            }
            $result = PlanBilling::orderPlan($user, $plan, $cycle, (array) $request->input('os', []));
            if (is_string($result)) {
                return redirect(route('vendor.subscription.index') . '#plans')->with('danger', $result);
            }
        }

        return redirect(route('vendor.subscription.index') . '#orders')->with('success', __('Order :ref created. Pay it using the details below; your plan starts as soon as we confirm the payment.', ['ref' => $result->reference]));
    }

    public function cancelOrder($id)
    {
        $this->checkPermission('dashboard_vendor_access');
        $order = VendorPlanOrder::where('vendor_id', Auth::id())->findOrFail($id);
        PlanBilling::cancel($order);

        return redirect(route('vendor.subscription.index') . '#orders')->with('success', __('Order cancelled.'));
    }
}
