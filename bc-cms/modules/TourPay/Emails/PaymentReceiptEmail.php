<?php
namespace Modules\TourPay\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\Payment;

class PaymentReceiptEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice, public Payment $payment) {}

    public function build()
    {
        $v = \App\User::find($this->invoice->vendor_id);
        $company = $v?->business_name ?: $v?->name ?: setting_item('site_title', 'Tsoka');

        return $this->subject(__('Payment received: :n', ['n' => $this->invoice->invoice_number]))
            ->view('TourPay::emails.receipt')
            ->with(['invoice' => $this->invoice, 'payment' => $this->payment, 'company' => $company]);
    }
}
