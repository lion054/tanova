<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\InvoiceItem;
use Modules\TourPay\Models\Payment;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\InvoiceRuleException;
use Modules\Vendor\Models\VendorCustomer;

/**
 * Invoices and quotations: TourPay, over the API. Same rules as the portal screens (InvoiceBook), same tables.
 * `lines` in the API are TourPay's items.
 */
class VendorInvoiceController extends VendorApiController
{
    public function __construct(private InvoiceBook $book) {}

    public function index(Request $request): JsonResponse
    {
        $type = in_array($request->query('type'), ['quotation', 'credit_note'], true) ? $request->query('type') : 'invoice';
        $q = Invoice::where('type', $type);
        ListQuery::search($q, $request->query('q'), ['invoice_number', 'client_name', 'client_email', 'title']);
        $status = (string) $request->query('status', '');
        if ($status === 'overdue') {
            $q->overdue();
        } elseif (in_array($status, $type === 'quotation' ? Invoice::QUOTE_STATUSES : Invoice::STATUSES, true)) {
            $q->where('status', $status);
        }
        if ($d = $this->date($request, 'from')) { $q->whereDate('issue_date', '>=', $d); }
        if ($d = $this->date($request, 'to')) { $q->whereDate('issue_date', '<=', $d); }
        if ($request->filled('booking_id') && ctype_digit((string) $request->query('booking_id'))) { $q->where('booking_id', (int) $request->query('booking_id')); }
        if ($request->filled('customer_id') && ctype_digit((string) $request->query('customer_id'))) { $q->where('customer_id', (int) $request->query('customer_id')); }
        ListQuery::sort($q, $request->query('sort'), ['newest' => ['issue_date', 'desc'], 'oldest' => ['issue_date', 'asc'], 'amount' => ['total', 'desc'], 'due' => ['due_date', 'asc'], 'number' => ['invoice_number', 'desc']], 'newest');

        $with = $this->includes($request);
        $res = $this->page($q, $request, fn ($i) => $this->shape($i, $with))->getData(true);
        $all = Invoice::where('type', 'invoice');
        $open = (clone $all)->whereNotIn('status', ['paid', 'void']);
        $res['meta']['summary'] = [
            'outstanding' => round((float) $open->sum('total') - (float) (clone $open)->sum('amount_paid'), 2),
            'paid' => round((float) (clone $all)->where('status', 'paid')->sum('total'), 2),
            'count' => (clone $all)->count(),
        ];

        return response()->json($res);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->success($this->shape(Invoice::findOrFail($id), ['lines', 'payments']));
    }

    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'type'            => ['nullable', Rule::in(['invoice', 'quotation'])],
            'customer_id'     => ['nullable', 'integer'],
            'booking_id'      => ['nullable', 'integer'],
            'bill_to_name'    => ['required', 'string', 'max:191'],
            'bill_to_email'   => ['nullable', 'email', 'max:191'],
            'bill_to_address' => ['nullable', 'string', 'max:1000'],
            'title'           => ['nullable', 'string', 'max:255'],
            'issue_date'      => ['nullable', 'date'],
            'due_date'        => ['nullable', 'date', 'after_or_equal:issue_date'],
            'valid_days'      => ['nullable', 'integer', 'min:1', 'max:365'],
            'currency'        => ['nullable', 'string', 'size:3'],
            'tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_lines'       => ['nullable', 'array', 'max:6'],
            'tax_lines.*.name' => ['required_with:tax_lines', 'string', 'max:40'],
            'tax_lines.*.rate' => ['required_with:tax_lines', 'numeric', 'min:0', 'max:100'],
            'tax_mode'        => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'terms'           => ['nullable', 'string', 'max:2000'],
            'lines'                => ['nullable', 'array', 'max:100'],
            'lines.*.description'  => ['required', 'string', 'max:500'],
            'lines.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price'   => ['required', 'numeric'],
        ]);
        if (!empty($d['customer_id']) && !VendorCustomer::whereKey($d['customer_id'])->exists()) {
            return $this->error('not_found', 'That customer was not found.', 404);
        }
        if (!empty($d['booking_id']) && !\Modules\Booking\Models\Booking::forVendor()->whereKey($d['booking_id'])->exists()) {
            return $this->error('not_found', 'That booking was not found.', 404);
        }
        $type = $d['type'] ?? 'invoice';
        $s = Setting::forVendor($this->vendorId());
        $inv = Invoice::create([
            'vendor_id' => $this->vendorId(), 'author_id' => $this->vendorId(),
            'invoice_number' => Invoice::generateNumber($this->vendorId(), $type), 'type' => $type, 'status' => 'draft', 'pay_token' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $d['customer_id'] ?? null, 'booking_id' => $d['booking_id'] ?? null,
            'client_name' => $d['bill_to_name'], 'client_email' => $d['bill_to_email'] ?? null, 'client_address' => $d['bill_to_address'] ?? null, 'title' => $d['title'] ?? null,
            'issue_date' => $d['issue_date'] ?? now()->toDateString(), 'due_date' => $d['due_date'] ?? null, 'valid_days' => $d['valid_days'] ?? ($type === 'quotation' ? ($s->default_valid_days ?: 14) : null),
            'currency' => strtoupper($d['currency'] ?? ($s->default_currency ?: 'USD')), 'tax_rate' => $d['tax_rate'] ?? ($s->default_tax_rate ?? 0),
            // The API has always added tax on top of the lines, so that stays the default here (the portal form defaults to tax-inclusive prices).
            'tax_mode' => $d['tax_mode'] ?? 'exclusive', 'tax_lines' => !empty($d['tax_lines']) ? array_values($d['tax_lines']) : null, 'discount' => $d['discount'] ?? 0,
            'notes' => $d['notes'] ?? $s->default_notes, 'payment_terms' => $d['terms'] ?? $s->default_terms, 'banking_details' => $s->banking_details, 'template' => $s->template ?: 1,
        ]);
        foreach ($d['lines'] ?? [] as $l) {
            $this->book->addItem($inv, $l['description'], (float) $l['quantity'], (float) $l['unit_price']);
        }
        $inv->recalculate();

        return $this->created($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        try {
            $this->book->assertEditable($inv);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }
        $d = $request->validate([
            'bill_to_name'    => ['sometimes', 'string', 'max:191'],
            'bill_to_email'   => ['nullable', 'email', 'max:191'],
            'bill_to_address' => ['nullable', 'string', 'max:1000'],
            'title'           => ['nullable', 'string', 'max:255'],
            'issue_date'      => ['sometimes', 'date'],
            'due_date'        => ['nullable', 'date'],
            'tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_mode'        => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'terms'           => ['nullable', 'string', 'max:2000'],
        ]);
        $issue = $d['issue_date'] ?? $inv->issue_date->toDateString();
        if (!empty($d['due_date']) && $d['due_date'] < $issue) {
            return $this->error('validation_failed', 'The due date cannot be before the issue date.', 422);
        }
        $map = ['bill_to_name' => 'client_name', 'bill_to_email' => 'client_email', 'bill_to_address' => 'client_address', 'terms' => 'payment_terms'];
        $fields = [];
        foreach ($d as $k => $v) {
            $fields[$map[$k] ?? $k] = $v;
        }
        foreach (['tax_rate', 'discount'] as $k) {
            if (array_key_exists($k, $fields) && $fields[$k] === null) { $fields[$k] = 0; }
        }
        $inv->update($fields);
        $inv->recalculate();

        return $this->success($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->book->deleteDraft(Invoice::findOrFail($id));
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->noContent();
    }

    public function addLine(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $d = $request->validate(['description' => ['required', 'string', 'max:500'], 'quantity' => ['required', 'numeric', 'min:0.01'], 'unit_price' => ['required', 'numeric']]);
        try {
            $this->book->addItem($inv, $d['description'], (float) $d['quantity'], (float) $d['unit_price']);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->created($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function removeLine(int $id, int $lineId): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        try {
            $this->book->removeItem($inv, InvoiceItem::where('invoice_id', $inv->id)->findOrFail($lineId));
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->noContent();
    }

    public function addPayment(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $d = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(Invoice::METHODS)],
            'reference' => ['nullable', 'string', 'max:191'], 'paid_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $this->book->recordPayment($inv, (float) $d['amount'], $d['method'], $d['paid_at'] ?? now()->toDateString(), $d['reference'] ?? null, $d['notes'] ?? null, 'api');
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), $e->errorCode === 'exceeds_balance' ? 422 : 409);
        }

        return $this->created($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function removePayment(int $id, int $paymentId): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $this->book->removePayment($inv, Payment::where('invoice_id', $inv->id)->findOrFail($paymentId));

        return $this->noContent();
    }

    public function issue(int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        try {
            $this->book->issue($inv);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->success($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function void(int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $this->book->void($inv);

        return $this->success($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function pdf(int $id)
    {
        $inv = Invoice::with(['items', 'payments'])->findOrFail($id);
        $company = optional(\App\User::find($inv->vendor_id))->business_name ?: optional(\App\User::find($inv->vendor_id))->name;
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml(view('TourPay::pdf.template' . max(1, min(5, (int) $inv->template)), ['row' => $inv, 'company' => $company])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="' . $inv->invoice_number . '.pdf"']);
    }

    /** A credit note against an invoice: lowers what is owed; anything already paid becomes a refund due. */
    public function creditNote(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $d = $request->validate(['amount' => ['nullable', 'numeric', 'min:0.01'], 'reason' => ['required', 'string', 'max:240']]);
        try {
            $cn = $this->book->creditNote($inv, isset($d['amount']) ? (float) $d['amount'] : null, $d['reason']);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), $e->errorCode === 'not_creditable' ? 409 : 422);
        }

        return $this->created($this->shape($cn->fresh(), ['lines', 'payments']));
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(Invoice::METHODS)], 'paid_at' => ['nullable', 'date'], 'reference' => ['nullable', 'string', 'max:191'], 'notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $this->book->recordRefund($inv, (float) $d['amount'], $d['method'], $d['paid_at'] ?? now()->toDateString(), $d['reference'] ?? null, $d['notes'] ?? null);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), $e->errorCode === 'exceeds_refund_due' ? 422 : 409);
        }

        return $this->created($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $d = $request->validate(['mode' => ['required', Rule::in(['none', 'deposit', 'split'])], 'percent' => ['nullable', 'numeric', 'min:1', 'max:99'], 'parts' => ['nullable', 'integer', 'min:2', 'max:12'], 'balance_days' => ['nullable', 'integer', 'min:0', 'max:365']]);
        try {
            $this->book->setSchedule($inv, $d['mode'], isset($d['percent']) ? (float) $d['percent'] : null, $d['parts'] ?? null, $d['balance_days'] ?? null);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), in_array($e->errorCode, ['invoice_locked'], true) ? 409 : 422);
        }

        return $this->success($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function convert(int $id): JsonResponse
    {
        try {
            $inv = $this->book->convertQuotation(Invoice::with('items')->findOrFail($id));
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->created($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    public function duplicate(int $id): JsonResponse
    {
        return $this->created($this->shape($this->book->duplicate(Invoice::with('items')->findOrFail($id))->fresh(), ['lines', 'payments']));
    }

    /** Confirm or refuse a bank transfer a guest reported from the pay page. */
    public function decidePayment(int $id, int $paymentId, string $decision): JsonResponse
    {
        $inv = Invoice::findOrFail($id);
        $p = Payment::where('invoice_id', $inv->id)->findOrFail($paymentId);
        try {
            $decision === 'approve' ? $this->book->approvePayment($inv, $p) : $this->book->rejectPayment($inv, $p);
        } catch (InvoiceRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->success($this->shape($inv->fresh(), ['lines', 'payments']));
    }

    /** E-mail it to the client. A test key sends nothing. */
    public function send(Request $request, int $id): JsonResponse
    {
        $inv = Invoice::with('items')->findOrFail($id);
        $d = $request->validate(['email' => ['nullable', 'email', 'max:191']]);
        $to = $d['email'] ?? $inv->client_email;
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->error('no_email', 'There is no valid e-mail address to send it to.', 422);
        }
        if (!$inv->items()->exists()) {
            return $this->error('no_lines', 'Add at least one line before sending it.', 409);
        }
        if ($this->isTest()) {
            return $this->success(['sent_to' => $to, 'simulated' => true]);
        }
        $company = optional(\App\User::find($inv->vendor_id))->business_name ?: optional(\App\User::find($inv->vendor_id))->name;
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml(view('TourPay::pdf.template' . max(1, min(5, (int) $inv->template)), ['row' => $inv, 'company' => $company])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();
        try {
            \Illuminate\Support\Facades\Mail::to($to)->send(new \Modules\TourPay\Emails\InvoiceEmail($inv, $dompdf->output()));
        } catch (\Throwable $e) {
            return $this->error('send_failed', 'The mail server refused it. Check the mail settings and try again.', 502);
        }
        if ($inv->status === 'draft') {
            $inv->update(['status' => 'sent', 'sent_at' => now()]);
            $inv->recalculate();
        } else {
            $inv->update(['sent_at' => now()]);
        }

        return $this->success(['sent_to' => $to, 'invoice' => $this->shape($inv->fresh())]);
    }

    private function includes(Request $request): array
    {
        return array_values(array_intersect(array_map('trim', explode(',', (string) $request->query('include', ''))), ['lines', 'payments']));
    }

    private function shape(Invoice $i, array $with = []): array
    {
        $out = [
            'id' => $i->id, 'number' => $i->invoice_number, 'type' => $i->type, 'status' => $i->status,
            'overdue' => $i->isOverdue(),
            'booking_id' => $i->booking_id, 'customer_id' => $i->customer_id,
            'bill_to' => ['name' => $i->client_name, 'email' => $i->client_email, 'address' => $i->client_address],
            'title' => $i->title,
            'issue_date' => optional($i->issue_date)->toDateString(), 'due_date' => optional($i->due_date)->toDateString(), 'valid_days' => $i->valid_days, 'currency' => $i->currency,
            'subtotal' => (float) $i->subtotal, 'discount' => (float) $i->discount, 'tax_rate' => (float) $i->tax_rate, 'tax_mode' => $i->tax_mode ?: 'inclusive', 'tax_amount' => (float) $i->tax_amount,
            'tax_lines' => $i->tax_lines ?: [],
            'total' => (float) $i->total, 'credit_total' => (float) $i->credit_total, 'amount_paid' => (float) $i->amount_paid, 'balance' => $i->balance(), 'refund_due' => $i->refundDue(),
            'parent_id' => $i->parent_id,
            'notes' => $i->notes, 'terms' => $i->payment_terms,
            'pay_url' => route('tourpay.pay', $i->pay_token),
            'sent_at' => optional($i->sent_at)->toIso8601String(), 'viewed_at' => optional($i->viewed_at)->toIso8601String(),
            'created_at' => optional($i->created_at)->toIso8601String(),
        ];
        if (in_array('lines', $with, true)) {
            $out['lines'] = $i->items()->orderBy('sort_order')->orderBy('id')->get()->map(fn ($l) => ['id' => $l->id, 'description' => $l->name, 'details' => $l->description, 'quantity' => (float) $l->quantity, 'unit_price' => (float) $l->unit_price, 'line_total' => (float) $l->total])->all();
        }
        if (in_array('payments', $with, true)) {
            $out['payments'] = $i->payments()->orderBy('paid_at')->orderBy('id')->get()->map(fn ($p) => ['id' => $p->id, 'amount' => (float) $p->amount, 'method' => $p->method, 'reference' => $p->reference, 'paid_at' => optional($p->paid_at)->toDateString(), 'notes' => $p->notes, 'source' => $p->source, 'status' => $p->status])->all();
            $out['schedule'] = collect($this->book->schedule($i))->map(fn ($r) => ['label' => $r['label'], 'amount' => $r['amount'], 'due_date' => $r['due_date']->toDateString(), 'paid' => $r['paid'], 'remaining' => $r['remaining'], 'late' => $r['late']])->all();
        }

        return $out;
    }
}
