@php
    $author       = $row->author ?? null;
    $company      = $author?->business_name ?: ($author?->name ?: setting_item('site_title', 'Tsoka Travel'));
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
body { font-family: -apple-system, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }
.doc { max-width: 720px; margin: 0 auto; }
/* Top colour bar */
.topbar { height: 6px; background: linear-gradient(90deg,#1a73e8,#0d47a1); }
.inner { padding: 40px 52px; }
.hd { display: table; width: 100%; margin-bottom: 32px; }
.hd-left  { display: table-cell; vertical-align: middle; }
.hd-right { display: table-cell; vertical-align: middle; text-align: right; }
.logo-img  { max-height: 42px; max-width: 150px; }
.logo-text { font-size: 18px; font-weight: 700; color: #1a73e8; }
.doc-type  { font-size: 26px; font-weight: 800; color: #1a1a1a; letter-spacing: -.02em; }
.doc-number{ font-size: 12px; color: #888; margin-top: 3px; }
.badge-status {
    display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em; background: #e8f4ff; color: #1a73e8; margin-top: 6px;
}
.meta { display: table; width: 100%; margin-bottom: 28px; background: #f8faff; border-radius: 6px; padding: 18px; }
.meta-col { display: table-cell; vertical-align: top; width: 33.33%; padding-right: 12px; }
.meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #1a73e8; margin-bottom: 4px; }
.meta-value { font-size: 12px; color: #333; line-height: 1.5; }
.proj { margin-bottom: 24px; }
.proj-title { font-size: 15px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
.proj-desc  { font-size: 12px; color: #666; line-height: 1.5; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.items-table thead th { padding: 9px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #1a73e8; text-align: left; border-bottom: 2px solid #1a73e8; }
.items-table thead th:last-child, .items-table thead th:nth-child(2), .items-table thead th:nth-child(3) { text-align: right; }
.items-table tbody tr { border-bottom: 1px solid #f0f4ff; }
.items-table tbody td { padding: 9px 12px; font-size: 12px; vertical-align: top; }
.items-table tbody td:nth-child(2), .items-table tbody td:nth-child(3), .items-table tbody td:last-child { text-align: right; }
.item-desc { font-size: 10px; color: #aaa; margin-top: 2px; }
.totals-wrap { display: table; width: 100%; margin-bottom: 28px; }
.totals-pad { display: table-cell; width: 55%; }
.totals-box { display: table-cell; width: 45%; vertical-align: top; }
.tr { display: table; width: 100%; padding: 6px 0; border-bottom: 1px solid #f0f4ff; }
.tl { display: table-cell; font-size: 12px; color: #666; }
.tv { display: table-cell; font-size: 12px; text-align: right; font-weight: 600; }
.tr-total { background: #1a73e8; border-radius: 4px; margin-top: 6px; }
.tr-total .tl { color: #fff; font-weight: 700; padding: 8px 10px; }
.tr-total .tv { color: #fff; font-weight: 800; font-size: 15px; padding: 8px 10px; }
.info-grid { display: table; width: 100%; margin-bottom: 24px; }
.info-col  { display: table-cell; vertical-align: top; width: 50%; padding-right: 20px; }
.info-col:last-child { padding-right: 0; }
.info-head { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #1a73e8; margin-bottom: 8px; }
.info-row  { display: table; width: 100%; margin-bottom: 3px; }
.info-k    { display: table-cell; font-size: 11px; color: #888; width: 50%; }
.info-v    { display: table-cell; font-size: 11px; color: #333; font-weight: 600; }
.notes-text { font-size: 11px; color: #555; line-height: 1.6; }
.footer { border-top: 2px solid #1a73e8; margin-top: 8px; padding-top: 12px; text-align: center; font-size: 10px; color: #bbb; }
</style>
</head>
<body>
<div class="doc">
<div class="topbar"></div>
<div class="inner">
    <div class="hd">
        <div class="hd-left">
            @if($logoUrl)<img src="{{ $logoUrl }}" class="logo-img" alt="{{ $company }}">
            @else<div class="logo-text">{{ $company }}</div>@endif
        </div>
        <div class="hd-right">
            <div class="doc-type">{{ ucfirst($row->type) }}</div>
            <div class="doc-number">{{ $row->invoice_number }}</div>
            <div class="badge-status">{{ ucfirst($row->status) }}</div>
        </div>
    </div>
    <div class="meta">
        <div class="meta-col">
            <div class="meta-label">{{ __("From") }}</div>
            <div class="meta-value"><strong>{{ $company }}</strong><br>
            @if($sellerEmail){{ $sellerEmail }}<br>@endif
            @if($sellerPhone){{ $sellerPhone }}<br>@endif
            @if($sellerAddress){{ $sellerAddress }}<br>@endif
            @if($sellerReg){{ __("Reg") }}: {{ $sellerReg }}<br>@endif
            @if($sellerVat){{ __("VAT No") }}: {{ $sellerVat }}@endif</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Billed To") }}</div>
            <div class="meta-value"><strong>{{ $row->client_name }}</strong><br>
            @if($row->client_email){{ $row->client_email }}<br>@endif
            @if($row->client_phone){{ $row->client_phone }}<br>@endif
            @if($row->client_country){{ $row->client_country }}@endif</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Issued") }}</div>
            <div class="meta-value">{{ $row->issue_date ? $row->issue_date->format('d M Y') : date('d M Y') }}</div>
            @if($row->due_date)<div class="meta-label" style="margin-top:10px">{{ __("Due") }}</div>
            <div class="meta-value">{{ $row->due_date->format('d M Y') }}</div>@endif
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Currency") }}</div>
            <div class="meta-value">{{ $row->currency }}</div>
            @if($row->isQuotation() && $row->valid_days)
            <div class="meta-label" style="margin-top:10px">{{ __("Valid For") }}</div>
            <div class="meta-value">{{ $row->valid_days }} {{ __("days") }}</div>@endif
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
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->unit_price,2) }}</td>
            <td>{{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="totals-wrap">
        <div class="totals-pad"></div>
        <div class="totals-box">
            @if($row->tax_rate > 0)
            <div class="tr"><div class="tl">{{ __("Excl. VAT") }}</div><div class="tv">{{ $row->currency }} {{ number_format($row->subtotal,2) }}</div></div>
            <div class="tr"><div class="tl">{{ __("VAT") }} ({{ $row->tax_rate }}% {{ __("incl.") }})</div><div class="tv">{{ $row->currency }} {{ number_format($row->tax_amount,2) }}</div></div>
            @endif
            <div class="tr tr-total"><div class="tl">{{ __("TOTAL") }}</div><div class="tv">{{ $row->currency }} {{ number_format($row->total,2) }}</div></div>
        </div>
    </div>
    @php $hasBanking = !empty($banking['bank'] ?? $banking['account_name'] ?? null); @endphp
    @if($row->notes || $hasBanking)
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
</div>
</body>
</html>
