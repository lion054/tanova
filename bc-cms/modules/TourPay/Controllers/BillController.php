<?php
namespace Modules\TourPay\Controllers;

use App\Support\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\FrontendController;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;
use Pro\Integrations\Models\Operator;

/** Supplier bills: what you owe the people who deliver the trip, and what has been paid. */
class BillController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $q = Bill::with('supplier');
        ListQuery::search($q, $request->query('s'), ['supplier_name', 'reference', 'description']);
        $tab = in_array($request->query('tab'), ['open', 'overdue', 'paid', 'void'], true) ? $request->query('tab') : 'open';
        match ($tab) {
            'open'    => $q->whereIn('status', ['open', 'part_paid']),
            'overdue' => $q->whereIn('status', ['open', 'part_paid'])->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString()),
            'paid'    => $q->where('status', 'paid'),
            'void'    => $q->where('status', 'void'),
        };
        ListQuery::sort($q, $request->query('sort'), ['due' => ['due_date', 'asc'], 'newest' => ['id', 'desc'], 'amount' => ['total', 'desc']], $tab === 'paid' ? 'newest' : 'due');
        $rows = $q->paginate(ListQuery::perPage($request, [10, 20, 50, 100], 20))->withQueryString();

        $c = Bill::query()->selectRaw("SUM(status IN ('open','part_paid')) AS n_open, SUM(status IN ('open','part_paid') AND due_date IS NOT NULL AND due_date < CURDATE()) AS n_over, SUM(status = 'paid') AS n_paid, SUM(status = 'void') AS n_void")->first();
        $owed = Bill::whereIn('status', ['open', 'part_paid'])->selectRaw('currency, SUM(total - amount_paid) AS total')->groupBy('currency')->get();

        return view('TourPay::frontend.bills', [
            'rows' => $rows, 'tab' => $tab, 'owed' => $owed, 'page_title' => __('Supplier bills'), 'settings' => \Modules\TourPay\Models\Setting::forVendor((int) resolve_current_vendor_id()),
            'counts' => ['open' => (int) $c->n_open, 'overdue' => (int) $c->n_over, 'paid' => (int) $c->n_paid, 'void' => (int) $c->n_void],
            'suppliers' => Operator::orderBy('name')->get(['id', 'name']),
            'bookings' => Booking::forVendor()->orderByDesc('id')->limit(150)->get(['id', 'code', 'first_name', 'last_name', 'start_date']),
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->validated($request);
        Bill::create($d + ['status' => 'open']);

        return back()->with('success', __('Bill added.'));
    }

    public function update(Request $request, $id)
    {
        $bill = Bill::findOrFail($id);
        if ($bill->status === 'void') {
            return back()->with('error', __('A voided bill cannot be changed.'));
        }
        $bill->update($this->validated($request));
        $bill->recalculate();

        return back()->with('success', __('Bill updated.'));
    }

    public function addPayment(Request $request, $id)
    {
        $bill = Bill::findOrFail($id);
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(['bank', 'cash', 'card', 'mobile_money', 'other'])], 'paid_at' => ['nullable', 'date', 'before_or_equal:today'], 'reference' => ['nullable', 'string', 'max:191'], 'notes' => ['nullable', 'string', 'max:1000']]);
        if ($bill->status === 'void') {
            return back()->with('error', __('This bill is void.'));
        }
        if ($d['amount'] > $bill->balance() + 0.001) {
            return back()->withInput()->with('error', __('That is more than the :bal still owed on this bill.', ['bal' => number_format($bill->balance(), 2)]));
        }
        BillPayment::create($d + ['vendor_id' => $bill->vendor_id, 'bill_id' => $bill->id, 'paid_at' => $d['paid_at'] ?? now()->toDateString()]);
        $bill->recalculate();

        return back()->with('success', $bill->status === 'paid' ? __('Bill paid in full.') : __('Payment recorded.'));
    }

    public function void($id)
    {
        Bill::findOrFail($id)->update(['status' => 'void']);

        return back()->with('success', __('Bill voided. It no longer counts against the booking.'));
    }

    public function delete($id)
    {
        $bill = Bill::findOrFail($id);
        if ($bill->payments()->exists()) {
            return back()->with('error', __('A bill with payments cannot be deleted. Void it instead.'));
        }
        $bill->delete();

        return back()->with('success', __('Deleted.'));
    }

    private function validated(Request $request): array
    {
        $d = $request->validate([
            'supplier_id'   => ['nullable', 'integer'], 'supplier_name' => ['nullable', 'string', 'max:255'], 'booking_id' => ['nullable', 'integer'],
            'reference'     => ['nullable', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:255'],
            'currency'      => ['required', 'string', 'size:3'], 'total' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'bill_date'     => ['required', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:bill_date'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $d['currency'] = strtoupper($d['currency']);
        if (!empty($d['supplier_id'])) {
            $op = Operator::find($d['supplier_id']);
            $d['supplier_id'] = $op?->id;
            $d['supplier_name'] = $op?->name ?? ($d['supplier_name'] ?? null);
        }
        if (empty($d['supplier_id']) && empty($d['supplier_name'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['supplier_name' => __('Choose a supplier or type a name.')]);
        }
        if (!empty($d['booking_id']) && !Booking::forVendor()->whereKey($d['booking_id'])->exists()) {
            $d['booking_id'] = null;
        }

        return $d;
    }
}
