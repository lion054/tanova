@php
    // The business that issued it, not the platform.
    $company = ($company ?? null) ?: setting_item('site_title', 'Tsoka Travel');
    $author  = \App\User::find($row->vendor_id ?: $row->author_id);
    $logoId  = $author?->avatar_id ?: setting_item('logo_id');
    $logoUrl = $logoId ? get_file_url($logoId) : null;
    $banking = $row->banking_details ?: (\Modules\TourPay\Models\Setting::forVendor((int) $row->vendor_id)->banking_details ?? []);
    $isInvoice = $row->type === 'invoice';
    $excl    = ($row->tax_mode ?? 'inclusive') === 'exclusive';
    $balance = max(0, $row->balance());
    $open    = $isInvoice && !in_array($row->status, ['paid', 'void', 'draft']) && $balance > 0;
    $onlineEnabled = !empty($gateways);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $row->invoice_number }} — {{ $company }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #1a1a1a; background: #f5f5f5; }
.pay-wrap { min-height: 100vh; padding: 40px 20px 60px; }
.pay-header {
    display: flex; align-items: center; justify-content: space-between;
    max-width: 780px; margin: 0 auto 32px;
}
.pay-logo img  { max-height: 36px; }
.pay-logo-text { font-size: 16px; font-weight: 700; color: #111; }
.pay-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px;
    border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; background: #f0f0f0; color: #888;
}
.pay-badge.paid     { background: #e8f5e9; color: #2e7d32; }
.pay-badge.part_paid { background: #fff7ed; color: #c2410c; }
.pay-badge.overdue, .pay-badge.void, .pay-badge.declined { background: #fff1f2; color: #e11d48; }
.pay-flash { max-width: 780px; margin: 0 auto 16px; padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; }
.pay-flash.ok { background: #e8f5e9; color: #2e7d32; }
.pay-flash.err { background: #fff1f2; color: #e11d48; }
.pay-preview { max-width: 780px; margin: 0 auto 16px; padding: 10px 16px; border-radius: 8px; font-size: 12px; background: #0a0a0a; color: #fff; }
.pay-pays { max-width: 780px; margin: 0 auto 20px; background: #fff; border-radius: 10px; padding: 16px 20px; box-shadow: 0 2px 12px rgba(0,0,0,.06); font-size: 13px; }
.pay-pays h4 { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #888; margin-bottom: 8px; }
.pay-pays .r { display: flex; justify-content: space-between; padding: 6px 0; border-top: 1px solid #f3f3f3; }
.pay-pays .r:first-of-type { border-top: 0; }
.pay-btns { max-width: 780px; margin: 0 auto 20px; display: flex; gap: 12px; }
.pay-btns form { flex: 1; }
.pay-btns button { width: 100%; padding: 14px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: 2px solid #0a0a0a; background: #0a0a0a; color: #fff; }
.pay-btns button.ghost { background: #fff; color: #0a0a0a; }
.pay-badge.accepted { background: #e8f5e9; color: #2e7d32; }
.pay-badge.sent     { background: #e8f4ff; color: #1a73e8; }

/* Invoice card */
.pay-card {
    max-width: 780px; margin: 0 auto 28px; background: #fff;
    border-radius: 10px; overflow: hidden; box-shadow: 0 2px 16px rgba(0,0,0,.08);
}
/* Use template1 styles embedded */
.inv-doc { padding: 40px 44px; }
.inv-hd { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid #c8a96e; }
.inv-logo img { max-height: 42px; }
.inv-logo-text { font-size: 20px; font-weight: 700; color: #1a1a1a; }
.inv-hd-right { text-align: right; }
.inv-type   { font-size: 26px; font-weight: 700; color: #c8a96e; text-transform: uppercase; }
.inv-number { font-size: 12px; color: #888; margin-top: 4px; }
.inv-meta { display: flex; gap: 32px; margin-bottom: 24px; }
.inv-meta-item {}
.inv-meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #c8a96e; margin-bottom: 3px; }
.inv-meta-value { font-size: 12px; color: #333; line-height: 1.5; }
.inv-title { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
.inv-desc  { font-size: 12px; color: #666; line-height: 1.5; margin-bottom: 20px; }
.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.inv-table thead th { background: #c8a96e; color: #fff; padding: 8px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; text-align: left; }
.inv-table thead th:nth-child(2),.inv-table thead th:nth-child(3),.inv-table thead th:last-child { text-align: right; }
.inv-table tbody tr { border-bottom: 1px solid #f0ead8; }
.inv-table tbody td { padding: 9px 12px; font-size: 12px; }
.inv-table tbody td:nth-child(2),.inv-table tbody td:nth-child(3),.inv-table tbody td:last-child { text-align: right; }
.item-sub { font-size: 10px; color: #aaa; margin-top: 2px; }
.inv-totals { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.inv-totals-inner { width: 260px; }
.inv-tot-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f0ead8; font-size: 12px; color: #666; }
.inv-tot-row span:last-child { font-weight: 600; color: #1a1a1a; }
.inv-tot-grand { background: #c8a96e; border-radius: 3px; padding: 8px 12px; display: flex; justify-content: space-between; margin-top: 6px; }
.inv-tot-grand span { color: #fff; font-weight: 700; }
.inv-tot-grand span:last-child { font-size: 16px; font-weight: 800; }
.inv-notes { font-size: 11px; color: #666; line-height: 1.6; margin-top: 8px; }

/* Payment section */
.pay-section {
    max-width: 780px; margin: 0 auto;
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}
.pay-option {
    background: #fff; border-radius: 10px; padding: 28px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 2px solid transparent;
}
.pay-option-head { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.pay-option-icon { width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.pay-option-title { font-size: 15px; font-weight: 700; color: #0a0a0a; }
.pay-option-sub   { font-size: 11px; color: #888; margin-top: 1px; }
.pay-now-btn {
    display: block; width: 100%; padding: 13px; border-radius: 6px;
    font-size: 14px; font-weight: 700; text-align: center;
    background: #0a0a0a; color: #fff !important; text-decoration: none;
    border: none; cursor: pointer; margin-top: 16px; transition: opacity .15s;
}
.pay-now-btn:hover { opacity: .85; }
.pay-bank-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f5f5f5; font-size: 12px; }
.pay-bank-row .k { color: #888; }
.pay-bank-row .v { font-weight: 600; }
.pay-ref-note { margin-top: 12px; font-size: 11px; color: #888; line-height: 1.5; background: #fafafa; border-radius: 4px; padding: 10px 12px; }

@media (max-width: 600px) {
    .pay-section { grid-template-columns: 1fr; }
    .inv-meta { flex-wrap: wrap; }
}
</style>
</head>
<body>
<div class="pay-wrap">

    @if($preview ?? false)<div class="pay-preview">{{ __('This is how your client sees it. Opening it yourself does not count as viewed.') }}</div>@endif
    @if(session('success'))<div class="pay-flash ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="pay-flash err">{{ session('error') }}</div>@endif
    {{-- Header --}}
    <div class="pay-header">
        <div class="pay-logo">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $company }}">
            @else<div class="pay-logo-text">{{ $company }}</div>@endif
        </div>
        <div class="pay-badge {{ $row->display_status }}">{{ $row->status_label }}</div>
    </div>

    {{-- Invoice card --}}
    <div class="pay-card">
        <div class="inv-doc">
            <div class="inv-hd">
                <div class="inv-logo">
                    @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $company }}">
                    @else<div class="inv-logo-text">{{ $company }}</div>@endif
                </div>
                <div class="inv-hd-right">
                    <div class="inv-type">{{ strtoupper($row->type) }}</div>
                    <div class="inv-number">{{ $row->invoice_number }}</div>
                </div>
            </div>
            <div class="inv-meta">
                <div class="inv-meta-item">
                    <div class="inv-meta-label">{{ __("Billed To") }}</div>
                    <div class="inv-meta-value"><strong>{{ $row->client_name }}</strong>
                    @if($row->client_email)<br>{{ $row->client_email }}@endif
                    @if($row->client_phone)<br>{{ $row->client_phone }}@endif</div>
                </div>
                <div class="inv-meta-item">
                    <div class="inv-meta-label">{{ __("Date") }}</div>
                    <div class="inv-meta-value">{{ $row->issue_date ? $row->issue_date->format('d M Y') : $row->created_at->format('d M Y') }}</div>
                </div>
                @if($row->due_date)
                <div class="inv-meta-item">
                    <div class="inv-meta-label">{{ __("Due") }}</div>
                    <div class="inv-meta-value">{{ $row->due_date->format('d M Y') }}</div>
                </div>
                @endif
                <div class="inv-meta-item">
                    <div class="inv-meta-label">{{ $isInvoice ? __("Amount Due") : __("Total") }}</div>
                    <div class="inv-meta-value" style="font-size:16px;font-weight:800;color:#c8a96e;">{{ $row->currency }} {{ number_format($isInvoice ? $balance : $row->total, 2) }}</div>
                </div>
            </div>

            @if($row->title)<div class="inv-title">{{ $row->title }}</div>@endif
            @if($row->description)<div class="inv-desc">{{ $row->description }}</div>@endif

            <table class="inv-table">
                <thead><tr>
                    <th>{{ __("Description") }}</th>
                    <th>{{ __("Qty") }}</th>
                    <th>{{ __("Unit") }}</th>
                    <th>{{ __("Total") }}</th>
                </tr></thead>
                <tbody>
                @foreach($row->items as $item)
                <tr>
                    <td>{{ $item->name }}
                        @if($item->description)<div class="item-sub">{{ $item->description }}</div>@endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>

            <div class="inv-totals">
                <div class="inv-totals-inner">
                    @if($row->discount > 0 || ($excl && $row->tax_rate > 0))
                    <div class="inv-tot-row"><span>{{ __("Subtotal") }}</span><span>{{ $row->currency }} {{ number_format($excl ? $row->subtotal : $row->subtotal + $row->tax_amount + $row->discount, 2) }}</span></div>
                    @endif
                    @if($row->discount > 0)
                    <div class="inv-tot-row"><span>{{ __("Discount") }}</span><span>− {{ $row->currency }} {{ number_format($row->discount, 2) }}</span></div>
                    @endif
                    @if($row->tax_rate > 0)
                    @foreach(($row->tax_lines ?: [['name' => __('VAT'), 'rate' => $row->tax_rate + 0, 'amount' => $row->tax_amount]]) as $tl)
                    <div class="inv-tot-row"><span>{{ $tl['name'] }} ({{ $tl['rate'] + 0 }}%{{ $excl ? '' : ' '.__('incl.') }})</span><span>{{ $row->currency }} {{ number_format($tl['amount'], 2) }}</span></div>
                    @endforeach
                    @endif
                    <div class="inv-tot-grand">
                        <span>{{ __("TOTAL") }}</span>
                        <span>{{ $row->currency }} {{ number_format($row->total, 2) }}</span>
                    </div>
                    @if($isInvoice && $row->credit_total > 0)
                    <div class="inv-tot-row"><span>{{ __("Credit notes") }}</span><span>− {{ $row->currency }} {{ number_format($row->credit_total, 2) }}</span></div>
                    @endif
                    @if($isInvoice && ($row->amount_paid != 0 || $row->credit_total > 0))
                    <div class="inv-tot-row"><span>{{ __("Paid") }}</span><span>− {{ $row->currency }} {{ number_format($row->amount_paid, 2) }}</span></div>
                    <div class="inv-tot-row" style="font-weight:800;"><span>{{ $balance <= 0 ? __("Paid in full") : __("Balance due") }}</span><span>{{ $row->currency }} {{ number_format($balance, 2) }}</span></div>
                    @endif
                </div>
            </div>

            @if($row->payment_terms)
            <div class="inv-notes"><strong>{{ __("Payment Terms:") }}</strong> {{ $row->payment_terms }}</div>
            @endif
        </div>
    </div>

    @if($isInvoice && count($schedule) && $row->status !== 'void')
    <div class="pay-pays">
        <h4>{{ __('Payment schedule') }}</h4>
        @foreach($schedule as $it)
        <div class="r"><span>{{ $it['label'] }} <span style="color:{{ $it['late'] ? '#e11d48' : '#999' }}">· {{ __('due :d', ['d' => $it['due_date']->format('d M Y')]) }}{{ $it['late'] ? ' · '.__('late') : '' }}</span></span><strong style="color:{{ $it['remaining'] <= 0.004 ? '#16a34a' : '#0a0a0a' }}">{{ $it['remaining'] <= 0.004 ? '✓ ' : '' }}{{ $row->currency }} {{ number_format($it['amount'], 2) }}</strong></div>
        @endforeach
    </div>
    @endif

    @if($isInvoice && $pending->count())
    <div class="pay-pays" style="background:#fffbeb;">
        <h4>{{ __('Waiting for the business to confirm') }}</h4>
        @foreach($pending as $p)
        <div class="r"><span>{{ $p->reference }}</span><strong>{{ $row->currency }} {{ number_format($p->amount, 2) }}</strong></div>
        @endforeach
    </div>
    @endif

    @if($isInvoice && $row->payments->where('status', 'confirmed')->count())
    <div class="pay-pays">
        <h4>{{ __('Payments received') }}</h4>
        @foreach($row->payments->where('status', 'confirmed') as $p)
        <div class="r"><span>{{ $p->paid_at->format('d M Y') }} · {{ ucfirst(str_replace('_', ' ', $p->method)) }}@if($p->reference) · {{ $p->reference }}@endif</span><strong>{{ $row->currency }} {{ number_format($p->amount, 2) }}</strong></div>
        @endforeach
    </div>
    @endif

    @if($row->type === 'credit_note')
        <div style="max-width:780px;margin:0 auto;text-align:center;padding:20px;background:#f0f9ff;border-radius:8px;color:#0369a1;font-weight:700;">{{ __('This credit note reduces what you owe on the invoice it refers to.') }}</div>
    @elseif(!$isInvoice)
        {{-- Quotation: accept or decline --}}
        @if($row->status === 'sent' && !($preview ?? false))
        <div class="pay-btns">
            <form method="POST" action="{{ route('tourpay.answer', [$row->pay_token, 'accept']) }}">@csrf<button type="submit">✓ {{ __('Accept this quotation') }}</button></form>
            <form method="POST" action="{{ route('tourpay.answer', [$row->pay_token, 'decline']) }}" onsubmit="return confirm('{{ __('Decline this quotation?') }}')">@csrf<button type="submit" class="ghost">{{ __('Decline') }}</button></form>
        </div>
        @elseif($row->status === 'accepted')
        <div style="max-width:780px;margin:0 auto;text-align:center;padding:24px;background:#e8f5e9;border-radius:8px;color:#2e7d32;font-weight:700;">✓ {{ __('Accepted. The business will send you an invoice.') }}</div>
        @elseif(in_array($row->status, ['declined', 'expired', 'void']))
        <div style="max-width:780px;margin:0 auto;text-align:center;padding:24px;background:#fff1f2;border-radius:8px;color:#e11d48;font-weight:700;">{{ __('This quotation is no longer open.') }}</div>
        @endif
        @if($row->valid_days && $row->issue_date && $row->status === 'sent')
        <div style="max-width:780px;margin:12px auto 0;text-align:center;font-size:12px;color:#888;">{{ __('Valid until :d', ['d' => $row->issue_date->copy()->addDays($row->valid_days)->format('d M Y')]) }}</div>
        @endif
    @elseif($open)
    <div class="pay-section">

        @if($onlineEnabled)
        {{-- Online payment, through the business's own gateway account --}}
        <div class="pay-option" style="border-color:#0a0a0a;">
            <div class="pay-option-head">
                <div class="pay-option-icon">💳</div>
                <div><div class="pay-option-title">{{ __("Pay Online") }}</div><div class="pay-option-sub">{{ __("Card or wallet, processed securely") }}</div></div>
            </div>
            <form method="POST" action="{{ route('tourpay.online', $row->pay_token) }}">
                @csrf
                @if($amounts['next'] && $amounts['next']['amount'] + 0.005 < $balance)
                <div style="margin:4px 0 10px;font-size:13px;">
                    <label style="display:flex;gap:8px;align-items:center;margin-bottom:6px;"><input type="radio" name="choice" value="next" checked> <span>{{ $amounts['next']['label'] }}: <strong>{{ $row->currency }} {{ number_format($amounts['next']['amount'], 2) }}</strong></span></label>
                    <label style="display:flex;gap:8px;align-items:center;"><input type="radio" name="choice" value="balance"> <span>{{ __('Everything: :a', ['a' => $row->currency.' '.number_format($balance, 2)]) }}</span></label>
                </div>
                @else
                <input type="hidden" name="choice" value="balance">
                @endif
                @foreach($gateways as $gw => $label)
                <button type="submit" name="gateway" value="{{ $gw }}" class="pay-now-btn" style="margin-top:10px;">{{ $label }} · {{ $row->currency }} {{ number_format(($amounts['next'] && $amounts['next']['amount'] + 0.005 < $balance) ? $amounts['next']['amount'] : $balance, 2) }}</button>
                @endforeach
            </form>
        </div>
        @endif

        {{-- Manual EFT --}}
        @if($bankEnabled)
        <div class="pay-option">
            <div class="pay-option-head">
                <div class="pay-option-icon">🏦</div>
                <div>
                    <div class="pay-option-title">{{ __("Bank Transfer (EFT)") }}</div>
                    <div class="pay-option-sub">{{ __("Pay directly to our bank account") }}</div>
                </div>
            </div>
            @php $hasBanking = !empty($banking['bank'] ?? $banking['account_name'] ?? null); @endphp
            @if($hasBanking)
                @foreach(['account_name'=>__('Account Name'),'bank'=>__('Bank'),'account_number'=>__('Account No'),'branch_code'=>__('Branch Code'),'swift'=>__('SWIFT')] as $bk=>$bl)
                @if(!empty($banking[$bk]))
                <div class="pay-bank-row"><span class="k">{{ $bl }}</span><span class="v">{{ $banking[$bk] }}</span></div>
                @endif
                @endforeach
                <div class="pay-bank-row"><span class="k">{{ __('Amount') }}</span><span class="v">{{ $row->currency }} {{ number_format($balance, 2) }}</span></div>
                <div class="pay-ref-note">
                    {{ __("Please use") }} <strong>{{ $row->invoice_number }}</strong> {{ __("as your payment reference. Once paid, the business will confirm your payment.") }}
                </div>
            @else
                <p style="font-size:12px;color:#888;line-height:1.6;">{{ __("Banking details will be provided by the business. Please contact them directly.") }}</p>
            @endif
            @if($hasBanking && !($preview ?? false))
            <details style="margin-top:14px;">
                <summary style="cursor:pointer;font-size:13px;font-weight:700;">{{ __("I have paid by bank transfer") }}</summary>
                <form method="POST" action="{{ route('tourpay.transfer', $row->pay_token) }}" enctype="multipart/form-data" style="margin-top:10px;font-size:13px;">
                    @csrf
                    <label style="display:block;color:#888;font-size:11px;font-weight:700;text-transform:uppercase;margin:8px 0 3px;">{{ __('Amount paid') }}</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $balance }}" value="{{ number_format($balance, 2, '.', '') }}" required style="width:100%;padding:9px;border:1.5px solid #e4e4e4;border-radius:6px;">
                    <label style="display:block;color:#888;font-size:11px;font-weight:700;text-transform:uppercase;margin:8px 0 3px;">{{ __('Your bank reference') }}</label>
                    <input type="text" name="reference" required maxlength="191" style="width:100%;padding:9px;border:1.5px solid #e4e4e4;border-radius:6px;">
                    <label style="display:block;color:#888;font-size:11px;font-weight:700;text-transform:uppercase;margin:8px 0 3px;">{{ __('Proof of payment (photo or PDF, optional)') }}</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" style="width:100%;font-size:12px;">
                    <button type="submit" class="pay-now-btn" style="margin-top:12px;">{{ __('Tell the business I have paid') }}</button>
                </form>
            </details>
            @endif
        </div>
        @endif

    </div>
    @elseif($isInvoice && $row->status === 'paid')
    <div style="max-width:780px;margin:0 auto;text-align:center;padding:24px;background:#e8f5e9;border-radius:8px;color:#2e7d32;font-weight:700;">
        ✓ {{ __("This invoice has been paid in full. Thank you!") }}
    </div>
    @elseif($isInvoice && $row->status === 'void')
    <div style="max-width:780px;margin:0 auto;text-align:center;padding:24px;background:#fff1f2;border-radius:8px;color:#e11d48;font-weight:700;">{{ __("This invoice was cancelled. Nothing is owed on it.") }}</div>
    @endif

</div>
</body>
</html>
