<?php

namespace Modules\Vendor\Admin;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Vendor\Models\VendorPlan;
use Modules\Vendor\Models\VendorSubscription;

class SubscriptionController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('vendor.admin.subscription.index'));
    }

    public function index(Request $request)
    {
        $this->checkPermission('vendor_payout_view');

        $query = VendorSubscription::with(['vendor', 'plan'])->orderBy('id', 'desc');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->input('plan_id'));
        }

        $data = [
            'rows'       => $query->paginate(20),
            'plans'      => VendorPlan::where('status', 'publish')->orderBy('name')->get(),
            'statuses'   => VendorSubscription::getAllStatuses(),
            'page_title' => __('Vendor Subscriptions'),
            'breadcrumbs' => [
                ['name' => __('Vendor Subscriptions'), 'class' => 'active'],
            ],
        ];

        return view('Vendor::admin.subscriptions.index', $data);
    }

    public function assign(Request $request)
    {
        $this->checkPermission('vendor_payout_manage');

        $plans = VendorPlan::where('status', 'publish')->orderBy('price')->get();

        $vendor = null;
        if ($request->filled('vendor_id')) {
            $vendor = User::find($request->input('vendor_id'));
        }

        $data = [
            'plans'      => $plans,
            'vendor'     => $vendor,
            'page_title' => __('Assign Plan to Vendor'),
            'breadcrumbs' => [
                ['name' => __('Vendor Subscriptions'), 'url' => route('vendor.admin.subscription.index')],
                ['name' => __('Assign Plan'), 'class' => 'active'],
            ],
        ];

        return view('Vendor::admin.subscriptions.assign', $data);
    }

    public function doAssign(Request $request)
    {
        $this->checkPermission('vendor_payout_manage');

        $request->validate([
            'vendor_id'     => 'required|exists:users,id',
            'plan_id'       => 'required|exists:core_vendor_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'starts_at'     => 'required|date',
            'amount_paid'   => 'required|numeric|min:0',
            'notes'         => 'nullable|string|max:1000',
            'os'            => 'nullable|array',
            'os.*'          => 'string|max:20',
        ]);

        $plan    = VendorPlan::findOrFail($request->input('plan_id'));
        $vendor  = User::findOrFail($request->input('vendor_id'));
        if (!$plan->coversAllOs() && ($problem = \Modules\Vendor\Services\CompanyOs::choose($vendor, (array) $request->input('os', []), $plan))) {
            return back()->withInput()->withErrors(['os' => $problem]);
        }
        if ($plan->coversAllOs()) {
            \Illuminate\Support\Facades\DB::table('vendor_company_os')->where('vendor_id', $vendor->id)->where('is_addon', 1)->delete();
        }
        $startsAt = Carbon::parse($request->input('starts_at'));
        $endsAt   = $request->input('billing_cycle') === 'yearly'
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();

        // Cancel any currently active subscription for this vendor
        VendorSubscription::where('vendor_id', $vendor->id)
            ->where('status', VendorSubscription::STATUS_ACTIVE)
            ->update(['status' => VendorSubscription::STATUS_CANCELLED]);

        $subscription = VendorSubscription::create([
            'vendor_id'       => $vendor->id,
            'plan_id'         => $plan->id,
            'billing_cycle'   => $request->input('billing_cycle'),
            'amount_paid'     => $request->input('amount_paid'),
            'payment_gateway' => 'manual',
            'status'          => VendorSubscription::STATUS_ACTIVE,
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'notes'           => $request->input('notes'),
            'created_by'      => Auth::id(),
        ]);

        // Update vendor's plan columns
        $vendor->vendor_plan_id         = $plan->id;
        $vendor->vendor_plan_expires_at = $endsAt;
        $vendor->save();

        return redirect(route('vendor.admin.subscription.index'))
            ->with('success', __('Plan assigned to :name until :date', [
                'name' => $vendor->getDisplayName(),
                'date' => $endsAt->format('d M Y'),
            ]));
    }

    public function cancel(Request $request, $id)
    {
        $this->checkPermission('vendor_payout_manage');

        $subscription = VendorSubscription::findOrFail($id);
        $subscription->status = VendorSubscription::STATUS_CANCELLED;
        $subscription->save();

        // If this was the vendor's current plan, clear it
        $vendor = $subscription->vendor;
        if ($vendor && (int) $vendor->vendor_plan_id === (int) $subscription->plan_id) {
            $vendor->vendor_plan_id         = null;
            $vendor->vendor_plan_expires_at = null;
            $vendor->save();
        }

        return redirect()->back()->with('success', __('Subscription cancelled.'));
    }
}
