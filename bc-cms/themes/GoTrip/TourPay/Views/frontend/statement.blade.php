@extends('layouts.user')
@section('content')
<style>
.tp * { box-sizing: border-box; }
.tp-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #aaa; text-decoration: none; margin-bottom: 20px; }
.tp-back:hover { color: #0a0a0a; }
.tp-h1 { font-size: 24px; font-weight: 800; letter-spacing: -.03em; margin: 0 0 3px; }
.tp-sub { font-size: 13px; color: #999; margin: 0 0 20px; }
.tp-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
.tp-stat { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; padding: 14px 18px; min-width: 200px; flex: 1; }
.tp-stat .l { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: #888; margin-bottom: 6px; }
.tp-stat .v { font-size: 18px; font-weight: 800; letter-spacing: -.02em; }
.tp-stat .c { font-size: 11.5px; color: #999; margin-top: 2px; }
.tp-bar { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; margin-bottom: 16px; }
.tp-bar label { display: block; font-size: 11px; font-weight: 700; color: #777; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.tp-bar input, .tp-bar select { padding: 9px 11px; border: 1.5px solid #e4e4e4; border-radius: 7px; font-size: 13.5px; background: #fff; }
.tp-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; }
.tp-btn-primary { background: #0a0a0a; color: #fff !important; }
.tp-btn-ghost { background: #fff; color: #0a0a0a !important; border: 1.5px solid #e0e0e0; }
.tp-card { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; margin-bottom: 18px; overflow-x: auto; }
.tp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.tp-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #888; padding: 10px 16px; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
.tp-table td { padding: 10px 16px; border-bottom: 1px solid #f7f7f7; white-space: nowrap; }
.tp-table .n { text-align: right; font-variant-numeric: tabular-nums; }
.tp-table tr.open td { background: #fafafa; color: #777; font-weight: 700; }
.tp-pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 99px; background: #f4f4f5; color: #52525b; }
.tp-pill.payout { background: #0a0a0a; color: #fff; }
.tp-pill.expense { background: #fff1f2; color: #e11d48; }
.tp-in { color: #15803d; font-weight: 700; }
.tp-out { color: #e11d48; font-weight: 700; }
.empty { padding: 30px; text-align: center; color: #aaa; }
</style>
<div class="tp">
<a href="{{ route('tourpay.vendor.index') }}" class="tp-back"><i class="icofont-arrow-left"></i> {{ __('Back to TourPay') }}</a>
<h1 class="tp-h1">{{ __('Statement') }}</h1>
<p class="tp-sub">{{ __('Every invoice paid, every payout the platform made to you, and every expense, in one running account.') }}</p>

<div class="tp-stats">
    @foreach($currencies as $c)
    @php $t = $totals[$c] ?? ['invoice' => 0, 'booking' => 0, 'payout' => 0, 'expense' => 0, 'net' => 0]; @endphp
    <div class="tp-stat"><div class="l">{{ $c }} · {{ __('Invoices paid') }}</div><div class="v">{{ number_format($t['invoice'], 2) }}</div></div>
    <div class="tp-stat"><div class="l">{{ $c }} · {{ __('Booking payments') }}</div><div class="v">{{ number_format($t['booking'], 2) }}</div></div>
    <div class="tp-stat"><div class="l">{{ $c }} · {{ __('Paid out by platform') }}</div><div class="v">{{ number_format($t['payout'], 2) }}</div></div>
    <div class="tp-stat"><div class="l">{{ $c }} · {{ __('Expenses') }}</div><div class="v">{{ number_format($t['expense'], 2) }}</div></div>
    <div class="tp-stat"><div class="l">{{ $c }} · {{ __('Net') }}</div><div class="v {{ $t['net'] < 0 ? 'tp-out' : '' }}">{{ number_format($t['net'], 2) }}</div><div class="c">{{ __('Opening') }} {{ number_format($opening[$c] ?? 0, 2) }}</div></div>
    @if(!empty($owed[$c]))<div class="tp-stat"><div class="l">{{ $c }} · {{ __('Commission owed to platform') }}</div><div class="v tp-out">{{ number_format($owed[$c], 2) }}</div><div class="c">{{ __('On money you collected yourself. Settled from your next payout when it is in the payout currency.') }}</div></div>@endif
    @if(!empty($held[$c]))<div class="tp-stat"><div class="l">{{ $c }} · {{ __('Held by platform') }}</div><div class="v">{{ number_format($held[$c], 2) }}</div><div class="c">{{ __('Collected for you, not yet paid out') }}</div></div>@endif
    @endforeach
    @if(empty($currencies))<div class="tp-stat"><div class="l">{{ __('Nothing recorded yet') }}</div></div>@endif
</div>

<form method="GET" action="{{ route('tourpay.vendor.statement') }}" class="tp-bar">
    <div><label>{{ __('From') }}</label><input type="date" name="from" value="{{ $from->toDateString() }}"></div>
    <div><label>{{ __('To') }}</label><input type="date" name="to" value="{{ $to->toDateString() }}"></div>
    <div><label>{{ __('Show') }}</label>
        <select name="kind"><option value="">{{ __('Everything') }}</option>@foreach($kinds as $k => $l)<option value="{{ $k }}" {{ $kind === $k ? 'selected' : '' }}>{{ __($l) }}</option>@endforeach</select></div>
    @if(count($currencies) > 1)
    <div><label>{{ __('Currency') }}</label>
        <select name="cur"><option value="">{{ __('All') }}</option>@foreach($currencies as $c)<option value="{{ $c }}" {{ $cur === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach</select></div>
    @endif
    <button class="tp-btn tp-btn-primary">{{ __('Show') }}</button>
    <a class="tp-btn tp-btn-ghost" href="{{ route('tourpay.vendor.statement.csv', array_filter(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'kind' => $kind, 'cur' => $cur])) }}"><i class="icofont-download"></i> {{ __('Download CSV') }}</a>
    <button type="button" class="tp-btn tp-btn-ghost" onclick="window.print()"><i class="icofont-print"></i> {{ __('Print') }}</button>
</form>

<div class="tp-card">
@if(empty($entries) && empty($opening))
    <div class="empty">{{ __('Nothing in this period.') }}</div>
@else
<table class="tp-table">
    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Name') }}</th><th>{{ __('Method') }}</th><th>{{ __('Currency') }}</th><th class="n">{{ __('Money in') }}</th><th class="n">{{ __('Money out') }}</th><th class="n">{{ __('Balance') }}</th></tr></thead>
    <tbody>
    @foreach($opening as $c => $v)@if(!$cur || $cur === $c)
        <tr class="open"><td>{{ $from->format('d M Y') }}</td><td colspan="4">{{ __('Opening balance') }}</td><td>{{ $c }}</td><td></td><td></td><td class="n">{{ number_format($v, 2) }}</td></tr>
    @endif @endforeach
    @foreach($entries as $e)
        <tr>
            <td>{{ $e['date']->format('d M Y') }}</td>
            <td><span class="tp-pill {{ $e['kind'] }}">{{ __($kinds[$e['kind']]) }}</span>@if($e['note'] !== '') <span class="tp-pill expense">{{ $e['note'] }}</span>@endif</td>
            <td>{{ $e['ref'] }}</td><td>{{ $e['who'] }}</td><td>{{ ucfirst(str_replace('_', ' ', $e['method'])) }}</td><td>{{ $e['cur'] }}</td>
            <td class="n tp-in">{{ $e['in'] ? number_format($e['in'], 2) : '' }}@if($e['held'])<span class="tp-pill">{{ __('Held by platform') }} {{ number_format($e['held'], 2) }}</span>@endif @if($e['owed'])<span class="tp-pill expense">{{ $e['owed'] > 0 ? __('Commission owed') : __('Commission settled') }} {{ number_format(abs($e['owed']), 2) }}</span>@endif</td>
            <td class="n tp-out">{{ $e['out'] ? number_format($e['out'], 2) : '' }}</td>
            <td class="n"><strong>{{ number_format($e['balance'], 2) }}</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>
<div style="padding:12px 18px;font-size:12px;color:#999;">{{ __('Amounts are shown in the currency they were paid in and are never added across currencies. Where a booking and its invoice use different currencies, the booking is credited at that day\'s rate: your own rates first, otherwise the daily feed.') }} <a href="https://www.exchangerate-api.com" target="_blank" rel="noopener" style="color:#999;text-decoration:underline;">Rates By Exchange Rate API</a>. {{ __('Invoice payments count once the payment is confirmed; refunds come off. Expenses are payments made on supplier bills. Money the platform collected for you is shown as held until it is paid out; the payout is what lands in your account (in the platform currency). Each currency keeps its own balance and is never converted.') }}</div>
@endif
</div>
</div>
@endsection
