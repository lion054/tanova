<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Bill;
use Modules\TourPay\Models\BillPayment;
use Modules\TourPay\Services\Profit;
use Pro\Integrations\Models\Operator;

/** Supplier bills: what you owe the people who deliver the trip, and what a booking really earned. Same rules as the TourPay bills screen. */
class VendorBillController extends VendorApiController
{
    public function index(Request $request): JsonResponse
    {
        $q = Bill::query();
        ListQuery::search($q, $request->query('q'), ['supplier_name', 'reference', 'description']);
        $status = (string) $request->query('status', '');
        if ($status === 'overdue') {
            $q->whereIn('status', ['open', 'part_paid'])->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
        } elseif (in_array($status, Bill::STATUSES, true)) {
            $q->where('status', $status);
        }
        foreach (['booking_id', 'supplier_id'] as $k) {
            if ($request->filled($k) && ctype_digit((string) $request->query($k))) { $q->where($k, (int) $request->query($k)); }
        }
        if ($d = $this->date($request, 'from')) { $q->whereDate('bill_date', '>=', $d); }
        if ($d = $this->date($request, 'to')) { $q->whereDate('bill_date', '<=', $d); }
        ListQuery::sort($q, $request->query('sort'), ['due' => ['due_date', 'asc'], 'newest' => ['id', 'desc'], 'amount' => ['total', 'desc']], 'newest');

        $res = $this->page($q, $request, fn ($b) => $this->shape($b))->getData(true);
        $res['meta']['owed'] = Bill::whereIn('status', ['open', 'part_paid'])->selectRaw('currency, SUM(total - amount_paid) AS total')->groupBy('currency')->pluck('total', 'currency')->map(fn ($v) => round((float) $v, 2))->all();

        return response()->json($res);
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->shape(Bill::findOrFail($id), true));
    }

    public function store(Request $request): JsonResponse
    {
        $d = $this->fields($request, true);
        if ($fail = $this->check($d, true)) {
            return $fail;
        }

        return $this->created($this->shape(Bill::create($d + ['status' => 'open'])->fresh(), true));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        if ($bill->status === 'void') {
            return $this->error('bill_void', 'A voided bill cannot be changed.', 409);
        }
        $d = $this->fields($request, false);
        if ($fail = $this->check($d, false)) {
            return $fail;
        }
        $bill->update($d);
        $bill->recalculate();

        return $this->success($this->shape($bill->fresh(), true));
    }

    public function destroy(int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        if ($bill->payments()->exists()) {
            return $this->error('has_payments', 'A bill with payments cannot be deleted. Void it instead.', 409);
        }
        $bill->delete();

        return $this->noContent();
    }

    public function addPayment(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(['bank', 'cash', 'card', 'mobile_money', 'other'])], 'paid_at' => ['nullable', 'date'], 'reference' => ['nullable', 'string', 'max:191'], 'notes' => ['nullable', 'string', 'max:1000']]);
        if ($bill->status === 'void') {
            return $this->error('bill_void', 'This bill is void.', 409);
        }
        if ($d['amount'] > $bill->balance() + 0.001) {
            return $this->error('exceeds_balance', 'That is more than is still owed on this bill.', 422);
        }
        BillPayment::create($d + ['vendor_id' => $bill->vendor_id, 'bill_id' => $bill->id, 'paid_at' => $d['paid_at'] ?? now()->toDateString()]);
        $bill->recalculate();

        return $this->created($this->shape($bill->fresh(), true));
    }

    public function void(int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        $bill->update(['status' => 'void']);

        return $this->success($this->shape($bill->fresh(), true));
    }

    /** What one booking earned: what it is billed minus its supplier bills. */
    public function profit(string $code): JsonResponse
    {
        $b = Booking::forVendor()->where('code', $code)->firstOrFail();

        return $this->success(['booking_code' => $b->code] + app(Profit::class)->forBooking($b));
    }

    private function fields(Request $request, bool $create): array
    {
        $req = $create ? 'required' : 'sometimes';
        $d = $request->validate([
            'supplier_id' => ['nullable', 'integer'], 'supplier_name' => ['nullable', 'string', 'max:255'], 'booking_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:255'],
            'currency' => [$req, 'string', 'size:3'], 'total' => [$req, 'numeric', 'min:0.01', 'max:100000000'],
            'bill_date' => [$req, 'date'], 'due_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if (isset($d['currency'])) {
            $d['currency'] = strtoupper($d['currency']);
        }

        return $d;
    }

    /** Suppliers and bookings must be yours; a bill needs someone to be owed. */
    private function check(array &$d, bool $create): ?JsonResponse
    {
        if (!empty($d['supplier_id'])) {
            $op = Operator::find($d['supplier_id']);
            if (!$op) {
                return $this->error('not_found', 'That supplier was not found.', 404);
            }
            $d['supplier_name'] = $op->name;
        }
        if ($create || array_key_exists('supplier_id', $d) || array_key_exists('supplier_name', $d)) {
            if (empty($d['supplier_id']) && empty($d['supplier_name'])) {
                return $this->error('validation_failed', 'Choose a supplier or give a supplier name.', 422);
            }
        }
        if (!empty($d['booking_id']) && !Booking::forVendor()->whereKey($d['booking_id'])->exists()) {
            return $this->error('not_found', 'That booking was not found.', 404);
        }
        if (!empty($d['due_date']) && !empty($d['bill_date']) && $d['due_date'] < $d['bill_date']) {
            return $this->error('validation_failed', 'The due date cannot be before the bill date.', 422);
        }

        return null;
    }

    private function shape(Bill $b, bool $withPayments = false): array
    {
        $out = [
            'id' => $b->id, 'supplier_id' => $b->supplier_id, 'supplier_name' => $b->supplier_name, 'booking_id' => $b->booking_id, 'reference' => $b->reference, 'description' => $b->description,
            'currency' => $b->currency, 'total' => (float) $b->total, 'amount_paid' => (float) $b->amount_paid, 'balance' => $b->balance(), 'status' => $b->status, 'overdue' => $b->isOverdue(),
            'bill_date' => optional($b->bill_date)->toDateString(), 'due_date' => optional($b->due_date)->toDateString(), 'notes' => $b->notes, 'created_at' => optional($b->created_at)->toIso8601String(),
        ];
        if ($withPayments) {
            $out['payments'] = $b->payments()->orderBy('paid_at')->orderBy('id')->get()->map(fn ($p) => ['id' => $p->id, 'amount' => (float) $p->amount, 'method' => $p->method, 'reference' => $p->reference, 'paid_at' => optional($p->paid_at)->toDateString(), 'notes' => $p->notes])->all();
        }

        return $out;
    }
}
