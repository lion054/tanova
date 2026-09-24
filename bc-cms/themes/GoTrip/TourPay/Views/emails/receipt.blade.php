<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #1a1a1a; background: #f5f5f5; margin: 0; padding: 0; }
.wrap { max-width: 560px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.topbar { height: 4px; background: #16a34a; }
.body { padding: 36px 40px; }
.greeting { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
.para { font-size: 13px; color: #555; line-height: 1.7; margin-bottom: 16px; }
.box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 20px; margin: 24px 0; }
.row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; }
.row .k { color: #888; } .row .v { font-weight: 600; }
.big { font-size: 20px; font-weight: 800; color: #16a34a; }
.cta { display: block; padding: 14px; border-radius: 5px; text-align: center; background: #0a0a0a; color: #fff !important; font-size: 14px; font-weight: 700; text-decoration: none; margin: 24px 0 8px; }
.footer { background: #fafafa; border-top: 1px solid #f0f0f0; padding: 20px 40px; text-align: center; font-size: 11px; color: #bbb; }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar"></div>
    <div class="body">
        <div class="greeting">{{ __("Hi :name,", ['name' => $invoice->client_name]) }}</div>
        <p class="para">{{ __("Thank you. :company has received your payment.", ['company' => $company]) }}</p>
        <div class="box">
            <div class="row"><span class="k">{{ __("Amount received") }}</span><span class="big">{{ $invoice->currency }} {{ number_format($payment->amount, 2) }}</span></div>
            <div class="row"><span class="k">{{ __("Invoice") }}</span><span class="v">{{ $invoice->invoice_number }}</span></div>
            <div class="row"><span class="k">{{ __("Date") }}</span><span class="v">{{ $payment->paid_at->format('d M Y') }}</span></div>
            <div class="row"><span class="k">{{ __("Method") }}</span><span class="v">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span></div>
            @if($payment->reference)<div class="row"><span class="k">{{ __("Reference") }}</span><span class="v">{{ $payment->reference }}</span></div>@endif
            <div class="row"><span class="k">{{ $invoice->balance() > 0 ? __("Still to pay") : __("Status") }}</span><span class="v">{{ $invoice->balance() > 0 ? $invoice->currency.' '.number_format($invoice->balance(), 2) : __("Paid in full") }}</span></div>
        </div>
        <a href="{{ route('tourpay.pay', $invoice->pay_token) }}" class="cta">{{ __("View your invoice") }}</a>
    </div>
    <div class="footer">{{ $company }}</div>
</div>
</body>
</html>
