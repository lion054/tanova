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
body { font-family: Georgia, 'Times New Roman', serif; font-size: 13px; color: #1a1a1a; background: #fff; }

.doc { padding: 48px 52px; max-width: 720px; margin: 0 auto; }

/* Header */
.hd { display: table; width: 100%; border-bottom: 2px solid #c8a96e; padding-bottom: 24px; margin-bottom: 28px; }
.hd-left  { display: table-cell; vertical-align: middle; width: 50%; }
.hd-right { display: table-cell; vertical-align: middle; text-align: right; }
.logo-img  { max-height: 48px; max-width: 160px; }
.logo-text { font-size: 20px; font-weight: 700; color: #1a1a1a; letter-spacing: -.01em; }
.doc-type  { font-size: 28px; font-weight: 700; color: #c8a96e; letter-spacing: .04em; text-transform: uppercase; }
.doc-number{ font-size: 13px; color: #888; margin-top: 4px; }

/* Meta grid */
.meta { display: table; width: 100%; margin-bottom: 28px; }
.meta-col { display: table-cell; vertical-align: top; width: 33.33%; padding-right: 16px; }
.meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #c8a96e; margin-bottom: 4px; }
.meta-value { font-size: 12px; color: #333; line-height: 1.5; }

/* Title / desc */
.proj { margin-bottom: 24px; padding: 16px; background: #fdf8f0; border-left: 3px solid #c8a96e; border-radius: 2px; }
.proj-title { font-size: 14px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
.proj-desc  { font-size: 12px; color: #666; line-height: 1.5; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.items-table thead tr { background: #c8a96e; }
.items-table thead th { padding: 9px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #fff; text-align: left; }
.items-table thead th:last-child, .items-table thead th:nth-child(2), .items-table thead th:nth-child(3) { text-align: right; }
.items-table tbody tr { border-bottom: 1px solid #f0ead8; }
.items-table tbody tr:nth-child(even) { background: #fffdf7; }
.items-table tbody td { padding: 9px 12px; font-size: 12px; vertical-align: top; }
.items-table tbody td:nth-child(2), .items-table tbody td:nth-child(3), .items-table tbody td:last-child { text-align: right; }
.item-desc { font-size: 10px; color: #888; margin-top: 2px; }

/* Totals */
.totals { display: table; width: 100%; margin-bottom: 28px; }
.totals-pad { display: table-cell; width: 55%; }
.totals-box { display: table-cell; width: 45%; vertical-align: top; }
.totals-row { display: table; width: 100%; border-bottom: 1px solid #f0ead8; }
.totals-label { display: table-cell; padding: 7px 12px; font-size: 12px; color: #666; }
.totals-val   { display: table-cell; padding: 7px 12px; font-size: 12px; text-align: right; font-weight: 600; }
.totals-grand { background: #c8a96e; border-radius: 2px; margin-top: 4px; }
.totals-grand .totals-label { color: #fff; font-weight: 700; font-size: 13px; }
.totals-grand .totals-val   { color: #fff; font-weight: 700; font-size: 15px; }

/* Banking / Notes */
.info-grid { display: table; width: 100%; margin-bottom: 24px; }
.info-col  { display: table-cell; vertical-align: top; width: 50%; padding-right: 20px; }
.info-col:last-child { padding-right: 0; }
.info-head { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #c8a96e; margin-bottom: 8px; border-bottom: 1px solid #f0ead8; padding-bottom: 4px; }
.info-row  { display: table; width: 100%; margin-bottom: 3px; }
.info-k    { display: table-cell; font-size: 11px; color: #888; width: 50%; }
.info-v    { display: table-cell; font-size: 11px; color: #333; font-weight: 600; }
.notes-text { font-size: 11px; color: #555; line-height: 1.6; }

/* Footer */
.footer { border-top: 1px solid #f0ead8; padding-top: 14px; text-align: center; font-size: 10px; color: #bbb; }
</style>
</head>
<body>
<div class="doc">

    {{-- Header --}}
    <div class="hd">
        <div class="hd-left">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" class="logo-img" alt="{{ $company }}">
            @else
                <div class="logo-text">{{ $company }}</div>
            @endif
        </div>
        <div class="hd-right">
            <div class="doc-type">{{ strtoupper($row->type) }}</div>
            <div class="doc-number">{{ $row->invoice_number }}</div>
        </div>
    </div>

    {{-- Meta --}}
    <div class="meta">
        <div class="meta-col">
            <div class="meta-label">{{ __("From") }}</div>
            <div class="meta-value">
                <strong>{{ $company }}</strong><br>
                @if($sellerEmail){{ $sellerEmail }}<br>@endif
                @if($sellerPhone){{ $sellerPhone }}<br>@endif
                @if($sellerAddress){{ $sellerAddress }}<br>@endif
                @if($sellerReg){{ __("Reg") }}: {{ $sellerReg }}<br>@endif
                @if($sellerVat){{ __("VAT") }}: {{ $sellerVat }}@endif
            </div>
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Billed To") }}</div>
            <div class="meta-value">
                <strong>{{ $row->client_name }}</strong><br>
                @if($row->client_email){{ $row->client_email }}<br>@endif
                @if($row->client_phone){{ $row->client_phone }}<br>@endif
                @if($row->client_country){{ $row->client_country }}@endif
                @if($row->client_address)<br>{{ $row->client_address }}@endif
            </div>
        </div>
        <div class="meta-col">
            <div class="meta-label">{{ __("Issue Date") }}</div>
            <div class="meta-value">{{ $row->issue_date ? $row->issue_date->format('d M Y') : date('d M Y') }}</div>
            @if($row->due_date)
            <div class="meta-label" style="margin-top:12px;">{{ __("Due Date") }}</div>
            <div class="meta-value">{{ $row->due_date->format('d M Y') }}</div>
            @endif
            @if($row->isQuotation() && $row->valid_days)
            <div class="meta-label" style="margin-top:12px;">{{ __("Valid For") }}</div>
            <div class="meta-value">{{ $row->valid_days }} {{ __("days") }}</div>
            @endif
            <div class="meta-label" style="margin-top:12px;">{{ __("Status") }}</div>
            <div class="meta-value"><strong>{{ ucfirst($row->status) }}</strong></div>
            <div class="meta-label" style="margin-top:12px;">{{ __("Currency") }}</div>
            <div class="meta-value">{{ $row->currency }}</div>
        </div>
    </div>

    {{-- Project title --}}
    @if($row->title || $row->description)
    <div class="proj">
        @if($row->title)<div class="proj-title">{{ $row->title }}</div>@endif
        @if($row->description)<div class="proj-desc">{{ $row->description }}</div>@endif
    </div>
    @endif

    {{-- Line items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __("Description") }}</th>
                <th>{{ __("Qty") }}</th>
                <th>{{ __("Unit Price") }}</th>
                <th>{{ __("Amount") }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($row->items as $item)
            <tr>
                <td>
                    {{ $item->name }}
                    @if($item->description)<div class="item-desc">{{ $item->description }}</div>@endif
                </td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-pad"></div>
        <div class="totals-box">
            @if($row->tax_rate > 0)
            <div class="totals-row">
                <div class="totals-label">{{ __("Excl. VAT") }}</div>
                <div class="totals-val">{{ $row->currency }} {{ number_format($row->subtotal, 2) }}</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">{{ __("VAT") }} ({{ $row->tax_rate }}% {{ __("incl.") }})</div>
                <div class="totals-val">{{ $row->currency }} {{ number_format($row->tax_amount, 2) }}</div>
            </div>
            @endif
            <div class="totals-row totals-grand">
                <div class="totals-label">{{ __("TOTAL") }}</div>
                <div class="totals-val">{{ $row->currency }} {{ number_format($row->total, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Notes + Banking --}}
    @php $hasBanking = !empty($banking['account_name'] ?? $banking['bank'] ?? null); @endphp
    @if($row->notes || $row->payment_terms || $hasBanking)
    <div class="info-grid">
        @if($row->notes || $row->payment_terms)
        <div class="info-col">
            @if($row->notes)
            <div class="info-head">{{ __("Notes") }}</div>
            <div class="notes-text">{{ $row->notes }}</div>
            @endif
            @if($row->payment_terms)
            <div class="info-head" style="margin-top:12px;">{{ __("Payment Terms") }}</div>
            <div class="notes-text">{{ $row->payment_terms }}</div>
            @endif
        </div>
        @endif
        @if($hasBanking)
        <div class="info-col">
            <div class="info-head">{{ __("Banking Details") }}</div>
            @foreach(['account_name'=>__('Account Name'),'bank'=>__('Bank'),'account_number'=>__('Account No'),'branch_code'=>__('Branch Code'),'swift'=>__('SWIFT')] as $bk => $bl)
                @if(!empty($banking[$bk]))
                <div class="info-row">
                    <div class="info-k">{{ $bl }}</div>
                    <div class="info-v">{{ $banking[$bk] }}</div>
                </div>
                @endif
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <div class="footer">{{ $company }} &nbsp;·&nbsp; {{ __("Thank you for your business.") }}</div>

</div>
</body>
</html>
