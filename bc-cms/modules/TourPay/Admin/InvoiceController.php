<?php

namespace Modules\TourPay\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\LedgerEntry;
use Modules\TourPay\Models\Payment;

/**
 * Staff view of TourPay across every business (read-only), in the admin shell. Every query says explicitly that it is platform-wide,
 * so it does not depend on which business the signed-in staff member may also run.
 */
class InvoiceController extends AdminController
{
    private const STATUSES = ['draft', 'sent', 'part_paid', 'paid', 'void', 'credited', 'accepted', 'declined', 'expired'];

    public function index(Request $request)
    {
        $this->checkPermission('tourpay_view');
        $q = Invoice::withoutVendorScope()->with('vendorOwner:id,name,business_name,email')->orderByDesc('id');
        if ($s = trim((string) $request->query('s'))) {
            $like = '%' . addcslashes($s, '%_\\') . '%';
            $q->where(fn ($w) => $w->where('invoice_number', 'like', $like)->orWhere('client_name', 'like', $like)->orWhere('client_email', 'like', $like));
        }
        if (in_array($request->query('status'), self::STATUSES, true)) {
            $q->where('status', $request->query('status'));
        }
        if (in_array($request->query('type'), ['invoice', 'quotation', 'credit_note'], true)) {
            $q->where('type', $request->query('type'));
        }
        if ($request->filled('vendor')) {
            $q->where('vendor_id', (int) $request->query('vendor'));
        }

        $totals = Invoice::withoutVendorScope()->where('type', 'invoice')->whereNotIn('status', ['draft', 'void'])
            ->selectRaw('currency, COUNT(*) AS n, SUM(total - credit_total) AS billed, SUM(amount_paid) AS paid')->groupBy('currency')->orderByDesc('billed')->get();

        return view('TourPay::admin.index', [
            'rows' => $q->paginate(25)->withQueryString(), 'totals' => $totals, 'statuses' => self::STATUSES, 'page_title' => __('TourPay'),
            'businesses' => Invoice::withoutVendorScope()->distinct()->count('vendor_id'),
            'ledgerRows' => LedgerEntry::withoutVendorScope()->count(),
        ]);
    }

    public function view($id)
    {
        $this->checkPermission('tourpay_view');
        $row = Invoice::withoutVendorScope()->with(['items', 'vendorOwner:id,name,business_name,email'])->findOrFail($id);

        return view('TourPay::admin.view', [
            'row' => $row, 'page_title' => $row->invoice_number,
            'payments' => Payment::withoutVendorScope()->where('invoice_id', $row->id)->orderBy('paid_at')->orderBy('id')->get(),
        ]);
    }
}
