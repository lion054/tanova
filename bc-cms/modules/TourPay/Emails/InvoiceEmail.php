<?php
namespace Modules\TourPay\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\TourPay\Models\Invoice;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    protected string $pdfData;

    public function __construct(Invoice $invoice, string $pdfData)
    {
        $this->invoice = $invoice;
        $this->pdfData = $pdfData;
    }

    public function build()
    {
        $siteName = setting_item('site_title', 'Tsoka');
        $subject  = $this->invoice->type === 'quotation'
            ? __('Quotation :n from :s', ['n' => $this->invoice->invoice_number, 's' => $siteName])
            : __('Invoice :n from :s', ['n' => $this->invoice->invoice_number, 's' => $siteName]);

        return $this->subject($subject)
            ->view('TourPay::emails.invoice')
            ->with(['invoice' => $this->invoice])
            ->attachData($this->pdfData, $this->invoice->invoice_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
