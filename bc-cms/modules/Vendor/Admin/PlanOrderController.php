<?php

namespace Modules\Vendor\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Vendor\Models\VendorPlanOrder;
use Modules\Vendor\Services\PlanBilling;

/** Orders companies have placed for a plan or an extra OS. The platform team confirms each one when the money has arrived. */
class PlanOrderController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('vendor.admin.plan_orders.index'));
    }

    public function index(Request $request)
    {
        $this->checkPermission('vendor_payout_view');
        $status = $request->input('status', 'pending');
        $query = VendorPlanOrder::with(['vendor', 'plan'])->orderByDesc('id');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('Vendor::admin.plan_orders.index', ['rows' => $query->paginate(30)->appends($request->query()), 'status' => $status, 'page_title' => __('Plan orders'),
            'breadcrumbs' => [['name' => __('Plan orders'), 'class' => 'active']]]);
    }

    public function confirm(Request $request, $id)
    {
        $this->checkPermission('vendor_payout_manage');
        $data = $request->validate(['payment_method' => 'nullable|string|max:40', 'payment_note' => 'nullable|string|max:190']);
        $order = VendorPlanOrder::findOrFail($id);
        PlanBilling::confirm($order, Auth::user(), $data['payment_method'] ?? null, $data['payment_note'] ?? null);

        return back()->with('success', __('Order :ref confirmed. The plan is active and the company has been told.', ['ref' => $order->reference]));
    }

    public function cancel($id)
    {
        $this->checkPermission('vendor_payout_manage');
        PlanBilling::cancel(VendorPlanOrder::findOrFail($id));

        return back()->with('success', __('Order cancelled.'));
    }
}
