<?php
namespace Modules\TourPay\Controllers;

use App\Support\ListQuery;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\FrontendController;
use Modules\TourPay\Emails\InvoiceEmail;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Payment;
use Modules\TourPay\Models\Setting;
use Modules\TourPay\Services\InvoiceBook;
use Modules\TourPay\Services\InvoiceFromBooking;
use Modules\TourPay\Models\Attempt;
use Modules\TourPay\Services\Gateways\Gateway;
use Modules\TourPay\Services\Gateways\GatewayException;
use Modules\TourPay\Services\InvoiceRuleException;
use Modules\TourPay\Services\PayOnline;

/**
 * TourPay: invoices and quotations for a vendor. Every query is scoped to the signed-in vendor by the model
 * (BelongsToVendor); the rules for changing one live in InvoiceBook, shared with the API.
 */
class InvoiceController extends FrontendController
{
    public function __construct(private InvoiceBook $book, private PayOnline $online)
    {
        parent::__construct();
    }

    // ── List ─────────────────────────────────────────────────────────────────

    public const TABS = ['all', 'draft', 'awaiting', 'overdue', 'part_paid', 'paid', 'quotations', 'void'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'all';
        $query = Invoice::query();

        match ($tab) {
            'draft'      => $query->where('type', 'invoice')->where('status', 'draft'),
            'awaiting'   => $query->where('type', 'invoice')->where('status', 'sent'),
            'overdue'    => $query->overdue(),
            'part_paid'  => $query->where('type', 'invoice')->where('status', 'part_paid'),
            'paid'       => $query->where('type', 'invoice')->where('status', 'paid'),
            'quotations' => $query->where('type', 'quotation'),
            'void'       => $query->whereIn('status', ['void', 'credited']),
            default      => null,
        };
        if (in_array($request->query('type'), ['invoice', 'quotation', 'credit_note'], true)) {
            $query->where('type', $request->query('type'));
        }
        if ($request->filled('status') && $request->query('status') !== 'overdue') {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->query('status') === 'overdue') {
            $query->overdue();
        }
        ListQuery::search($query, $request->query('s'), ['invoice_number', 'client_name', 'client_email', 'title']);
        if ($d = ListQuery::date($request->query('date_from'))) { $query->whereDate('issue_date', '>=', $d); }
        if ($d = ListQuery::date($request->query('date_to')))   { $query->whereDate('issue_date', '<=', $d); }
        if ($request->filled('currency')) { $query->where('currency', (string) $request->query('currency')); }
        ListQuery::sort($query, $request->query('sort'), ['newest' => ['id', 'desc'], 'oldest' => ['id', 'asc'], 'amount' => ['total', 'desc'], 'due' => ['due_date', 'asc']], 'newest');

        $rows = $query->paginate(ListQuery::perPage($request, [10, 20, 50, 100], 20))->withQueryString();

        // Tab counts and money, each from one grouped query over this vendor's documents.
        $c = Invoice::query()->selectRaw("
            COUNT(*) AS n_all,
            SUM(type = 'invoice' AND status = 'draft') AS n_draft,
            SUM(type = 'invoice' AND status = 'sent') AS n_awaiting,
            SUM(type = 'invoice' AND status = 'part_paid') AS n_part_paid,
            SUM(type = 'invoice' AND status = 'paid') AS n_paid,
            SUM(type = 'quotation') AS n_quotations,
            SUM(status IN ('void','credited')) AS n_void,
            SUM(type = 'invoice' AND status NOT IN ('paid','void','draft','credited') AND due_date IS NOT NULL AND due_date < CURDATE()) AS n_overdue")->first();
        $counts = ['all' => (int) $c->n_all, 'draft' => (int) $c->n_draft, 'awaiting' => (int) $c->n_awaiting, 'overdue' => (int) $c->n_overdue, 'part_paid' => (int) $c->n_part_paid,
            'paid' => (int) $c->n_paid, 'quotations' => (int) $c->n_quotations, 'void' => (int) $c->n_void];

        $open = fn () => Invoice::where('type', 'invoice')->whereNotIn('status', ['paid', 'void', 'draft', 'credited']);
        $stats = [
            'outstanding_by_cur' => $open()->selectRaw('currency, SUM(total - credit_total - amount_paid) AS total, COUNT(*) AS cnt')->groupBy('currency')->orderByDesc('total')->get(),
            'overdue_by_cur'     => Invoice::overdue()->selectRaw('currency, SUM(total - credit_total - amount_paid) AS total, COUNT(*) AS cnt')->groupBy('currency')->orderByDesc('total')->get(),
            // Money that actually arrived this month, from the ledger (not from an invoice's status).
            'paid_month_by_cur'  => Payment::query()->join('bc_tourpay_invoices as inv', 'inv.id', '=', 'bc_tourpay_payments.invoice_id')
                ->whereMonth('bc_tourpay_payments.paid_at', now()->month)->whereYear('bc_tourpay_payments.paid_at', now()->year)
                ->selectRaw('inv.currency AS currency, SUM(bc_tourpay_payments.amount) AS total')->groupBy('inv.currency')->orderByDesc('total')->get(),
            'drafts'             => $counts['draft'],
            'total'              => $counts['all'],
            'currencies'         => Invoice::query()->distinct()->pluck('currency')->filter()->sort()->values(),
        ];

        $settings = Setting::forVendor($this->vendorId());
        // Totals across currencies, in the vendor's base currency by their own rates (only when every currency has a rate).
        $inBase = function ($rowsByCur) use ($settings) {
            if (!$settings->base_currency || $rowsByCur->count() < 2 && optional($rowsByCur->first())->currency === $settings->base_currency) {
                return null;
            }
            $sum = 0.0;
            foreach ($rowsByCur as $r) {
                $v = $settings->toBase((float) $r->total, (string) $r->currency);
                if ($v === null) {
                    return null;
                }
                $sum += $v;
            }

            return $rowsByCur->isEmpty() ? null : round($sum, 2);
        };
        $stats['outstanding_base'] = $inBase($stats['outstanding_by_cur']);
        $stats['overdue_base']     = $inBase($stats['overdue_by_cur']);
        $stats['paid_month_base']  = $inBase($stats['paid_month_by_cur']);

        return view('TourPay::frontend.index', [
            'rows' => $rows, 'stats' => $stats, 'counts' => $counts, 'tab' => $tab, 'settings' => $settings,
            'page_title' => __('TourPay'),
        ]);
    }

    // ── Create and edit ──────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $type = $request->input('type') === 'quotation' ? 'quotation' : 'invoice';
        $s = Setting::forVendor($this->vendorId());
        $row = new Invoice([
            'type' => $type, 'currency' => $s->default_currency ?: setting_item('tourpay_default_currency', 'USD'), 'template' => $s->template ?: 1,
            'tax_rate' => $s->default_tax_rate ?? 0, 'tax_mode' => $s->default_tax_mode ?: 'inclusive', 'payment_terms' => $s->default_terms, 'notes' => $s->default_notes,
            'banking_details' => $s->banking_details, 'issue_date' => now()->toDateString(),
            'valid_days' => $type === 'quotation' ? ($s->default_valid_days ?: 14) : null,
            'due_date' => $type === 'invoice' && $s->default_due_days ? now()->addDays($s->default_due_days)->toDateString() : null,
        ]);
        // Pre-fill from a customer record when the vendor came from the customer list.
        if ($request->filled('customer') && ctype_digit((string) $request->input('customer'))) {
            $this->fillFromCustomer($row, (int) $request->input('customer'));
        }

        return view('TourPay::frontend.form', $this->formData($row, $type, $type === 'quotation' ? __('New Quotation') : __('New Invoice')));
    }

    public function edit(Request $request, $id)
    {
        $row = Invoice::with('items')->findOrFail($id);

        return view('TourPay::frontend.form', $this->formData($row, $row->type, __('Edit: :n', ['n' => $row->invoice_number])));
    }

    /** Type-ahead for the form: JSON list of this business's customers or services matching what was typed. */
    public function suggest(Request $request, string $what)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 60);
        $svc = app(\Modules\TourPay\Services\Suggest::class);

        return response()->json(['results' => $what === 'customers' ? $svc->customers($q) : $svc->services($q)])->header('Cache-Control', 'no-store');
    }

    private function formData(Invoice $row, string $type, string $title): array
    {
        return ['row' => $row, 'type' => $type, 'page_title' => $title, 'settings' => Setting::forVendor($this->vendorId()),
        ];
    }

    private function fillFromCustomer(Invoice $row, int $customerId): void
    {
        if ($c = \Modules\Vendor\Models\VendorCustomer::find($customerId)) {
            $row->customer_id = $c->id;
            $row->client_name = trim($c->first_name . ' ' . $c->last_name);
            $row->client_email = $c->email;
            $row->client_phone = $c->phone;
            $row->client_country = $c->nationality;
        }
    }

    public function store(Request $request, $id)
    {
        $data = $request->validate([
            'type'         => ['required', Rule::in(['invoice', 'quotation'])],
            'client_name'  => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:40'],
            'client_country' => ['nullable', 'string', 'max:80'],
            'client_address' => ['nullable', 'string', 'max:1000'],
            'customer_id'  => ['nullable', 'integer'],
            'title'        => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'currency'     => ['required', 'string', 'size:3'],
            'tax_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_mode'     => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'tax_lines'          => ['nullable', 'array', 'max:6'],
            'tax_lines.*.name'   => ['nullable', 'string', 'max:40'],
            'tax_lines.*.rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount'     => ['nullable', 'numeric', 'min:0'],
            'issue_date'   => ['nullable', 'date'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:issue_date'],
            'valid_days'   => ['nullable', 'integer', 'min:1', 'max:365'],
            'template'     => ['nullable', 'integer', 'between:1,5'],
            'notes'        => ['nullable', 'string', 'max:3000'],
            'payment_terms' => ['nullable', 'string', 'max:3000'],
            'banking_details' => ['nullable', 'array'],
            'items'                => ['required', 'array', 'min:1', 'max:100'],
            'items.*.name'         => ['nullable', 'string', 'max:250'],
            'items.*.description'  => ['nullable', 'string', 'max:1000'],
            'items.*.quantity'     => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'items.*.unit_price'   => ['nullable', 'numeric', 'min:-10000000', 'max:10000000'],
        ]);
        if (!collect($data['items'])->contains(fn ($i) => trim((string) ($i['name'] ?? '')) !== '')) {
            return back()->withInput()->withErrors(['items' => __('Add at least one item with a name.')]);
        }
        if (!empty($data['customer_id']) && !\Modules\Vendor\Models\VendorCustomer::whereKey($data['customer_id'])->exists()) {
            $data['customer_id'] = null;
        }

        $fields = collect($data)->except(['items', 'action'])->all();
        $fields['tax_rate'] = $fields['tax_rate'] ?? 0;
        $fields['tax_mode'] = $fields['tax_mode'] ?? 'inclusive';
        $fields['discount'] = $fields['discount'] ?? 0;
        $fields['currency'] = strtoupper($fields['currency']);
        $fields['tax_lines'] = collect($data['tax_lines'] ?? [])->filter(fn ($l) => trim((string) ($l['name'] ?? '')) !== '' && (float) ($l['rate'] ?? 0) > 0)->map(fn ($l) => ['name' => trim($l['name']), 'rate' => (float) $l['rate']])->values()->all() ?: null;

        try {
            if ((int) $id === -1) {
                $row = Invoice::create($fields + [
                    'vendor_id' => $this->vendorId(), 'author_id' => Auth::id(), 'create_user' => Auth::id(), 'status' => 'draft',
                    'invoice_number' => Invoice::generateNumber($this->vendorId(), $data['type']), 'pay_token' => Str::uuid()->toString(),
                    'issue_date' => $fields['issue_date'] ?? now()->toDateString(),
                ]);
            } else {
                $row = Invoice::findOrFail($id);
                $this->book->assertEditable($row);
                if ($row->status !== 'draft') {
                    unset($fields['type']);   // an issued document keeps its kind
                }
                $row->update($fields + ['update_user' => Auth::id()]);
            }
            $this->book->replaceItems($row, $data['items']);
        } catch (InvoiceRuleException $e) {
            return redirect(route('tourpay.vendor.view', $id))->with('error', $e->getMessage());
        }

        if ($request->input('action') === 'send') {
            $sent = $this->deliver($row->fresh('items'), $row->client_email);
            if ($sent !== true) {
                return redirect(route('tourpay.vendor.view', $row->id))->with('error', __('Saved, but it was not sent: :why', ['why' => $sent]));
            }

            return redirect(route('tourpay.vendor.view', $row->id))->with('success', __('Saved and sent.'));
        }

        return redirect(route('tourpay.vendor.view', $row->id))->with('success', __('Saved.'));
    }

    public function delete($id)
    {
        try {
            $this->book->deleteDraft(Invoice::findOrFail($id));
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect(route('tourpay.vendor.index'))->with('success', __('Deleted.'));
    }

    // ── One document ─────────────────────────────────────────────────────────

    public function view($id)
    {
        $row = Invoice::with(['items', 'payments', 'parent'])->findOrFail($id);

        return view('TourPay::frontend.view', [
            'row' => $row, 'page_title' => $row->invoice_number, 'children' => Invoice::where('parent_id', $row->id)->get(),
            'schedule' => $this->book->schedule($row),
            'booking' => $row->booking_id ? Booking::withoutGlobalScopes()->where('vendor_id', $row->vendor_id)->find($row->booking_id) : null,
        ]);
    }

    public function pdf($id)
    {
        return $this->streamPdf(Invoice::with(['items', 'payments'])->findOrFail($id));
    }

    // ── Money ────────────────────────────────────────────────────────────────

    public function addPayment(Request $request, $id)
    {
        $row = Invoice::findOrFail($id);
        $d = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'method'    => ['required', Rule::in(Invoice::METHODS)],
            'paid_at'   => ['nullable', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:191'],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $this->book->recordPayment($row, (float) $d['amount'], $d['method'], $d['paid_at'] ?? now()->toDateString(), $d['reference'] ?? null, $d['notes'] ?? null, 'manual', Auth::id(), null, $request->boolean('send_receipt'));
        } catch (InvoiceRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        $row->refresh();

        return back()->with('success', $row->status === 'paid' ? __('Payment recorded. This invoice is now paid in full.') : __('Payment recorded. :bal still to pay.', ['bal' => $row->currency . ' ' . number_format($row->balance(), 2)]));
    }

    public function deletePayment($id, $paymentId)
    {
        $row = Invoice::findOrFail($id);
        $this->book->removePayment($row, Payment::where('invoice_id', $row->id)->findOrFail($paymentId));

        return back()->with('success', __('Payment removed.'));
    }

    /** "Mark as paid" records a payment of what is still owed. It never completes the booking: that stays a separate step. */
    public function markPaid(Request $request, $id)
    {
        $row = Invoice::findOrFail($id);
        try {
            $this->book->markPaid($row, (string) $request->input('method', 'other'), Auth::id());
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __(':n marked as paid.', ['n' => $row->invoice_number]));
    }

    public function void($id)
    {
        $this->book->void(Invoice::findOrFail($id));

        return back()->with('success', __('Voided. It stays on record but no longer counts.'));
    }

    public function creditNote(Request $request, $id)
    {
        $row = Invoice::findOrFail($id);
        $d = $request->validate(['amount' => ['nullable', 'numeric', 'min:0.01'], 'reason' => ['required', 'string', 'max:240']]);
        try {
            $cn = $this->book->creditNote($row, isset($d['amount']) ? (float) $d['amount'] : null, $d['reason']);
        } catch (InvoiceRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        $row->refresh();

        return back()->with('success', $row->refundDue() > 0
            ? __('Credit note :n issued. :a is now due back to the client: record the refund when you send it.', ['n' => $cn->invoice_number, 'a' => $row->currency . ' ' . number_format($row->refundDue(), 2)])
            : __('Credit note :n issued.', ['n' => $cn->invoice_number]));
    }

    public function refund(Request $request, $id)
    {
        $row = Invoice::findOrFail($id);
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'method' => ['required', Rule::in(Invoice::METHODS)], 'paid_at' => ['nullable', 'date', 'before_or_equal:today'], 'reference' => ['nullable', 'string', 'max:191'], 'notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $this->book->recordRefund($row, (float) $d['amount'], $d['method'], $d['paid_at'] ?? now()->toDateString(), $d['reference'] ?? null, $d['notes'] ?? null, Auth::id());
        } catch (InvoiceRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Refund recorded.'));
    }

    public function issue($id)
    {
        try {
            $this->book->issue(Invoice::findOrFail($id));
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Marked as sent.'));
    }

    public function duplicate($id)
    {
        $copy = $this->book->duplicate(Invoice::with('items')->findOrFail($id));

        return redirect(route('tourpay.vendor.edit', $copy->id))->with('success', __('Copied as :n. It is a draft.', ['n' => $copy->invoice_number]));
    }

    public function convert($id)
    {
        try {
            $inv = $this->book->convertQuotation(Invoice::with('items')->findOrFail($id));
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect(route('tourpay.vendor.edit', $inv->id))->with('success', __('Invoice :n created from the quotation.', ['n' => $inv->invoice_number]));
    }

    // ── Sending ──────────────────────────────────────────────────────────────

    public function sendEmail(Request $request, $id)
    {
        $request->validate(['email' => 'required|email']);
        $row = Invoice::with('items')->findOrFail($id);
        $sent = $this->deliver($row, $request->input('email'));

        return $sent === true
            ? back()->with('success', __(':n emailed to :email.', ['n' => $row->invoice_number, 'email' => $request->input('email')]))
            : back()->with('error', __('It was not sent: :why', ['why' => $sent]));
    }

    /** Send by e-mail. @return true|string true when sent, otherwise the reason. */
    private function deliver(Invoice $row, ?string $email): bool|string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return __('there is no valid e-mail address.');
        }
        if (!$row->items()->exists()) {
            return __('there are no items on it yet.');
        }
        try {
            Mail::to($email)->send(new InvoiceEmail($row, $this->buildPdfString($row)));
        } catch (\Throwable $e) {
            \Log::warning('tourpay_email_failed', ['invoice' => $row->id, 'error' => $e->getMessage()]);

            return __('the mail server refused it. Check the mail settings and try again.');
        }
        $row->refresh();
        if ($row->status === 'draft') {
            $row->update(['status' => 'sent', 'sent_at' => now()]);
            $row->recalculate();
        } else {
            $row->update(['sent_at' => now()]);
        }
        \App\Support\Audit::log('invoice.sent', $row, ['to' => $email], (int) $row->vendor_id, $row->invoice_number . ' e-mailed to ' . $email);

        return true;
    }

    /** WhatsApp on the vendor's OWN connected number (Integrations), never a platform-wide key. */
    public function sendWhatsApp(Request $request, $id)
    {
        $request->validate(['phone' => 'required|string']);
        $row = Invoice::findOrFail($id);
        $company = $this->companyName($row);
        $text = "Hi {$row->client_name}, please find your " . str_replace('_', ' ', $row->type) . " {$row->invoice_number} from {$company}.\n\n"
              . ($row->isQuotation() ? 'Amount: ' : 'Balance due: ') . "{$row->currency} " . number_format($row->isQuotation() ? $row->total : $row->balance(), 2) . "\n\n"
              . 'View & pay: ' . route('tourpay.pay', $row->pay_token);
        $r = app(\Modules\Vendor\Services\VendorChannelDispatcher::class)->send($this->vendorId(), 'whatsapp', ['phone' => $request->input('phone')], '', $text);

        if (($r['status'] ?? '') !== 'sent') {
            $why = match ($r['error'] ?? '') {
                'whatsapp_not_connected' => __('Connect your WhatsApp number under Integrations first.'),
                'no_phone' => __('There is no phone number.'),
                default => __('WhatsApp did not accept the message (:e).', ['e' => $r['error'] ?? 'unknown']),
            };

            return response()->json(['error' => $why], 422);
        }
        if ($row->status === 'draft' && $row->items()->exists()) {
            $row->update(['status' => 'sent', 'sent_at' => now()]);
            $row->recalculate();
        }

        return response()->json(['success' => true]);
    }

    public function shareLink($id)
    {
        $row = Invoice::findOrFail($id);
        return response()->json(['url' => route('tourpay.pay', $row->pay_token)]);
    }

    // ── Guest payments waiting for the vendor, and schedules ─────────────────

    public function approvePayment($id, $paymentId)
    {
        $row = Invoice::findOrFail($id);
        try {
            $this->book->approvePayment($row, Payment::where('invoice_id', $row->id)->findOrFail($paymentId), Auth::id());
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Payment confirmed.'));
    }

    public function rejectPayment($id, $paymentId)
    {
        $row = Invoice::findOrFail($id);
        try {
            $this->book->rejectPayment($row, Payment::where('invoice_id', $row->id)->findOrFail($paymentId));
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Payment rejected. It does not count.'));
    }

    /** The bank slip a guest attached, for the vendor only. */
    public function proof($id, $paymentId)
    {
        $row = Invoice::findOrFail($id);
        $p = Payment::where('invoice_id', $row->id)->whereNotNull('proof_path')->findOrFail($paymentId);
        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($p->proof_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($p->proof_path);
    }

    public function setSchedule(Request $request, $id)
    {
        $row = Invoice::findOrFail($id);
        $d = $request->validate([
            'mode' => ['required', Rule::in(['none', 'deposit', 'split'])], 'percent' => ['nullable', 'numeric', 'min:1', 'max:99'],
            'parts' => ['nullable', 'integer', 'min:2', 'max:12'], 'balance_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);
        try {
            $this->book->setSchedule($row, $d['mode'], isset($d['percent']) ? (float) $d['percent'] : null, $d['parts'] ?? null, $d['balance_days'] ?? null);
        } catch (InvoiceRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $d['mode'] === 'none' ? __('Schedule removed: one payment.') : __('Payment schedule saved.'));
    }

    // ── Settings ─────────────────────────────────────────────────────────────

    /** The settings page: one page, with a section for how invoices look and are numbered, getting paid, currencies, and reminders. */
    public function settingsPage()
    {
        $settings = Setting::forVendor($this->vendorId());

        return view('TourPay::frontend.settings', [
            'settings' => $settings, 'page_title' => __('TourPay settings'),
            'audit' => \Illuminate\Support\Facades\DB::table('bc_audit_log')->where('vendor_id', $this->vendorId())->orderByDesc('id')->limit(100)->get(),
            'gateways' => Gateway::LABELS, 'gatewayFields' => self::GATEWAY_FORM, 'secretFields' => Gateway::SECRET_FIELDS, 'currencyHints' => Gateway::CURRENCIES,
            'notifyUrls' => collect(array_keys(Gateway::LABELS))->mapWithKeys(fn ($g) => [$g => route('tourpay.notify', $g)])->all(),
        ]);
    }

    /** What each gateway asks for, and where the vendor finds it. */
    public const GATEWAY_FORM = [
        'stripe'   => ['fields' => ['secret_key' => ['Secret key', 'sk_live_… or sk_test_…']], 'help' => 'Stripe Dashboard → Developers → API keys.'],
        'paypal'   => ['fields' => ['client_id' => ['Client ID', ''], 'client_secret' => ['Client secret', '']], 'mode' => true, 'help' => 'PayPal Developer → Apps & Credentials → your REST app.'],
        'paystack' => ['fields' => ['secret_key' => ['Secret key', 'sk_live_… or sk_test_…']], 'help' => 'Paystack Dashboard → Settings → API Keys & Webhooks.'],
        'paynow'   => ['fields' => ['integration_id' => ['Integration ID', ''], 'integration_key' => ['Integration key', '']], 'help' => 'Paynow → My Business → Integrations. Takes USD and ZWG. Use a live integration: in test mode Paynow only accepts the merchant\'s own login.'],
        'pesapal'  => ['fields' => ['consumer_key' => ['Consumer key', ''], 'consumer_secret' => ['Consumer secret', '']], 'mode' => true, 'help' => 'Pesapal Merchant → Developers → API keys (use the Demo keys with Sandbox mode).'],
        'selcom'   => ['fields' => ['vendor' => ['Till number (vendor)', 'TILL60000000'], 'api_key' => ['API key', ''], 'api_secret' => ['API secret', '']], 'help' => 'Selcom Business → Developer. Takes TZS.'],
    ];

    public function saveSettings(Request $request)
    {
        $section = in_array($request->input('section'), ['general', 'currency', 'reminders', 'payments'], true) ? $request->input('section') : 'general';
        $d = [];
        if ($section === 'general') {
            $d = $request->validate([
                'invoice_prefix'   => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]*$/'],
                'quote_prefix'     => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]*$/'],
                'default_currency' => ['nullable', 'string', 'size:3'],
                'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'default_tax_mode' => ['nullable', Rule::in(['inclusive', 'exclusive'])],
                'default_due_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'default_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
                'default_terms'    => ['nullable', 'string', 'max:3000'],
                'default_notes'    => ['nullable', 'string', 'max:3000'],
                'template'         => ['nullable', 'integer', 'between:1,5'],
                'accent_color'     => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'footer_note'      => ['nullable', 'string', 'max:500'],
            ]);
            if (!empty($d['default_currency'])) {
                $d['default_currency'] = strtoupper($d['default_currency']);
            }
        } elseif ($section === 'currency') {
            $d = $request->validate(['base_currency' => ['nullable', 'string', 'size:3'], 'rates_text' => ['nullable', 'string', 'max:1000']]);
            $d['base_currency'] = !empty($d['base_currency']) ? strtoupper($d['base_currency']) : null;
            // One rate per line, "ZAR = 0.054": what 1 unit of that currency is worth in the base currency.
            $rates = [];
            foreach (preg_split('/\R/', (string) ($d['rates_text'] ?? '')) as $line) {
                if (preg_match('/^\s*([A-Za-z]{3})\s*[=:]\s*([0-9]*\.?[0-9]+)\s*$/', $line, $m) && (float) $m[2] > 0) {
                    $rates[strtoupper($m[1])] = (float) $m[2];
                }
            }
            $d['rates'] = $rates ?: null;
            unset($d['rates_text']);
        } elseif ($section === 'reminders') {
            $d = $request->validate(['remind_before_days' => ['nullable', 'integer', 'min:0', 'max:60'], 'remind_overdue_every' => ['nullable', 'integer', 'min:1', 'max:60'], 'remind_max' => ['nullable', 'integer', 'min:1', 'max:20'], 'remind_channel' => ['nullable', Rule::in(['email', 'whatsapp'])]]);
            $d['remind_enabled'] = $request->boolean('remind_enabled');
            foreach (['remind_before_days' => 3, 'remind_overdue_every' => 7, 'remind_max' => 4] as $k => $def) {
                $d[$k] = $d[$k] ?? $def;
            }
            $d['remind_channel'] = $d['remind_channel'] ?? 'email';
        } else {
            // Getting paid: the bank details, and each gateway. A blank key keeps the one already saved; keys are never shown again.
            $d = $request->validate(['banking_details' => ['nullable', 'array'], 'banking_details.*' => ['nullable', 'string', 'max:200']]);
            $d['banking_details'] = array_filter($d['banking_details'] ?? [], fn ($v) => $v !== null && $v !== '') ?: null;
            $d['bank_enabled'] = $request->boolean('bank_enabled');
            $d['send_receipts'] = $request->boolean('send_receipts');
            $s = Setting::forVendor($this->vendorId());
            $saved = (array) ($s->gateways ?? []);
            foreach (self::GATEWAY_FORM as $name => $def) {
                $in = (array) $request->input("gateways.$name", []);
                $g = (array) ($saved[$name] ?? []);
                $g['enabled'] = !empty($in['enabled']);
                foreach (array_keys($def['fields']) as $f) {
                    if (array_key_exists($f, $in)) {
                        $v = trim((string) $in[$f]);
                        if ($v !== '' || !in_array($f, Gateway::SECRET_FIELDS, true)) {
                            if (($g[$f] ?? null) !== $v && $v !== '') {
                                unset($g['ipn_id'], $g['ipn_url']);   // new keys: Pesapal must register again
                            }
                            $g[$f] = $v;
                        }
                    }
                }
                if (!empty($def['mode'])) {
                    $g['mode'] = ($in['mode'] ?? 'live') === 'sandbox' ? 'sandbox' : 'live';
                }
                $saved[$name] = $g;
            }
            $d['gateways'] = $saved;
        }
        Setting::forVendor($this->vendorId())->update($d);
        \App\Support\Audit::log($section === 'payments' ? 'payment_settings_changed' : 'settings_changed', null, ['section' => $section]);

        return redirect()->route('tourpay.vendor.settings.page')->withFragment($section)->with('success', __('Saved.'));
    }

    public function testGateway(Request $request, string $gateway)
    {
        abort_unless(isset(Gateway::REQUIRED[$gateway]), 404);
        $s = Setting::forVendor($this->vendorId());
        try {
            $gw = Gateway::make($gateway, $s->gateway($gateway));
            $gw->test();
            if ($extra = $gw->prepare(route('tourpay.notify', $gateway))) {
                $saved = (array) $s->gateways;
                $saved[$gateway] = array_merge((array) ($saved[$gateway] ?? []), $extra);
                $s->update(['gateways' => $saved]);
            }
        } catch (GatewayException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => __('Connected. These keys work.')]);
    }

    // ── Guest-facing ─────────────────────────────────────────────────────────

    public function publicPay(Request $request, $token)
    {
        $row = Invoice::withoutVendorScope()->where('pay_token', $token)->with(['items', 'payments'])->firstOrFail();
        // The first time someone other than the vendor opens it, note when.
        $isOwner = Auth::check() && (int) resolve_current_vendor_id() === (int) $row->vendor_id;
        if (!$isOwner && !$row->viewed_at && $row->status !== 'draft') {
            $row->forceFill(['viewed_at' => now()])->saveQuietly();
        }

        $settings = Setting::forVendor((int) $row->vendor_id);

        return view('TourPay::public.pay', [
            'row' => $row, 'company' => $this->companyName($row), 'preview' => $isOwner,
            'gateways' => $row->type === 'invoice' ? $settings->enabledGateways($row->currency) : [], 'bankEnabled' => (bool) $settings->bank_enabled,
            'amounts' => $this->online->amounts($row), 'schedule' => $this->book->schedule($row),
            'pending' => $row->pendingPayments()->get(),
        ]);
    }

    /** A guest accepts or declines a quotation from the pay page. */
    public function answerQuotation(Request $request, $token, string $answer)
    {
        $row = Invoice::withoutVendorScope()->where('pay_token', $token)->firstOrFail();
        try {
            $this->book->answerQuotation($row, $answer === 'accept');
        } catch (InvoiceRuleException $e) {
            return redirect()->route('tourpay.pay', $token)->with('error', $e->getMessage());
        }

        return redirect()->route('tourpay.pay', $token)->with('success', $answer === 'accept' ? __('Thank you. The quotation is accepted and the business has been told.') : __('The quotation was declined.'));
    }

    /** The guest chose a gateway: send them to it. */
    public function payOnline(Request $request, $token)
    {
        $row = Invoice::withoutVendorScope()->where('pay_token', $token)->firstOrFail();
        $d = $request->validate(['gateway' => ['required', 'string', 'max:16'], 'choice' => ['nullable', Rule::in(['balance', 'next'])]]);
        try {
            $url = $this->online->begin($row, $d['gateway'], $d['choice'] ?? 'balance', route('tourpay.return', [$token, $d['gateway']]), route('tourpay.pay', $token));
        } catch (GatewayException $e) {
            return redirect()->route('tourpay.pay', $token)->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /** The guest came back from the gateway. Ask the gateway, and record the payment only if it says so. */
    public function payReturn(Request $request, $token, string $gateway)
    {
        $row = Invoice::withoutVendorScope()->where('pay_token', $token)->firstOrFail();
        abort_unless(isset(Gateway::LABELS[$gateway]), 404);
        $ref = Gateway::make($gateway, [])->referenceFrom($request->query());
        $attempt = Attempt::withoutVendorScope()->where('gateway', $gateway)->where('invoice_id', $row->id)
            ->when($ref, fn ($q) => $q->where('reference', (string) $ref), fn ($q) => $q->where('status', 'started')->orderByDesc('id'))->first();
        if (!$attempt) {
            return redirect()->route('tourpay.pay', $token)->with('error', __('We could not match that payment. If you were charged, contact the business with your reference.'));
        }
        try {
            $result = $this->online->settle($attempt);
        } catch (\Throwable $e) {
            \Log::warning('tourpay_return_failed', ['attempt' => $attempt->id, 'error' => $e->getMessage()]);
            $result = 'pending';
        }

        return redirect()->route('tourpay.pay', $token)->with($result === 'paid' ? 'success' : ($result === 'failed' ? 'error' : 'success'),
            $result === 'paid' ? __('Payment received. Thank you!') : ($result === 'failed' ? __('The payment did not go through. You have not been charged.') : __('Your payment is being confirmed. This page updates when it is done.')));
    }

    /**
     * A gateway telling us a payment moved (Pesapal's notification, Paynow's result, Selcom's webhook). Nothing in the message is
     * believed: it only points at one of OUR open attempts, and the gateway is then asked what really happened.
     */
    public function notify(Request $request, string $gateway)
    {
        abort_unless(isset(Gateway::LABELS[$gateway]), 404);
        $ref = Gateway::make($gateway, [])->referenceFrom($request->all() + $request->query());
        if ($ref && ($a = Attempt::withoutVendorScope()->where('gateway', $gateway)->where('reference', (string) $ref)->first())) {
            try {
                $this->online->settle($a);
            } catch (\Throwable $e) {
                \Log::warning('tourpay_notify_failed', ['gateway' => $gateway, 'attempt' => $a->id, 'error' => $e->getMessage()]);
            }
        }

        return response('OK', 200);
    }

    /** "I paid by bank transfer": recorded as waiting, and the vendor confirms it. */
    public function reportTransfer(Request $request, $token)
    {
        $row = Invoice::withoutVendorScope()->where('pay_token', $token)->firstOrFail();
        $d = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'], 'reference' => ['required', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:500'], 'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);
        $path = $request->file('proof') ? $request->file('proof')->store('tourpay-proofs/' . $row->vendor_id, 'local') : null;
        try {
            $this->book->reportTransfer($row, (float) $d['amount'], $d['reference'], $path, $d['notes'] ?? null);
        } catch (InvoiceRuleException $e) {
            return redirect()->route('tourpay.pay', $token)->with('error', $e->getMessage());
        }

        return redirect()->route('tourpay.pay', $token)->with('success', __('Thank you. The business will confirm your payment once it reaches their account.'));
    }

    // ── From a booking ───────────────────────────────────────────────────────

    /** An invoice for a booking: what was bought, add-ons, fees, and what has been paid. Asking again opens the same one. */
    public function createFromBooking(Booking $booking)
    {
        if ((int) $booking->vendor_id !== $this->vendorId() && !auth()->user()->hasPermission('dashboard_access')) {
            abort(403);
        }
        [$invoice, $made] = app(InvoiceFromBooking::class)->make($booking);

        return redirect()->route($made ? 'tourpay.vendor.edit' : 'tourpay.vendor.view', $invoice->id)
            ->with('success', $made ? __('Invoice :n created from booking :code.', ['n' => $invoice->invoice_number, 'code' => $booking->code ?: $booking->id]) : __('This booking already has invoice :n.', ['n' => $invoice->invoice_number]));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function vendorId(): int
    {
        return (int) resolve_current_vendor_id();
    }

    private function companyName(Invoice $row): string
    {
        $v = \App\User::find($row->vendor_id);

        return (string) ($v?->business_name ?: $v?->name ?: setting_item('site_title', 'Tsoka'));
    }

    public function streamPdf(Invoice $row)
    {
        $dompdf = $this->dompdf($row);
        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $row->invoice_number . '.pdf"',
        ]);
    }

    private function buildPdfHtml(Invoice $row): string
    {
        $template = max(1, min(5, (int) $row->template));
        return view('TourPay::pdf.template' . $template, ['row' => $row, 'company' => $this->companyName($row)])->render();
    }

    private function dompdf(Invoice $row): Dompdf
    {
        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($this->buildPdfHtml($row));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf;
    }

    private function buildPdfString(Invoice $row): string
    {
        return $this->dompdf($row)->output();
    }
}
