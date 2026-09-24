@extends('layouts.user')
@section('content')
<style>
.tp * { box-sizing: border-box; }
.tp-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #aaa; text-decoration: none; margin-bottom: 20px; }
.tp-back:hover { color: #0a0a0a; }
.tp-h1 { font-size: 24px; font-weight: 800; letter-spacing: -.03em; margin: 0 0 3px; }
.tp-sub { font-size: 13px; color: #999; margin: 0 0 20px; }
.tp-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin: 0 0 16px; border-bottom: 1px solid #ebebeb; }
.tp-tab { padding: 9px 14px; font-size: 13px; font-weight: 600; color: #777 !important; text-decoration: none !important; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.tp-tab.on { color: #0a0a0a !important; border-bottom-color: #0a0a0a; }
.tp-bar { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; margin-bottom: 16px; }
.tp-bar label { display: block; font-size: 11px; font-weight: 700; color: #777; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.tp-bar input, .tp-bar select { padding: 9px 11px; border: 1.5px solid #e4e4e4; border-radius: 7px; font-size: 13.5px; background: #fff; }
.tp-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; }
.tp-btn-primary { background: #0a0a0a; color: #fff !important; }
.tp-btn-ghost { background: #fff; color: #0a0a0a !important; border: 1.5px solid #e0e0e0; }
.tp-card { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; margin-bottom: 18px; overflow-x: auto; }
.tp-card h3 { margin: 0; padding: 14px 18px; font-size: 14px; font-weight: 800; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; }
.tp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.tp-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #888; padding: 10px 18px; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
.tp-table td { padding: 10px 18px; border-bottom: 1px solid #f7f7f7; white-space: nowrap; }
.tp-table .n { text-align: right; font-variant-numeric: tabular-nums; }
.tp-table tr.total td { font-weight: 800; border-top: 2px solid #0a0a0a; background: #fafafa; }
.late { color: #e11d48; font-weight: 700; }
.empty { padding: 30px; text-align: center; color: #aaa; }
</style>
<div class="tp">
<a href="{{ route('tourpay.vendor.index') }}" class="tp-back"><i class="icofont-arrow-left"></i> {{ __('Back to TourPay') }}</a>
<h1 class="tp-h1">{{ __('Reports') }}</h1>
<p class="tp-sub">{{ __('What you are owed, what came in, the tax you collected, and each client\'s account.') }}</p>

<div class="tp-tabs">
    @foreach(['receivables' => __('Who owes you'), 'revenue' => __('Money in'), 'tax' => __('Tax'), 'profit' => __('Profit by booking'), 'statement' => __('Client statement')] as $k => $l)
        <a class="tp-tab {{ $tab === $k ? 'on' : '' }}" href="{{ route('tourpay.vendor.reports', ['tab' => $k, 'from' => request('from'), 'to' => request('to'), 'client' => request('client')]) }}">{{ $l }}</a>
    @endforeach
</div>

<form method="GET" action="{{ route('tourpay.vendor.reports') }}" class="tp-bar">
    <input type="hidden" name="tab" value="{{ $tab }}">
    @if($tab === 'revenue' || $tab === 'tax')
        <div><label>{{ __('From') }}</label><input type="date" name="from" value="{{ $from->toDateString() }}"></div>
        <div><label>{{ __('To') }}</label><input type="date" name="to" value="{{ $to->toDateString() }}"></div>
        <button class="tp-btn tp-btn-primary">{{ __('Show') }}</button>
    @endif
    @if($tab === 'statement')
        <div><label>{{ __('Client') }}</label>
            <select name="client" onchange="this.form.submit()" style="min-width:280px;">
                <option value="">{{ __('Choose a client…') }}</option>
                @foreach($clients as $c)<option value="{{ $c['key'] }}" {{ $client === $c['key'] ? 'selected' : '' }}>{{ $c['label'] }}</option>@endforeach
            </select></div>
    @endif
    @if($tab !== 'statement' || $client !== '')
    <a class="tp-btn tp-btn-ghost" href="{{ route('tourpay.vendor.reports.csv', array_filter(['report' => $tab, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'client' => $client])) }}"><i class="icofont-download"></i> {{ __('Download CSV') }}</a>
    @endif
</form>

@if($tab === 'receivables')
    @forelse($receivables as $cur => $r)
    <div class="tp-card">
        <h3><span>{{ $cur }}</span><span>{{ __('Owed') }}: {{ number_format($r['total'], 2) }}</span></h3>
        <table class="tp-table">
            <thead><tr><th>{{ __('Client') }}</th><th class="n">{{ __('Invoices') }}</th>@foreach($buckets as $k => $l)<th class="n">{{ __($l) }}</th>@endforeach<th class="n">{{ __('Total') }}</th></tr></thead>
            <tbody>
            @foreach($r['clients'] as $c)
                <tr><td>{{ $c['name'] }}@if($c['email'])<div style="color:#aaa;font-size:11.5px;">{{ $c['email'] }}</div>@endif</td><td class="n">{{ $c['invoices'] }}</td>
                @foreach($buckets as $k => $l)<td class="n {{ $k !== 'current' && $c['buckets'][$k] > 0 ? 'late' : '' }}">{{ $c['buckets'][$k] > 0 ? number_format($c['buckets'][$k], 2) : '—' }}</td>@endforeach
                <td class="n"><strong>{{ number_format($c['total'], 2) }}</strong></td></tr>
            @endforeach
            <tr class="total"><td>{{ __('All clients') }}</td><td></td>@foreach($buckets as $k => $l)<td class="n">{{ number_format($r['buckets'][$k], 2) }}</td>@endforeach<td class="n">{{ number_format($r['total'], 2) }}</td></tr>
            </tbody>
        </table>
    </div>
    @empty
    <div class="tp-card"><div class="empty">{{ __('Nobody owes you anything right now.') }}</div></div>
    @endforelse
@elseif($tab === 'revenue')
    <div class="tp-card">
        @if(empty($revenue))<div class="empty">{{ __('Nothing in this period.') }}</div>@else
        <table class="tp-table"><thead><tr><th>{{ __('Month') }}</th><th>{{ __('Currency') }}</th><th class="n">{{ __('Invoices') }}</th><th class="n">{{ __('Invoiced') }}</th><th class="n">{{ __('Received') }}</th></tr></thead><tbody>
        @foreach($revenue as $ym => $byCur)@foreach($byCur as $cur => $r)
            <tr><td>{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</td><td>{{ $cur }}</td><td class="n">{{ $r['invoices'] ?? 0 }}</td><td class="n">{{ number_format($r['invoiced'] ?? 0, 2) }}</td><td class="n"><strong>{{ number_format($r['received'] ?? 0, 2) }}</strong></td></tr>
        @endforeach @endforeach
        </tbody></table>
        <div style="padding:12px 18px;font-size:12px;color:#999;">{{ __('Invoiced is by issue date, less credit notes. Received is money that arrived in the period, less refunds. Each currency is shown as it was, not converted.') }}</div>
        @endif
    </div>
@elseif($tab === 'tax')
    <div class="tp-card">
        @if(empty($tax))<div class="empty">{{ __('No tax charged in this period.') }}</div>@else
        <table class="tp-table"><thead><tr><th>{{ __('Currency') }}</th><th>{{ __('Tax') }}</th><th class="n">{{ __('Rate') }}</th><th class="n">{{ __('Taxable amount') }}</th><th class="n">{{ __('Tax') }}</th><th class="n">{{ __('Documents') }}</th></tr></thead><tbody>
        @foreach($tax as $r)<tr><td>{{ $r['currency'] }}</td><td>{{ $r['name'] }}</td><td class="n">{{ $r['rate'] + 0 }}%</td><td class="n">{{ number_format($r['taxable'], 2) }}</td><td class="n"><strong>{{ number_format($r['tax'], 2) }}</strong></td><td class="n">{{ $r['documents'] }}</td></tr>@endforeach
        </tbody></table>
        <div style="padding:12px 18px;font-size:12px;color:#999;">{{ __('Invoices issued in the period (drafts and voided ones excluded); credit notes take their tax back.') }}</div>
        @endif
    </div>
@elseif($tab === 'profit')
    <div class="tp-card">
        @if(empty($profit))<div class="empty">{{ __('No booking has a supplier bill yet. Add bills under Supplier bills and link them to a booking.') }}</div>@else
        <table class="tp-table"><thead><tr><th>{{ __('Booking') }}</th><th>{{ __('Guest') }}</th><th class="n">{{ __('Revenue') }}</th><th class="n">{{ __('Supplier costs') }}</th><th class="n">{{ __('Profit') }}</th><th class="n">{{ __('Margin') }}</th></tr></thead><tbody>
        @foreach($profit as $p)<tr>
            <td><a href="{{ route('vendor.bookings.ops', $p['booking']->id) }}">{{ $p['booking']->code ?: '#'.$p['booking']->id }}</a></td><td>{{ trim($p['booking']->first_name.' '.$p['booking']->last_name) }}</td>
            @if($p['mixed'])<td colspan="4" style="color:#c2410c;">{{ __('Revenue and costs are in different currencies: not added together.') }}</td>
            @else<td class="n">{{ $p['currency'] }} {{ number_format($p['revenue'], 2) }}</td><td class="n">{{ number_format($p['cost'], 2) }}</td><td class="n {{ $p['profit'] < 0 ? 'late' : '' }}"><strong>{{ number_format($p['profit'], 2) }}</strong></td><td class="n">{{ $p['margin'] !== null ? $p['margin'].'%' : '—' }}</td>@endif
        </tr>@endforeach
        </tbody></table>
        <div style="padding:12px 18px;font-size:12px;color:#999;">{{ __('Revenue is what the booking\'s invoices come to less credit notes (the booking total when it has no invoice yet). Costs are the supplier bills linked to it.') }}</div>
        @endif
    </div>
@else
    @if($statement)
    <div class="tp-card">
        <h3><span>{{ __('Account') }}</span><span>@foreach($statement['balances'] as $cur => $b)<span class="{{ $b > 0 ? 'late' : '' }}">{{ $cur }} {{ number_format($b, 2) }}</span> @endforeach</span></h3>
        <table class="tp-table"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Reference') }}</th><th class="n">{{ __('Billed') }}</th><th class="n">{{ __('Paid / credited') }}</th><th class="n">{{ __('Balance') }}</th></tr></thead><tbody>
        @foreach($statement['events'] as $e)
            <tr><td>{{ $e['date']->format('d M Y') }}</td><td>{{ ucfirst($e['kind']) }}</td><td>{{ $e['ref'] }}</td><td class="n">{{ $e['debit'] ? $e['cur'].' '.number_format($e['debit'], 2) : '' }}</td><td class="n">{{ $e['credit'] ? $e['cur'].' '.number_format($e['credit'], 2) : '' }}</td><td class="n"><strong>{{ $e['cur'] }} {{ number_format($e['balance'], 2) }}</strong></td></tr>
        @endforeach
        </tbody></table>
    </div>
    @else
    <div class="tp-card"><div class="empty">{{ __('Choose a client to see everything billed to them and everything they have paid.') }}</div></div>
    @endif
@endif
</div>
@endsection
