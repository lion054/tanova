<?php
namespace Modules\Visa\Controllers;

use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Core\Events\CreatedServicesEvent;
use Modules\Core\Events\UpdatedServiceEvent;
use Modules\FrontendController;
use Modules\Visa\Models\VisaService;
use Modules\Visa\Models\VisaType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageVisaController extends FrontendController
{
    use \App\Traits\FiltersManageList;

    public function callAction($method, $parameters)
    {
        if (!VisaService::isEnable()) {
            return redirect('/');
        }
        return parent::callAction($method, $parameters);
    }

    public function index(Request $request)
    {
        $this->checkPermission('visa_view');
        [$list, $fb, $perPage] = $this->manageFilters($request, VisaService::where('author_id', Auth::id()), ['table' => 'bc_visa_services', 'noun' => __('visas'), 'status' => true, 'price' => true]);
        $rows = $list->paginate($perPage)->appends($request->query());
        return view('Visa::frontend.manageVisa.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'page_title' => __('Manage Visa Services'),
            'breadcrumbs' => [
                ['name' => __('Manage Visa Services'), 'url' => route('visa.vendor.index')],
                ['name' => __('All'), 'class' => 'active'],
            ],
        ]);
    }

    public function recovery(Request $request)
    {
        $this->checkPermission('visa_view');
        [$list, $fb, $perPage] = $this->manageFilters($request, VisaService::onlyTrashed()->where('author_id', Auth::id()), ['table' => 'bc_visa_services', 'noun' => __('visas'), 'status' => true, 'price' => true]);
        $rows = $list->paginate($perPage)->appends($request->query());
        return view('Visa::frontend.manageVisa.index', [
            'rows'       => $rows,
            'fb'         => $fb,
            'recovery'   => 1,
            'page_title' => __('Recovery Visa Services'),
            'breadcrumbs' => [
                ['name' => __('Manage Visa Services'), 'url' => route('visa.vendor.index')],
                ['name' => __('Recovery'), 'class' => 'active'],
            ],
        ]);
    }

    public function restore($id)
    {
        $this->checkPermission('visa_delete');
        $row = VisaService::onlyTrashed()->where('author_id', Auth::id())->where('id', $id)->first();
        if ($row) {
            $row->restore();
            event(new UpdatedServiceEvent($row));
        }
        return redirect(route('visa.vendor.recovery'))->with('success', __('Visa service restored!'));
    }

    public function create()
    {
        $this->checkPermission('visa_create');
        return view('Visa::frontend.manageVisa.detail', [
            'row'         => new VisaService(),
            'translation' => new VisaService(),
            'types'       => VisaType::all(),
            'page_title'  => __('Add Visa Service'),
            'breadcrumbs' => [
                ['name' => __('Manage Visa Services'), 'url' => route('visa.vendor.index')],
                ['name' => __('Create'), 'class' => 'active'],
            ],
        ]);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('visa_update');
        $row = VisaService::where('author_id', Auth::id())->find($id);
        if (!$row) {
            return redirect(route('visa.vendor.index'))->with('warning', __('Visa service not found!'));
        }
        return view('Visa::frontend.manageVisa.detail', [
            'row'         => $row,
            'translation' => $row->translate($request->query('lang')),
            'types'       => VisaType::all(),
            'page_title'  => __('Edit: ') . $row->title,
            'breadcrumbs' => [
                ['name' => __('Manage Visa Services'), 'url' => route('visa.vendor.index')],
                ['name' => __('Edit'), 'class' => 'active'],
            ],
        ]);
    }

    public function store(Request $request, $id)
    {
        if ($id > 0) {
            $this->checkPermission('visa_update');
            $row = VisaService::find($id);
            if (!$row || ($row->author_id != Auth::id() && !$this->hasPermission('visa_manage_others'))) {
                return redirect(route('visa.vendor.index'));
            }
        } else {
            $this->checkPermission('visa_create');
            $row = new VisaService();
            $row->status    = setting_item('visa_vendor_create_service_must_approved_by_admin', 0) ? 'pending' : 'publish';
            $row->author_id = Auth::id();
        }

        $request->validate([
            'title' => 'required',
            'code'  => 'required|alpha_dash:ascii|unique:bc_visa_services,code,' . $id,
        ]);

        $row->fillByAttr([
            'title', 'slug', 'code', 'to_country', 'type_id',
            'price', 'original_price', 'processing_days',
            'max_stay_days', 'multiple_entry', 'content',
            'image_id', 'status', 'enable_service_fee', 'service_fee',
        ], $request->input());

        if (!auth()->user()->checkUserPlan() && $row->status === 'publish') {
            return redirect(route('user.plan'));
        }

        $res = $row->saveOriginOrTranslation($request->input('lang'), true);

        if ($res) {
            if ($id > 0) {
                event(new UpdatedServiceEvent($row));
                return back()->with('success', __('Visa service updated'));
            }
            event(new CreatedServicesEvent($row));
            return redirect(route('visa.vendor.edit', ['id' => $row->id]))->with('success', __('Visa service created'));
        }
    }

    public function delete($id)
    {
        $this->checkPermission('visa_delete');
        if (request()->query('permanently_delete')) {
            $row = VisaService::where('author_id', Auth::id())->withTrashed()->find($id);
            if ($row) $row->forceDelete();
        } else {
            $row = VisaService::where('author_id', Auth::id())->find($id);
            if ($row) {
                $row->delete();
                event(new UpdatedServiceEvent($row));
            }
        }
        return redirect(route('visa.vendor.index'))->with('success', __('Visa service deleted!'));
    }

    public function bulkEdit($id, Request $request)
    {
        $this->checkPermission('visa_update');
        $row = VisaService::where('author_id', Auth::id())->find($id);
        if (!$row) return redirect()->back()->with('error', __('Not Found'));

        switch ($request->input('action')) {
            case 'make-hide':
                $row->status = 'draft';
                break;
            case 'make-publish':
                if (!auth()->user()->checkUserPlan()) return redirect(route('user.plan'));
                $row->status = 'publish';
                break;
            default:
                return redirect()->back()->with('error', __('Please select an action!'));
        }
        $row->save();
        event(new UpdatedServiceEvent($row));
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function bookingReportBulkEdit($booking_id, Request $request)
    {
        $status = $request->input('status');
        if (!empty(setting_item('visa_allow_vendor_can_change_their_booking_status')) && $status && $booking_id) {
            $item = Booking::where('id', $booking_id)->where('vendor_id', Auth::id())->first();
            if ($item) {
                $item->status = $status;
                $item->save();
                if ($status == Booking::CANCELLED) $item->tryRefundToWallet();
                event(new BookingUpdatedEvent($item));
                return redirect()->back()->with('success', __('Update success'));
            }
            return redirect()->back()->with('error', __('Booking not found!'));
        }
        return redirect()->back()->with('error', __('Update fail!'));
    }
}
