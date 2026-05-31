<?php
namespace Modules\TourPay\Admin;

use App\Http\Controllers\Controller;
use Modules\TourPay\Models\Invoice;

class InvoiceController extends Controller
{
    public function index()
    {
        $rows = Invoice::with('author')->orderBy('id', 'desc')->paginate(30);
        return view('TourPay::frontend.index', ['rows' => $rows, 'page_title' => 'TourPay — All Invoices', 'admin' => true]);
    }

    public function view($id)
    {
        $row = Invoice::with('items')->findOrFail($id);
        return view('TourPay::frontend.view', ['row' => $row, 'page_title' => $row->invoice_number, 'admin' => true]);
    }
}
