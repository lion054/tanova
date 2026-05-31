<?php
namespace Modules\TourPay\Controllers;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\FrontendController;
use Modules\TourPay\Emails\InvoiceEmail;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\InvoiceItem;

class InvoiceController extends FrontendController
{
    public function index(Request $request)
    {
        $base  = Invoice::where('author_id', Auth::id());
        $query = clone $base;

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('s'))      $query->where(function ($q) use ($request) {
            $q->where('invoice_number', 'like', '%'.$request->s.'%')
              ->orWhere('client_name', 'like', '%'.$request->s.'%')
              ->orWhere('title', 'like', '%'.$request->s.'%');
        });
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('currency'))  $query->where('currency', $request->currency);

        $rows = $query->orderBy('id', 'desc')->paginate(20);

        $outstandingByCur = (clone $base)->whereIn('status', ['sent','draft'])
            ->selectRaw('currency, SUM(total) as total, COUNT(*) as cnt')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->get();

        $paidMonthByCur = (clone $base)->where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->selectRaw('currency, SUM(total) as total')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->get();

        $stats = [
            'outstanding_by_cur' => $outstandingByCur,
            'paid_month_by_cur'  => $paidMonthByCur,
            'drafts'             => (clone $base)->where('status', 'draft')->count(),
            'total'              => (clone $base)->count(),
            'currencies'         => (clone $base)->distinct()->pluck('currency')->filter()->sort()->values(),
        ];

        return view('TourPay::frontend.index', [
            'rows'       => $rows,
            'stats'      => $stats,
            'page_title' => __('TourPay'),
        ]);
    }

    public function create(Request $request)
    {
        $row  = new Invoice(['currency' => setting_item('tourpay_default_currency', 'ZAR'), 'template' => 1]);
        $type = $request->input('type', 'invoice');
        return view('TourPay::frontend.form', [
            'row'        => $row,
            'type'       => $type,
            'page_title' => $type === 'quotation' ? __('New Quotation') : __('New Invoice'),
        ]);
    }

    public function edit(Request $request, $id)
    {
        $row = Invoice::where('author_id', Auth::id())->with('items')->findOrFail($id);
        return view('TourPay::frontend.form', [
            'row'        => $row,
            'type'       => $row->type,
            'page_title' => __('Edit: :n', ['n' => $row->invoice_number]),
        ]);
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'type'        => 'required|in:invoice,quotation',
            'client_name' => 'required|string|max:255',
            'currency'    => 'required|string|max:10',
        ]);

        if ($id == -1) {
            $row = new Invoice();
            $row->author_id   = Auth::id();
            $row->create_user = Auth::id();
            $row->invoice_number = Invoice::generateNumber(Auth::id());
            $row->pay_token   = Str::uuid()->toString();
        } else {
            $row = Invoice::where('author_id', Auth::id())->findOrFail($id);
            $row->update_user = Auth::id();
        }

        $fields = [
            'type','status','client_user_id','client_name','client_email','client_phone',
            'client_country','client_address','title','description','currency',
            'tax_rate','issue_date','due_date','valid_days','template','notes','payment_terms',
        ];
        foreach ($fields as $f) {
            $row->$f = $request->input($f);
        }

        // Banking details from request or settings
        $row->banking_details = $request->input('banking_details') ?? null;

        // Items
        $items      = $request->input('items', []);
        $subtotal   = 0;
        foreach ($items as &$item) {
            $item['total'] = round(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2);
            $subtotal     += $item['total'];
        }
        unset($item);

        // Prices are tax-inclusive — extract VAT from within the total
        $taxRate         = (float) $row->tax_rate;
        $total           = $subtotal; // subtotal here is the sum of items (tax-inclusive)
        $taxAmount       = round($total - ($total / (1 + $taxRate / 100)), 2);
        $exVat           = round($total - $taxAmount, 2);
        $row->subtotal   = $exVat;
        $row->tax_amount = $taxAmount;
        $row->total      = $total;

        $row->save();

        // Sync items
        InvoiceItem::where('invoice_id', $row->id)->delete();
        foreach ($items as $i => $item) {
            if (empty($item['name'])) continue;
            InvoiceItem::create([
                'invoice_id' => $row->id,
                'sort_order' => $i,
                'name'       => $item['name'],
                'description'=> $item['description'] ?? '',
                'quantity'   => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'total'      => $item['total'],
            ]);
        }

        if ($request->input('action') === 'send') {
            $this->doSendEmail($row);
            return redirect(route('tourpay.vendor.view', $row->id))
                ->with('success', __('Invoice sent.'));
        }

        return redirect(route('tourpay.vendor.view', $row->id))
            ->with('success', __('Saved.'));
    }

    public function delete($id)
    {
        $row = Invoice::where('author_id', Auth::id())->findOrFail($id);
        $row->delete();
        return redirect(route('tourpay.vendor.index'))->with('success', __('Deleted.'));
    }

    public function view($id)
    {
        $row = Invoice::where('author_id', Auth::id())->with('items')->findOrFail($id);
        return view('TourPay::frontend.view', [
            'row'        => $row,
            'page_title' => $row->invoice_number,
        ]);
    }

    public function pdf($id)
    {
        $row  = Invoice::where('author_id', Auth::id())->with('items')->findOrFail($id);
        return $this->streamPdf($row);
    }

    public function sendEmail(Request $request, $id)
    {
        $request->validate(['email' => 'required|email']);
        $row = Invoice::where('author_id', Auth::id())->with('items')->findOrFail($id);
        $this->doSendEmail($row, $request->input('email'));
        return back()->with('success', __('Invoice emailed to :email', ['email' => $request->input('email')]));
    }

    public function sendWhatsApp(Request $request, $id)
    {
        $request->validate(['phone' => 'required|string']);

        $row = Invoice::where('author_id', Auth::id())->findOrFail($id);

        $apiKey = setting_item('vonage_api_key');
        $apiSecret = setting_item('vonage_api_secret');
        $from = setting_item('vonage_whatsapp_from');

        if (!$apiKey || !$apiSecret || !$from) {
            return response()->json([
                'error' => __('Vonage credentials not configured. Add vonage_api_key, vonage_api_secret and vonage_whatsapp_from in Settings.'),
            ], 422);
        }

        // Strip everything except digits (Vonage expects no leading +)
        $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));

        $company = setting_item('site_title', 'Tsoka Travel');
        $payUrl  = route('tourpay.pay', $row->pay_token);
        $text    = "Hi {$row->client_name}, please find your " . ucfirst($row->type) . " {$row->invoice_number} from {$company}.\n\n"
                 . "Amount: {$row->currency} " . number_format($row->total, 2) . "\n\n"
                 . "View & pay: {$payUrl}";

        try {
            $client = new \GuzzleHttp\Client();
            $client->post('https://api.nexmo.com/v1/messages', [
                'auth'    => [$apiKey, $apiSecret],
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'json'    => [
                    'channel'      => 'whatsapp',
                    'message_type' => 'text',
                    'to'           => $phone,
                    'from'         => $from,
                    'text'         => $text,
                ],
            ]);
            return response()->json(['success' => true]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            return response()->json(['error' => $body], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function shareLink($id)
    {
        $row = Invoice::where('author_id', Auth::id())->findOrFail($id);
        return response()->json(['url' => route('tourpay.pay', $row->pay_token)]);
    }

    public function markPaid(Request $request, $id)
    {
        $row = Invoice::where('author_id', Auth::id())->findOrFail($id);
        $row->status = 'paid';
        $row->save();

        // Sync payment status to the linked Tanova booking
        if ($row->tanova_trip_id) {
            $trip = $row->tanovaTrip;
            if ($trip?->booking_id) {
                Booking::where('id', $trip->booking_id)->update(['status' => Booking::COMPLETED]);
            }
        }

        return back()->with('success', __(':n marked as paid.', ['n' => $row->invoice_number]));
    }

    public function publicPay($token)
    {
        $row = Invoice::where('pay_token', $token)->with('items')->firstOrFail();
        return view('TourPay::public.pay', ['row' => $row]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function doSendEmail(Invoice $row, ?string $toEmail = null)
    {
        $email = $toEmail ?: $row->client_email;
        if (empty($email)) return;
        try {
            $pdf = $this->buildPdfString($row);
            Mail::to($email)->send(new InvoiceEmail($row, $pdf));
            if ($row->status === 'draft') {
                $row->status = 'sent';
                $row->save();
            }
        } catch (\Throwable $e) {
            // swallow; mail config may not be set
        }
    }

    public function streamPdf(Invoice $row)
    {
        $html     = $this->buildPdfHtml($row);
        $dompdf   = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = $row->invoice_number . '.pdf';
        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Create a TourPay invoice pre-filled from a regular Booking record */
    public function createFromBooking(\Modules\Booking\Models\Booking $booking)
    {
        if ($booking->vendor_id !== Auth::id() && !auth()->user()->hasPermission('dashboard_access')) {
            abort(403);
        }

        $serviceTitle = null;
        if ($booking->object_model === 'tanova_trip') {
            $trip = \Pro\Tanova\Models\TanovaTrip::find($booking->object_id);
            $serviceTitle = $trip?->title ?? "Trip #{$booking->object_id}";
        } else {
            $serviceTitle = $booking->service?->title ?? "Booking #{$booking->id}";
        }

        $invoice = new Invoice();
        $invoice->type           = 'invoice';
        $invoice->status         = 'draft';
        $invoice->author_id      = Auth::id();
        $invoice->create_user    = Auth::id();
        $invoice->invoice_number = Invoice::generateNumber(Auth::id());
        $invoice->pay_token      = Str::uuid()->toString();
        $invoice->client_name    = trim($booking->first_name . ' ' . $booking->last_name);
        $invoice->client_email   = $booking->email ?? '';
        $invoice->client_phone   = $booking->phone ?? '';
        $invoice->title          = $serviceTitle;
        $invoice->currency       = $booking->currency ?? setting_item('tourpay_default_currency', 'USD');
        $invoice->issue_date     = now()->toDateString();
        $invoice->due_date       = $booking->start_date instanceof \Carbon\Carbon
                                    ? $booking->start_date->toDateString()
                                    : ($booking->start_date ? date('Y-m-d', strtotime($booking->start_date)) : null);
        $invoice->subtotal       = $booking->total;
        $invoice->tax_rate       = 0;
        $invoice->tax_amount     = 0;
        $invoice->total          = $booking->total;
        $invoice->save();

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'sort_order'  => 0,
            'name'        => $serviceTitle,
            'description' => implode(' · ', array_filter([
                $booking->start_date ? 'From ' . date('d M Y', strtotime($booking->start_date)) : null,
                $booking->end_date   ? 'to '   . date('d M Y', strtotime($booking->end_date))   : null,
                $booking->total_guests ? "{$booking->total_guests} guest(s)" : null,
            ])),
            'quantity'    => 1,
            'unit_price'  => $booking->total,
            'total'       => $booking->total,
        ]);

        return redirect()->route('tourpay.vendor.edit', $invoice->id)
            ->with('success', "Invoice {$invoice->invoice_number} created from booking #{$booking->id}.");
    }

    private function buildPdfHtml(Invoice $row): string
    {
        $template = max(1, min(5, (int) $row->template));
        return view('TourPay::pdf.template' . $template, ['row' => $row])->render();
    }

    private function buildPdfString(Invoice $row): string
    {
        $html   = $this->buildPdfHtml($row);
        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}
