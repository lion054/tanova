@extends('layouts.user')
@section('content')
<style>
.tp * { box-sizing: border-box; }
.tp-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #aaa; text-decoration: none; margin-bottom: 20px; }
.tp-back:hover { color: #0a0a0a; }
.tp-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.tp-h1 { font-size: 24px; font-weight: 800; letter-spacing: -.03em; margin: 0 0 3px; }
.tp-sub { font-size: 13px; color: #999; margin: 0; }
.tp-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; }
.tp-btn-primary { background: #0a0a0a; color: #fff !important; }
.tp-btn-ghost { background: #fff; color: #0a0a0a !important; border: 1.5px solid #e0e0e0; }
.tp-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
.tp-stat { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; padding: 14px 18px; min-width: 200px; }
.tp-stat .l { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: #888; margin-bottom: 6px; }
.tp-stat .v { font-size: 18px; font-weight: 800; letter-spacing: -.02em; }
.tp-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin: 0 0 14px; border-bottom: 1px solid #ebebeb; }
.tp-tab { padding: 9px 13px; font-size: 13px; font-weight: 600; color: #777 !important; text-decoration: none !important; border-bottom: 2px solid transparent; margin-bottom: -1px; display: inline-flex; gap: 7px; }
.tp-tab.on { color: #0a0a0a !important; border-bottom-color: #0a0a0a; }
.tp-tab .n { font-size: 11px; background: #f4f4f5; color: #71717a; border-radius: 99px; padding: 1px 7px; }
.tp-tab.on .n { background: #0a0a0a; color: #fff; }
.tp-card { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; overflow-x: auto; }
.tp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.tp-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #888; padding: 10px 16px; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
.tp-table td { padding: 12px 16px; border-bottom: 1px solid #f7f7f7; vertical-align: top; }
.tp-table .n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.late { color: #e11d48; font-weight: 700; }
.sub { color: #aaa; font-size: 11.5px; }
.tp-empty { padding: 34px; text-align: center; color: #aaa; }
.tp-modal-bg { position: fixed; inset: 0; background: rgba(10,10,10,.45); display: none; align-items: center; justify-content: center; z-index: 10050; padding: 16px; }
.tp-modal-bg.open { display: flex; }
.tp-modal { background: #fff; border-radius: 12px; width: 100%; max-width: 520px; max-height: 92vh; overflow: auto; padding: 22px; }
.tp-modal h3 { margin: 0 0 12px; font-size: 17px; font-weight: 800; }
.tp-modal label { display: block; font-size: 11px; font-weight: 700; color: #777; text-transform: uppercase; letter-spacing: .05em; margin: 11px 0 4px; }
.tp-modal input, .tp-modal select, .tp-modal textarea { width: 100%; padding: 9px 11px; border: 1.5px solid #e4e4e4; border-radius: 7px; font-size: 13.5px; background: #fff; }
.tp-modal .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.tp-modal .foot { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.pill { display: inline-block; padding: 2px 9px; border-radius: 99px; font-size: 11.5px; font-weight: 700; background: #f4f4f5; color: #71717a; }
.pill.open { background: #eff6ff; color: #2563eb; } .pill.part_paid { background: #fff7ed; color: #c2410c; } .pill.paid { background: #f0fdf4; color: #16a34a; } .pill.overdue, .pill.void { background: #fff1f2; color: #e11d48; }
</style>
<div class="tp">
<a href="{{ route('tourpay.vendor.index') }}" class="tp-back"><i class="icofont-arrow-left"></i> {{ __('Back to TourPay') }}</a>
<div class="tp-head">
    <div><h1 class="tp-h1">{{ __('Supplier bills') }}</h1><p class="tp-sub">{{ __('What you owe the people who deliver the trip. Link a bill to a booking and you see what that booking really earned.') }}</p></div>
    <div style="display:flex;gap:8px;"><a class="tp-btn tp-btn-ghost" href="{{ route('tourpay.vendor.reports', ['tab' => 'profit']) }}"><i class="icofont-chart-bar-graph"></i> {{ __('Profit by booking') }}</a><button class="tp-btn tp-btn-primary" onclick="openM('bill-new')"><i class="icofont-plus"></i> {{ __('Add a bill') }}</button></div>
</div>
@include('admin.message')

<div class="tp-stats">
    <div class="tp-stat"><div class="l">{{ __('You owe suppliers') }}</div>@forelse($owed as $o)<div class="v">{{ $o->currency }} {{ number_format($o->total, 2) }}</div>@empty<div class="v" style="color:#aaa;">—</div>@endforelse</div>
    <div class="tp-stat"><div class="l">{{ __('Overdue') }}</div><div class="v {{ $counts['overdue'] ? 'late' : '' }}">{{ $counts['overdue'] }}</div></div>
</div>

<div class="tp-tabs">
@foreach(['open' => __('To pay'), 'overdue' => __('Overdue'), 'paid' => __('Paid'), 'void' => __('Void')] as $k => $l)
    <a class="tp-tab {{ $tab === $k ? 'on' : '' }}" href="{{ route('tourpay.vendor.bills', ['tab' => $k]) }}">{{ $l }} <span class="n">{{ $counts[$k] }}</span></a>
@endforeach
</div>

<div class="tp-card">
<table class="tp-table">
    <thead><tr><th>{{ __('Supplier') }}</th><th>{{ __('For') }}</th><th class="n">{{ __('Total') }}</th><th class="n">{{ __('Still owed') }}</th><th>{{ __('Due') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
    <tbody>
    @forelse($rows as $b)
    <tr>
        <td><strong>{{ $b->supplier_name }}</strong>@if($b->reference)<div class="sub">{{ __('their ref') }} {{ $b->reference }}</div>@endif</td>
        <td>{{ $b->description ?: '—' }}@if($b->booking_id)<div class="sub"><a href="{{ route('vendor.bookings.ops', $b->booking_id) }}">{{ __('booking') }} #{{ $b->booking_id }}</a></div>@endif</td>
        <td class="n">{{ $b->currency }} {{ number_format($b->total, 2) }}</td>
        <td class="n"><strong>{{ $b->currency }} {{ number_format($b->balance(), 2) }}</strong></td>
        <td class="{{ $b->isOverdue() ? 'late' : '' }}">{{ $b->due_date ? $b->due_date->format('d M Y') : '—' }}</td>
        <td><span class="pill {{ $b->isOverdue() ? 'overdue' : $b->status }}">{{ $b->isOverdue() ? __('Overdue') : ucfirst(str_replace('_', ' ', $b->status)) }}</span></td>
        <td class="n">
            @if(in_array($b->status, ['open','part_paid']))<button class="tp-btn tp-btn-ghost" style="padding:5px 11px;font-size:12px;" onclick="payBill({{ $b->id }}, '{{ addslashes($b->supplier_name) }}', '{{ $b->currency }}', {{ $b->balance() }})">{{ __('Pay') }}</button>@endif
            @if($b->status !== 'void')
            <form method="POST" action="{{ route('tourpay.vendor.bills.void', $b->id) }}" style="display:inline" onsubmit="return confirm('{{ __('Void this bill?') }}')">@csrf<button class="tp-btn tp-btn-ghost" style="padding:5px 11px;font-size:12px;">{{ __('Void') }}</button></form>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="7"><div class="tp-empty">{{ $tab === 'open' ? __('No bills to pay. Add one when a supplier invoices you.') : __('Nothing here.') }}</div></td></tr>
    @endforelse
    </tbody>
</table>
@if($rows->total() > 0)<div style="padding:12px 16px;">{{ $rows->links() }}</div>@endif
</div>
</div>

{{-- New bill --}}
<div class="tp-modal-bg" id="m-bill-new" onclick="if(event.target===this)closeM('bill-new')">
  <form class="tp-modal" method="POST" action="{{ route('tourpay.vendor.bills.store') }}">
    @csrf
    <h3>{{ __('Add a supplier bill') }}</h3>
    <label>{{ __('Supplier') }}</label>
    <select name="supplier_id"><option value="">{{ __('Not in my suppliers list: type a name below') }}</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
    <input type="text" name="supplier_name" maxlength="255" placeholder="{{ __('Supplier name (if not in the list)') }}" style="margin-top:6px;">
    <div class="row2"><div><label>{{ __('Their reference') }}</label><input type="text" name="reference" maxlength="60"></div><div><label>{{ __('For') }}</label><input type="text" name="description" maxlength="255" placeholder="{{ __('e.g. Transfers, Lodge, 4 nights') }}"></div></div>
    <div class="row2"><div><label>{{ __('Amount') }}</label><input type="number" name="total" step="0.01" min="0.01" required></div><div><label>{{ __('Currency') }}</label><input type="text" name="currency" value="{{ $settings->default_currency ?? 'USD' }}" maxlength="3" required style="text-transform:uppercase"></div></div>
    <div class="row2"><div><label>{{ __('Bill date') }}</label><input type="date" name="bill_date" value="{{ now()->toDateString() }}" required></div><div><label>{{ __('Due date') }}</label><input type="date" name="due_date"></div></div>
    <label>{{ __('Against booking') }} <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">({{ __('optional: shows the booking\'s profit') }})</span></label>
    <select name="booking_id"><option value="">{{ __('None') }}</option>@foreach($bookings as $bk)<option value="{{ $bk->id }}">{{ $bk->code ?: '#'.$bk->id }} · {{ trim($bk->first_name.' '.$bk->last_name) }}</option>@endforeach</select>
    <div class="foot"><button type="button" class="tp-btn tp-btn-ghost" onclick="closeM('bill-new')">{{ __('Cancel') }}</button><button class="tp-btn tp-btn-primary">{{ __('Add bill') }}</button></div>
  </form>
</div>

{{-- Pay a bill --}}
<div class="tp-modal-bg" id="m-bill-pay" onclick="if(event.target===this)closeM('bill-pay')">
  <form class="tp-modal" method="POST" id="bill-pay-form">
    @csrf
    <h3>{{ __('Record a payment to the supplier') }}</h3>
    <div id="bill-pay-sub" style="color:#888;font-size:12.5px;"></div>
    <div class="row2"><div><label>{{ __('Amount') }}</label><input type="number" name="amount" id="bill-pay-amount" step="0.01" min="0.01" required></div><div><label>{{ __('Date') }}</label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div></div>
    <label>{{ __('Method') }}</label><select name="method"><option value="bank">{{ __('Bank transfer') }}</option><option value="cash">{{ __('Cash') }}</option><option value="card">{{ __('Card') }}</option><option value="mobile_money">{{ __('Mobile money') }}</option><option value="other">{{ __('Other') }}</option></select>
    <label>{{ __('Reference') }}</label><input type="text" name="reference" maxlength="191">
    <div class="foot"><button type="button" class="tp-btn tp-btn-ghost" onclick="closeM('bill-pay')">{{ __('Cancel') }}</button><button class="tp-btn tp-btn-primary">{{ __('Record payment') }}</button></div>
  </form>
</div>
<script>
function openM(id) { document.getElementById('m-' + id).classList.add('open'); }
function closeM(id) { document.getElementById('m-' + id).classList.remove('open'); }
function payBill(id, who, cur, bal) {
    document.getElementById('bill-pay-form').action = "{{ url('user/tourpay/bills') }}/" + id + "/payments";
    document.getElementById('bill-pay-sub').textContent = who + ' · ' + cur + ' ' + bal.toFixed(2) + ' {{ __('still owed') }}';
    var a = document.getElementById('bill-pay-amount'); a.value = bal.toFixed(2); a.max = bal.toFixed(2); openM('bill-pay');
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('.tp-modal-bg.open').forEach(function (m) { m.classList.remove('open'); }); });
</script>
@endsection
