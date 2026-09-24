@php
    $author       = $row->author ?? null;
    $company      = ($company ?? null) ?: ($author?->business_name ?: ($author?->name ?: setting_item('site_title', 'Tsoka Travel')));
    $logoId       = $author?->avatar_id ?: setting_item('logo_id');
    $logoUrl      = $logoId ? get_file_url($logoId) : null;
    $banking      = $row->banking_details ?? [];
    $preview      = $preview ?? false;
    $sellerEmail   = $author?->email  ?: setting_item('admin_email', '');
    $sellerPhone   = $author?->phone  ?: setting_item('phone', '');
    $sellerAddress = $author?->address ?: setting_item('address', '');
    $sellerReg     = setting_item('company_reg', '');
    $sellerVat     = setting_item('company_vat', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #111; background: #fff; }
.doc { max-width: 720px; margin: 0 auto; padding: 52px 56px; }
.hd { display: table; width: 100%; margin-bottom: 40px; }
.hd-left  { display: table-cell; vertical-align: middle; }
.hd-right { display: table-cell; vertical-align: middle; text-align: right; }
.logo-img  { max-height: 36px; max-width: 140px; }
.logo-text { font-size: 16px; font-weight: 700; color: #111; }
.doc-type  { font-size: 32px; font-weight: 300; color: #111; letter-spacing: -.02em; }
.doc-number{ font-size: 12px; color: #bbb; margin-top: 4px; letter-spacing: .04em; }
.divider { height: 1px; background: #111; margin-bottom: 28px; }
.meta { display: table; width: 100%; margin-bottom: 32px; }
.meta-col { display: table-cell; vertical-align: top; width: 25%; padding-right: 16px; }
.meta-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 5px; }
.meta-value { font-size: 12px; color: #111; line-height: 1.5; }
.proj { margin-bottom: 28px; }
.proj-title { font-size: 15px; font-weight: 600; }
.proj-desc  { font-size: 12px; color: #777; margin-top: 4px; line-height: 1.6; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.items-table thead th { padding: 8px 0 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #999; text-align: left; border-bottom: 1px solid #111; }
.items-table thead th:last-child,.items-table thead th:nth-child(2),.items-table thead th:nth-child(3) { text-align: right; }
.items-table tbody tr { border-bottom: 1px solid #f0f0f0; }
.items-table tbody td { padding: 9px 0; font-size: 12px; }
.items-table tbody td:nth-child(2),.items-table tbody td:nth-child(3),.items-table tbody td:last-child { text-align: right; }
.item-desc { font-size: 10px; color: #bbb; margin-top: 2px; }
.totals-wrap { display: table; width: 100%; margin-bottom: 36px; }
.totals-pad { display: table-cell; width: 55%; }
.totals-box { display: table-cell; width: 45%; vertical-align: top; }
.tr { display: table; width: 100%; padding: 5px 0; }
.tl { display: table-cell; font-size: 12px; color: #777; }
.tv { display: table-cell; font-size: 12px; text-align: right; font-weight: 600; color: #111; }
.tr-total { border-top: 1px solid #111; margin-top: 6px; padding-top: 10px !important; }
.tr-total .tl { color: #111; font-weight: 700; font-size: 14px; }
.tr-total .tv { font-size: 16px; font-weight: 800; }
.info-grid { display: table; width: 100%; margin-bottom: 28px; }
.info-col  { display: table-cell; vertical-align: top; width: 50%; padding-right: 24px; }
.info-col:last-child { padding-right: 0; }
.info-head { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 8px; }
.info-row  { display: table; width: 100%; margin-bottom: 3px; }
.info-k    { display: table-cell; font-size: 11px; color: #bbb; width: 50%; }
.info-v    { display: table-cell; font-size: 11px; color: #333; }
.notes-text { font-size: 11px; color: #777; line-height: 1.7; }
.footer { border-top: 1px solid #f0f0f0; padding-top: 14px; font-size: 10px; color: #ccc; text-align: center; }
</style>
</head>
<body>
<div class="doc">
    <div class="hd">
        <div class="hd-left">
            @if($logoUrl)<img src="{{ $logoUrl }}" class="logo-img" alt="{{ $company }}">
            @else<div class="logo-text">{{ $company }}</div>@endif
        </div>
        <div class="hd-right">
            <div class="doc-type">{{ ucfirst($row->type) }}</div>
            <div class="doc-number">{{ $row->invoice_number }}</div>
        </div>
    </div>
    <div class="divider"></div>
    <div class="meta">
        <div class="meta-col">
            <div class="meta-label">{{ __("From") }}</div>
            <div class="meta-value"><strong>{{ $company }}</strong><br>
            @if($sellerEmail){{ $sellerEmail }}<br>@endif
            @if($sellerPhone){{ $sellerPhone }}<br>@endif
            @if($sellerAddress){{ $sellerAddress }}<br>@endif
            @if($sellerVat){{ __("VAT") }}: {{ $sellerVat }}@endif</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Billed To") }}</div>
            <div class="meta-value"><strong>{{ $row->client_name }}</strong><br>
            @if($row->client_email){{ $row->client_email }}<br>@endif
            @if($row->client_phone){{ $row->client_phone }}<br>@endif
            @if($row->client_country){{ $row->client_country }}@endif</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Date") }}</div>
            <div class="meta-value">{{ $row->issue_date ? $row->issue_date->format('d M Y') : date('d M Y') }}</div>
            @if($row->due_date)<div class="meta-label" style="margin-top:10px">{{ __("Due") }}</div>
            <div class="meta-value">{{ $row->due_date->format('d M Y') }}</div>@endif
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Status") }}</div>
            <div class="meta-value">{{ $row->status_label }}</div>
        </div>
    </div>
    @if($row->title || $row->description)
    <div class="proj">
        @if($row->title)<div class="proj-title">{{ $row->title }}</div>@endif
        @if($row->description)<div class="proj-desc">{{ $row->description }}</div>@endif
    </div>
    @endif
    <table class="items-table">
        <thead><tr>
            <th>{{ __("Description") }}</th><th>{{ __("Qty") }}</th><th>{{ __("Unit") }}</th><th>{{ __("Total") }}</th>
        </tr></thead>
        <tbody>
        @foreach($row->items as $item)
        <tr>
            <td>{{ $item->name }}@if($item->description)<div class="item-desc">{{ $item->description }}</div>@endif</td>
            <td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price,2) }}</td><td>{{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="totals-wrap">
        <div class="totals-pad"></div>
        <div class="totals-box">
            @php $excl = ($row->tax_mode ?? 'inclusive') === 'exclusive'; @endphp
            @if($excl && $row->tax_rate > 0)
            <div class="tr"><div class="tl">{{ __("Subtotal") }}</div><div class="tv">{{ $row->currency }} {{ number_format($row->subtotal,2) }}</div></div>
            @endif
            @if($row->discount > 0)
            <div class="tr"><div class="tl">{{ __("Discount") }}</div><div class="tv">− {{ $row->currency }} {{ number_format($row->discount,2) }}</div></div>
            @endif
            @if($row->tax_rate > 0)
            @if(!$excl)
            <div class="tr"><div class="tl">{{ __("Excl. VAT") }}</div><div class="tv">{{ $row->currency }} {{ number_format($row->subtotal,2) }}</div></div>
            @endif
            @foreach(($row->tax_lines ?: [['name' => __('VAT'), 'rate' => $row->tax_rate + 0, 'amount' => $row->tax_amount]]) as $tl)
            <div class="tr"><div class="tl">{{ $tl['name'] }} ({{ $tl['rate'] + 0 }}%{{ $excl ? '' : ' '.__('incl.') }})</div><div class="tv">{{ $row->currency }} {{ number_format($tl['amount'],2) }}</div></div>
            @endforeach
            @endif
            <div class="tr tr-total"><div class="tl">{{ __("Total") }}</div><div class="tv">{{ $row->currency }} {{ number_format($row->total,2) }}</div></div>
            @if($row->type === 'invoice' && $row->credit_total > 0)
            <div class="tr"><div class="tl">{{ __("Credit notes") }}</div><div class="tv">− {{ $row->currency }} {{ number_format($row->credit_total,2) }}</div></div>
            @endif
            @if($row->type === 'invoice' && ($row->amount_paid != 0 || $row->credit_total > 0))
            <div class="tr"><div class="tl">{{ __("Paid") }}</div><div class="tv">− {{ $row->currency }} {{ number_format($row->amount_paid,2) }}</div></div>
            <div class="tr"><div class="tl"><strong>{{ $row->balance() <= 0 ? __("PAID IN FULL") : __("BALANCE DUE") }}</strong></div><div class="tv"><strong>{{ $row->currency }} {{ number_format(max(0, $row->balance()), 2) }}</strong></div></div>
            @endif
        </div>
    </div>
    @php $hasBanking = !empty($banking['bank'] ?? $banking['account_name'] ?? null); @endphp
    @if($row->notes || $row->payment_terms || $hasBanking)
    <div class="info-grid">
        @if($row->notes || $row->payment_terms)
        <div class="info-col">
            @if($row->notes)<div class="info-head">{{ __("Notes") }}</div><div class="notes-text">{{ $row->notes }}</div>@endif
            @if($row->payment_terms)<div class="info-head" style="margin-top:12px">{{ __("Payment Terms") }}</div><div class="notes-text">{{ $row->payment_terms }}</div>@endif
        </div>
        @endif
        @if($hasBanking)
        <div class="info-col">
            <div class="info-head">{{ __("Banking Details") }}</div>
            @foreach(['account_name'=>__('Account Name'),'bank'=>__('Bank'),'account_number'=>__('Account No'),'branch_code'=>__('Branch Code'),'swift'=>__('SWIFT')] as $bk=>$bl)
            @if(!empty($banking[$bk]))<div class="info-row"><div class="info-k">{{ $bl }}</div><div class="info-v">{{ $banking[$bk] }}</div></div>@endif
            @endforeach
        </div>
        @endif
    </div>
    @endif
    <div class="footer">{{ $company }} &nbsp;·&nbsp; {{ __("Thank you for your business.") }}</div>
</div>
</body>
</html>
