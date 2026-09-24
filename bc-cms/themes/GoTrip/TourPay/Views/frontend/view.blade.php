@extends('layouts.user')
@section('content')
<style>
/* ── Base ────────────────────────────────────────────── */
.tp * { box-sizing: border-box; }
.tp-back {
    display: inline-flex; align-items: center; gap: 6px; font-size: 12px;
    color: #aaa; text-decoration: none; margin-bottom: 20px; transition: color .12s;
}
.tp-back:hover { color: #0a0a0a; }

/* ── Page header ─────────────────────────────────────── */
.tp-view-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
}
.tp-view-top-left { display: flex; align-items: center; gap: 14px; }
.tp-view-ref { font-size: 22px; font-weight: 800; color: #0a0a0a; letter-spacing: -.03em; }
.tp-view-meta { font-size: 12px; color: #aaa; margin-top: 3px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tp-view-meta span { display: flex; align-items: center; gap: 4px; }

/* ── Status pill ─────────────────────────────────────── */
.tp-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 100px; font-size: 12px; font-weight: 700;
    letter-spacing: .02em; flex-shrink: 0;
}
.tp-status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .5; }
.tp-pill-draft     { background: #f4f4f5; color: #71717a; }
.tp-pill-sent      { background: #eff6ff; color: #2563eb; }
.tp-pill-paid      { background: #f0fdf4; color: #16a34a; }
.tp-pill-accepted  { background: #f0fdf4; color: #16a34a; }
.tp-pill-cancelled { background: #fff1f2; color: #e11d48; }
.tp-pill-part_paid { background: #fff7ed; color: #c2410c; }
.tp-pill-overdue   { background: #fff1f2; color: #e11d48; }
.tp-pill-declined  { background: #fff1f2; color: #e11d48; }
.tp-pill-void      { background: #f4f4f5; color: #71717a; }
.tp-pill-credited  { background: #f4f4f5; color: #71717a; }
.tp-pill-credit_note { background: #f0f9ff; color: #0369a1; }

/* ── Two columns: the document, and what is going on with it ── */
.tp-view-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
@media (max-width: 1100px) { .tp-view-grid { grid-template-columns: 1fr; } }
.tp-side { display: flex; flex-direction: column; gap: 14px; }
.tp-card2 { background: #fff; border: 1px solid #ebebeb; border-radius: 10px; padding: 16px 18px; }
.tp-card2 h4 { margin: 0 0 10px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #888; display: flex; justify-content: space-between; align-items: center; }
.tp-bal-row { display: flex; justify-content: space-between; font-size: 13px; padding: 3px 0; color: #555; }
.tp-bal-row.big { font-size: 18px; font-weight: 800; color: #0a0a0a; border-top: 1px solid #f0f0f0; margin-top: 6px; padding-top: 10px; letter-spacing: -.02em; }
.tp-bal-row.big.zero { color: #16a34a; }
.tp-bal-row.late { color: #e11d48; font-weight: 700; }
.tp-bar { height: 6px; background: #f0f0f0; border-radius: 99px; overflow: hidden; margin: 8px 0 2px; }
.tp-bar > i { display: block; height: 100%; background: #16a34a; }
.tp-pay-row { display: flex; justify-content: space-between; gap: 10px; padding: 9px 0; border-top: 1px solid #f3f3f3; font-size: 13px; }
.tp-pay-row:first-of-type { border-top: 0; }
.tp-pay-row .m { color: #999; font-size: 11.5px; }
.tp-pay-row form { display: inline; }
.tp-pay-row button.x { border: 0; background: none; color: #bbb; cursor: pointer; font-size: 12px; }
.tp-pay-row button.x:hover { color: #e11d48; }
.tp-tl { list-style: none; margin: 0; padding: 0; }
.tp-tl li { position: relative; padding: 0 0 12px 18px; font-size: 12.5px; color: #555; }
.tp-tl li::before { content: ''; position: absolute; left: 3px; top: 5px; width: 7px; height: 7px; border-radius: 50%; background: #0a0a0a; }
.tp-tl li::after { content: ''; position: absolute; left: 6px; top: 14px; bottom: -2px; width: 1px; background: #e5e5e5; }
.tp-tl li:last-child::after { display: none; }
.tp-tl li small { display: block; color: #aaa; font-size: 11px; }
.tp-tl li.dim::before { background: #d4d4d8; }
.tp-linkrow { font-size: 12.5px; padding: 3px 0; }
.tp-linkrow a { color: #0a0a0a; font-weight: 600; }
.tp-ab-warn { background: #fff; color: #e11d48 !important; border: 1.5px solid #fecdd3; }
.tp-ab-warn:hover { background: #fff1f2; }
.tp-modal select, .tp-modal textarea { width: 100%; padding: 10px 13px; border: 1.5px solid #e4e4e4; border-radius: 7px; font-size: 14px; margin-bottom: 18px; background: #fff; }
.tp-modal .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.tp-pill-expired   { background: #fffbeb; color: #d97706; }

/* ── Action bar ──────────────────────────────────────── */
.tp-action-bar {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 10px 14px; margin-bottom: 22px;
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.tp-action-divider {
    width: 1px; height: 28px; background: #ebebeb; flex-shrink: 0; margin: 0 2px;
}

/* Button variants */
.tp-ab-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 15px; border-radius: 7px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; text-decoration: none !important;
    transition: all .12s; white-space: nowrap; letter-spacing: -.01em;
}
/* Primary */
.tp-ab-primary { background: #0a0a0a; color: #fff !important; }
.tp-ab-primary:hover { background: #222; }
/* Outlined */
.tp-ab-outline { background: #fff; color: #333 !important; border: 1.5px solid #e4e4e4; }
.tp-ab-outline:hover { border-color: #0a0a0a; color: #0a0a0a !important; }
/* WhatsApp */
.tp-ab-wa { background: #25d366; color: #fff !important; }
.tp-ab-wa:hover { background: #1ebe5d; }
/* Paid */
.tp-ab-paid { background: #f0fdf4; color: #16a34a !important; border: 1.5px solid #bbf7d0; }
.tp-ab-paid:hover { background: #dcfce7; border-color: #86efac; }

/* ── Document frame ──────────────────────────────────── */
.tp-doc-frame {
    background: #f2f2f2;
    background-image: radial-gradient(circle at 1px 1px, #ddd 1px, transparent 0);
    background-size: 20px 20px;
    padding: 40px 32px; border-radius: 12px;
    display: flex; justify-content: center; align-items: flex-start;
}
.tp-doc-inner {
    width: 100%; max-width: 720px; background: #fff;
    border-radius: 6px; overflow: hidden;
    box-shadow: 0 4px 32px rgba(0,0,0,.13), 0 1px 4px rgba(0,0,0,.06);
}

/* ── Toast ───────────────────────────────────────────── */
.tp-toast {
    position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(12px);
    background: #0a0a0a; color: #fff; padding: 11px 22px; border-radius: 100px;
    font-size: 13px; font-weight: 600; z-index: 99999; white-space: nowrap;
    opacity: 0; transition: opacity .25s, transform .25s; pointer-events: none;
}
.tp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

/* ── Modals ──────────────────────────────────────────── */
.tp-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4);
    z-index: 9999; align-items: center; justify-content: center;
    backdrop-filter: blur(2px);
}
.tp-modal-overlay.open { display: flex; }
.tp-modal {
    background: #fff; border-radius: 12px; width: 100%; max-width: 420px;
    padding: 28px; box-shadow: 0 16px 56px rgba(0,0,0,.2); position: relative;
    animation: tp-modal-in .18s ease;
}
@keyframes tp-modal-in {
    from { opacity:0; transform:scale(.97) translateY(6px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}
.tp-modal-icon {
    width: 44px; height: 44px; border-radius: 10px; display: flex;
    align-items: center; justify-content: center; font-size: 20px; margin-bottom: 14px;
}
.tp-modal-icon.email { background: #eff6ff; color: #2563eb; }
.tp-modal-icon.wa    { background: #f0fdf4; color: #25d366; }
.tp-modal h3 { font-size: 16px; font-weight: 800; color: #0a0a0a; margin: 0 0 5px; letter-spacing: -.02em; }
.tp-modal p  { font-size: 13px; color: #888; margin: 0 0 20px; line-height: 1.5; }
.tp-modal label {
    display: block; font-size: 11px; font-weight: 700; color: #777;
    text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px;
}
.tp-modal input {
    width: 100%; padding: 10px 13px; border: 1.5px solid #e4e4e4;
    border-radius: 7px; font-size: 14px; color: #0a0a0a; outline: none;
    margin-bottom: 18px; transition: border-color .15s, box-shadow .15s;
}
.tp-modal input:focus { border-color: #0a0a0a; box-shadow: 0 0 0 3px rgba(10,10,10,.06); }
.tp-modal-error {
    display: none; font-size: 12px; color: #e11d48; background: #fff1f2;
    border-radius: 6px; padding: 9px 12px; margin-bottom: 14px; line-height: 1.5;
}
.tp-modal-actions { display: flex; gap: 8px; justify-content: flex-end; }
.tp-modal-cancel {
    padding: 9px 18px; border: 1.5px solid #e4e4e4; border-radius: 7px;
    font-size: 13px; font-weight: 600; color: #555; background: #fff;
    cursor: pointer; transition: all .12s;
}
.tp-modal-cancel:hover { border-color: #aaa; color: #0a0a0a; }
.tp-modal-send {
    padding: 9px 20px; border: none; border-radius: 7px;
    font-size: 13px; font-weight: 700; cursor: pointer; transition: all .12s;
    display: inline-flex; align-items: center; gap: 7px;
}
.tp-modal-send.email { background: #0a0a0a; color: #fff; }
.tp-modal-send.email:hover { background: #222; }
.tp-modal-send.wa    { background: #25d366; color: #fff; }
.tp-modal-send.wa:hover { background: #1ebe5d; }
.tp-modal-send:disabled { opacity: .6; cursor: not-allowed; }
</style>

<div class="tp">

<a href="{{ route('tourpay.vendor.index') }}" class="tp-back">
    <i class="icofont-arrow-left"></i> {{ __("Back to TourPay") }}
</a>

{{-- ── Page header ── --}}
<div class="tp-view-top">
    <div class="tp-view-top-left">
        <div>
            <div class="tp-view-ref">{{ $row->invoice_number }}</div>
            <div class="tp-view-meta">
                <span><i class="icofont-file-document"></i> {{ ucfirst($row->type) }}</span>
                <span>·</span>
                <span><i class="icofont-calendar"></i> {{ $row->created_at->format('d M Y') }}</span>
                @if($row->client_name)
                <span>·</span>
                <span><i class="icofont-user"></i> {{ $row->client_name }}</span>
                @endif
            </div>
        </div>
        <div class="tp-status-pill tp-pill-{{ $row->display_status }}">{{ $row->status_label }}</div>
    </div>
    <div style="font-size:20px;font-weight:800;color:#0a0a0a;letter-spacing:-.03em;white-space:nowrap;text-align:right;">
        <span style="font-size:13px;font-weight:600;color:#aaa;margin-right:3px;vertical-align:.15em;">{{ $row->currency }}</span>{{ number_format($row->total, 2) }}
        @if($row->type === 'invoice' && $row->status !== 'void')<div style="font-size:12px;font-weight:600;color:{{ $row->balance() > 0 ? ($row->isOverdue() ? '#e11d48' : '#777') : '#16a34a' }};letter-spacing:0;">{{ $row->balance() > 0 ? __('Balance due :a', ['a' => $row->currency.' '.number_format($row->balance(), 2)]) : __('Paid in full') }}</div>@endif
    </div>
</div>

@include('admin.message')

{{-- ── Action bar ── --}}
<div class="tp-action-bar">

    {{-- Group 1: Primary action --}}
    <a href="{{ route('tourpay.vendor.pdf', $row->id) }}" class="tp-ab-btn tp-ab-primary" target="_blank">
        <i class="icofont-download"></i> {{ __("Download PDF") }}
    </a>

    <div class="tp-action-divider"></div>

    {{-- Group 2: Share --}}
    <button type="button" class="tp-ab-btn tp-ab-outline" onclick="openModal('email')">
        <i class="icofont-envelope"></i> {{ __("Email") }}
    </button>
    <button type="button" class="tp-ab-btn tp-ab-wa" onclick="openModal('wa')">
        <i class="icofont-brand-whatsapp"></i> {{ __("WhatsApp") }}
    </button>
    <button type="button" class="tp-ab-btn tp-ab-outline" onclick="copyPayLink()">
        <i class="icofont-link"></i> {{ __("Copy Link") }}
    </button>

    <div class="tp-action-divider"></div>

    {{-- Group 3: Manage --}}
    @if(!in_array($row->status, ['paid','void','accepted','declined','expired']))
    <a href="{{ route('tourpay.vendor.edit', $row->id) }}" class="tp-ab-btn tp-ab-outline">
        <i class="icofont-edit"></i> {{ __("Edit") }}
    </a>
    @endif

    <a href="{{ route('tourpay.vendor.edit', $row->id) }}" class="tp-ab-btn tp-ab-outline" style="display:none"></a>

    @if($row->type === 'invoice' && !in_array($row->status, ['paid','void']) && $row->balance() > 0)
    <button type="button" class="tp-ab-btn tp-ab-paid" onclick="openModal('pay')"><i class="icofont-money"></i> {{ __("Record a payment") }}</button>
    @endif
    @if($row->status === 'draft' && $row->items->count())
    <form method="POST" action="{{ route('tourpay.vendor.issue', $row->id) }}" style="display:contents;">@csrf<button type="submit" class="tp-ab-btn tp-ab-outline" title="{{ __('Mark as sent without e-mailing it') }}"><i class="icofont-check"></i> {{ __("Mark as sent") }}</button></form>
    @endif
    @if($row->type === 'quotation' && !in_array($row->status, ['void','declined','expired']) && !$children->where('status', '!=', 'void')->count())
    <form method="POST" action="{{ route('tourpay.vendor.convert', $row->id) }}" style="display:contents;">@csrf<button type="submit" class="tp-ab-btn tp-ab-primary"><i class="icofont-exchange"></i> {{ __("Convert to invoice") }}</button></form>
    @endif

    @if($row->type === 'invoice' && !in_array($row->status, ['draft','void','credited']) && ($row->total - $row->credit_total) > 0)
    <button type="button" class="tp-ab-btn tp-ab-outline" onclick="openModal('credit')"><i class="icofont-minus-circle"></i> {{ __("Credit note") }}</button>
    @endif
    @if($row->type === 'invoice' && $row->refundDue() > 0)
    <button type="button" class="tp-ab-btn tp-ab-warn" onclick="openModal('refund')"><i class="icofont-reply"></i> {{ __("Record refund") }} · {{ $row->currency }} {{ number_format($row->refundDue(), 2) }}</button>
    @endif

    <div class="tp-action-divider"></div>
    <form method="POST" action="{{ route('tourpay.vendor.duplicate', $row->id) }}" style="display:contents;">@csrf<button type="submit" class="tp-ab-btn tp-ab-outline"><i class="icofont-copy"></i> {{ __("Duplicate") }}</button></form>
    @if($row->status !== 'void')
    <form method="POST" action="{{ route('tourpay.vendor.void', $row->id) }}" style="display:contents;" onsubmit="return confirm('{{ __('Void this document? It stays on record but no longer counts.') }}')">@csrf<button type="submit" class="tp-ab-btn tp-ab-warn"><i class="icofont-ban"></i> {{ __("Void") }}</button></form>
    @endif

</div>

{{-- ── Document preview ── --}}
<div class="tp-view-grid">
<div class="tp-doc-frame">
    <div class="tp-doc-inner">
        @include('TourPay::pdf.template' . max(1, min(5, (int) $row->template)), ['row' => $row, 'preview' => true, 'company' => $company ?? ''])
    </div>
</div>

<aside class="tp-side">
    @if($row->type === 'invoice')
    <div class="tp-card2">
        <h4>{{ __('Payment') }} <span class="tp-status-pill tp-pill-{{ $row->display_status }}" style="padding:2px 9px;font-size:10.5px;">{{ $row->status_label }}</span></h4>
        @php $pct = $row->payable() > 0 ? min(100, max(0, round($row->amount_paid / $row->payable() * 100))) : 0; @endphp
        <div class="tp-bal-row"><span>{{ __('Total') }}</span><span>{{ $row->currency }} {{ number_format($row->total, 2) }}</span></div>
        @if($row->credit_total > 0)<div class="tp-bal-row"><span>{{ __('Credited') }}</span><span>− {{ $row->currency }} {{ number_format($row->credit_total, 2) }}</span></div>@endif
        <div class="tp-bal-row"><span>{{ __('Paid') }}</span><span>{{ $row->currency }} {{ number_format($row->amount_paid, 2) }}</span></div>
        <div class="tp-bar"><i style="width:{{ $pct }}%"></i></div>
        @if($row->refundDue() > 0)<div class="tp-bal-row late"><span>{{ __('Refund due to client') }}</span><span>{{ $row->currency }} {{ number_format($row->refundDue(), 2) }}</span></div>@endif
        @if($row->status !== 'void')
        <div class="tp-bal-row big {{ $row->balance() <= 0 ? 'zero' : '' }}"><span>{{ $row->balance() <= 0 ? __('Paid in full') : __('Balance due') }}</span><span>{{ $row->currency }} {{ number_format(max(0, $row->balance()), 2) }}</span></div>
        @if($row->due_date)<div class="tp-bal-row {{ $row->isOverdue() ? 'late' : '' }}"><span>{{ __('Due') }}</span><span>{{ $row->due_date->format('d M Y') }}{{ $row->isOverdue() ? ' · '.__(':n days late', ['n' => (int) $row->due_date->diffInDays(now())]) : '' }}</span></div>@endif
        @endif
        @if($row->status !== 'void' && $row->balance() > 0)<button type="button" class="tp-ab-btn tp-ab-paid" style="width:100%;justify-content:center;margin-top:12px;" onclick="openModal('pay')"><i class="icofont-money"></i> {{ __('Record a payment') }}</button>@endif
    </div>

    @php $pendingRows = $row->payments->where('status', 'pending'); @endphp
    @if($pendingRows->count())
    <div class="tp-card2" style="border-color:#fde68a;background:#fffbeb;">
        <h4>{{ __('Waiting for you to confirm') }} <span style="font-weight:600;text-transform:none;letter-spacing:0;">{{ $pendingRows->count() }}</span></h4>
        @foreach($pendingRows as $p)
        <div class="tp-pay-row" style="flex-direction:column;">
            <div><div style="font-weight:700;color:#0a0a0a;">{{ $row->currency }} {{ number_format($p->amount, 2) }} <span class="m">{{ __('says the client') }}</span></div>
                 <div class="m">{{ $p->created_at->format('d M, H:i') }} · {{ __('reference') }} {{ $p->reference }}@if($p->notes) · {{ $p->notes }}@endif</div>
                 @if($p->proof_path)<div class="m"><a href="{{ route('tourpay.vendor.payments.proof', [$row->id, $p->id]) }}" target="_blank" style="color:#0a0a0a;font-weight:600;">{{ __('View proof of payment') }}</a></div>@endif</div>
            <div style="display:flex;gap:6px;margin-top:8px;">
                <form method="POST" action="{{ route('tourpay.vendor.payments.approve', [$row->id, $p->id]) }}">@csrf<button class="tp-ab-btn tp-ab-paid" style="padding:6px 12px;">{{ __('It arrived: confirm') }}</button></form>
                <form method="POST" action="{{ route('tourpay.vendor.payments.reject', [$row->id, $p->id]) }}" onsubmit="return confirm('{{ __('Reject this payment?') }}')">@csrf<button class="tp-ab-btn tp-ab-warn" style="padding:6px 12px;">{{ __('Not received') }}</button></form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="tp-card2">
        <h4>{{ __('Payment schedule') }} @if($row->status !== 'void' && !in_array($row->status, ['paid']))<a href="#" onclick="openModal('schedule');return false;" style="font-weight:600;text-transform:none;letter-spacing:0;color:#0a0a0a;">{{ count($schedule) ? __('Change') : __('Set up') }}</a>@endif</h4>
        @forelse($schedule as $it)
        <div class="tp-pay-row">
            <div><div style="font-weight:700;color:#0a0a0a;">{{ $it['label'] }}</div><div class="m" style="{{ $it['late'] ? 'color:#e11d48;font-weight:600;' : '' }}">{{ __('due :d', ['d' => $it['due_date']->format('d M Y')]) }}{{ $it['late'] ? ' · '.__('late') : '' }}</div></div>
            <div style="text-align:right;"><div style="font-weight:700;">{{ $row->currency }} {{ number_format($it['amount'], 2) }}</div><div class="m" style="color:{{ $it['remaining'] <= 0.004 ? '#16a34a' : '#999' }}">{{ $it['remaining'] <= 0.004 ? __('paid') : __(':a to go', ['a' => number_format($it['remaining'], 2)]) }}</div></div>
        </div>
        @empty
        <div style="color:#aaa;font-size:12.5px;">{{ __('One payment. Split it into a deposit and a balance if you like.') }}</div>
        @endforelse
    </div>

    <div class="tp-card2">
        <h4>{{ __('Payments received') }} <span style="font-weight:600;text-transform:none;letter-spacing:0;">{{ $row->payments->where('status', 'confirmed')->count() }}</span></h4>
        @forelse($row->payments->where('status', 'confirmed') as $p)
        <div class="tp-pay-row">
            <div><div style="font-weight:700;color:{{ $p->amount < 0 ? '#e11d48' : '#0a0a0a' }};">{{ $p->amount < 0 ? '− ' : '' }}{{ $row->currency }} {{ number_format(abs($p->amount), 2) }}{{ $p->amount < 0 ? ' · '.__('refund') : '' }}</div>
                 <div class="m">{{ $p->paid_at->format('d M Y') }} · {{ ucfirst(str_replace('_', ' ', $p->method)) }}@if($p->reference) · {{ $p->reference }}@endif</div>
                 @if($p->notes)<div class="m">{{ $p->notes }}</div>@endif</div>
            @if($row->status !== 'void')
            <form method="POST" action="{{ route('tourpay.vendor.payments.delete', [$row->id, $p->id]) }}" onsubmit="return confirm('{{ __('Remove this payment?') }}')">@csrf @method('DELETE')<button class="x" title="{{ __('Remove') }}"><i class="icofont-close"></i></button></form>
            @endif
        </div>
        @empty
        <div style="color:#aaa;font-size:12.5px;">{{ __('Nothing received yet.') }}</div>
        @endforelse
    </div>
    @else
    <div class="tp-card2">
        <h4>{{ __('Quotation') }} <span class="tp-status-pill tp-pill-{{ $row->display_status }}" style="padding:2px 9px;font-size:10.5px;">{{ $row->status_label }}</span></h4>
        <div class="tp-bal-row"><span>{{ __('Total') }}</span><span>{{ $row->currency }} {{ number_format($row->total, 2) }}</span></div>
        @if($row->valid_days && $row->issue_date)<div class="tp-bal-row"><span>{{ __('Valid until') }}</span><span>{{ $row->issue_date->copy()->addDays($row->valid_days)->format('d M Y') }}</span></div>@endif
        <div style="font-size:12px;color:#999;margin-top:8px;">{{ __('The client can accept or decline from their link. Convert it to an invoice once they accept.') }}</div>
    </div>
    @endif

    <div class="tp-card2">
        <h4>{{ __('History') }}</h4>
        @php
            $events = collect([['t' => $row->created_at, 'label' => __('Created')]]);
            if ($row->sent_at)   { $events->push(['t' => $row->sent_at,   'label' => __('Sent to :c', ['c' => $row->client_name ?: __('the client')])]); }
            if ($row->viewed_at) { $events->push(['t' => $row->viewed_at, 'label' => __('Opened by the client')]); }
            foreach ($row->payments as $p) { $events->push(['t' => $p->created_at ?? $p->paid_at, 'label' => __('Payment of :a received', ['a' => $row->currency.' '.number_format($p->amount, 2)])]); }
            if ($row->voided_at) { $events->push(['t' => $row->voided_at, 'label' => __('Voided')]); }
        @endphp
        <ul class="tp-tl">
            @foreach($events->sortByDesc('t') as $e)
            <li><span>{{ $e['label'] }}</span><small>{{ \Illuminate\Support\Carbon::parse($e['t'])->format('d M Y, H:i') }}</small></li>
            @endforeach
            @if(!$row->sent_at && $row->status === 'draft')<li class="dim"><span>{{ __('Not sent yet') }}</span></li>@endif
            @if($row->sent_at && !$row->viewed_at && $row->status !== 'void' && $row->status !== 'paid')<li class="dim"><span>{{ __('Not opened yet') }}</span></li>@endif
        </ul>
    </div>

    @if($booking || $row->parent || $children->count() || $row->customer_id)
    <div class="tp-card2">
        <h4>{{ __('Linked to') }}</h4>
        @if($booking)<div class="tp-linkrow">{{ __('Booking') }}: <a href="{{ route('vendor.bookings.ops', $booking->id) }}">{{ $booking->code ?: '#'.$booking->id }}</a></div>@endif
        @if($row->parent)<div class="tp-linkrow">{{ $row->type === 'credit_note' ? __('Credits invoice') : __('From quotation') }}: <a href="{{ route('tourpay.vendor.view', $row->parent->id) }}">{{ $row->parent->invoice_number }}</a></div>@endif
        @foreach($children as $ch)<div class="tp-linkrow">{{ $ch->type === 'credit_note' ? __('Credit note') : __('Invoice') }}: <a href="{{ route('tourpay.vendor.view', $ch->id) }}">{{ $ch->invoice_number }}</a></div>@endforeach
        @if($row->customer_id)<div class="tp-linkrow">{{ __('Customer') }}: <a href="{{ route('vendor.customers.index', ['s' => $row->client_email ?: $row->client_name]) }}">{{ $row->client_name }}</a></div>@endif
    </div>
    @endif
</aside>
</div>

</div>{{-- /.tp --}}

<div class="tp-toast" id="tp-toast"></div>

{{-- Email modal --}}
<div class="tp-modal-overlay" id="modal-email" onclick="closeOnBg(event,'email')">
    <div class="tp-modal">
        <div class="tp-modal-icon email"><i class="icofont-envelope"></i></div>
        <h3>{{ __("Send by Email") }}</h3>
        <p>{{ __("Invoice :n will be sent with a PDF attachment.", ['n' => $row->invoice_number]) }}</p>
        <form method="POST" action="{{ route('tourpay.vendor.send-email', $row->id) }}">
            @csrf
            <label>{{ __("Recipient Email") }}</label>
            <input type="email" name="email" id="modal-email-input" value="{{ $row->client_email }}" required placeholder="client@example.com">
            <div class="tp-modal-actions">
                <button type="button" class="tp-modal-cancel" onclick="closeModal('email')">{{ __("Cancel") }}</button>
                <button type="submit" class="tp-modal-send email"><i class="icofont-send-mail"></i> {{ __("Send Email") }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Payment modal --}}
<div class="tp-modal-overlay" id="modal-pay" onclick="closeOnBg(event,'pay')">
    <div class="tp-modal">
        <div class="tp-modal-icon wa"><i class="icofont-money"></i></div>
        <h3>{{ __("Record a payment") }}</h3>
        <p>{{ __(":n · :a still to pay. The status follows the payments you record.", ['n' => $row->invoice_number, 'a' => $row->currency.' '.number_format(max(0, $row->balance()), 2)]) }}</p>
        <form method="POST" action="{{ route('tourpay.vendor.payments.add', $row->id) }}">
            @csrf
            <div class="row2">
                <div><label>{{ __("Amount") }}</label><input type="number" name="amount" id="modal-pay-input" step="0.01" min="0.01" max="{{ max(0.01, $row->balance()) }}" value="{{ number_format(max(0, $row->balance()), 2, '.', '') }}" required></div>
                <div><label>{{ __("Date") }}</label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div>
            </div>
            <label>{{ __("Method") }}</label>
            <select name="method">@foreach(['bank' => __('Bank transfer (EFT)'), 'cash' => __('Cash'), 'card' => __('Card'), 'mobile_money' => __('Mobile money'), 'paypal' => 'PayPal', 'stripe' => 'Stripe', 'paystack' => 'Paystack', 'other' => __('Other')] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <label>{{ __("Reference") }}</label><input type="text" name="reference" maxlength="191" placeholder="{{ __('Bank reference, receipt number…') }}">
            <label>{{ __("Note") }}</label><input type="text" name="notes" maxlength="1000">
            <label style="display:flex;gap:8px;align-items:center;text-transform:none;letter-spacing:0;font-size:13px;color:#555;font-weight:500;"><input type="checkbox" name="send_receipt" value="1" style="width:auto;margin:0;" {{ $row->client_email ? 'checked' : 'disabled' }}> {{ __('E-mail a receipt to :e', ['e' => $row->client_email ?: __('the client (no e-mail on file)')]) }}</label>
            <div class="tp-modal-actions" style="margin-top:14px;">
                <button type="button" class="tp-modal-cancel" onclick="closeModal('pay')">{{ __("Cancel") }}</button>
                <button type="submit" class="tp-modal-send email"><i class="icofont-check"></i> {{ __("Record payment") }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Credit note modal --}}
<div class="tp-modal-overlay" id="modal-credit" onclick="closeOnBg(event,'credit')">
    <div class="tp-modal">
        <div class="tp-modal-icon email"><i class="icofont-minus-circle"></i></div>
        <h3>{{ __("Issue a credit note") }}</h3>
        <p>{{ __("Reduces what :n owes. If the client already paid, the difference becomes a refund due to them.", ['n' => $row->invoice_number]) }}</p>
        <form method="POST" action="{{ route('tourpay.vendor.credit-note', $row->id) }}">
            @csrf
            <label>{{ __("Amount to credit") }} <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">({{ __('leave empty for all of it: :a', ['a' => $row->currency.' '.number_format($row->total - $row->credit_total, 2)]) }})</span></label>
            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $row->total - $row->credit_total }}">
            <label>{{ __("Reason") }}</label><input type="text" name="reason" required maxlength="240" placeholder="{{ __('e.g. Guest cancelled, Flight not taken') }}">
            <div class="tp-modal-actions"><button type="button" class="tp-modal-cancel" onclick="closeModal('credit')">{{ __("Cancel") }}</button><button type="submit" class="tp-modal-send email">{{ __("Issue credit note") }}</button></div>
        </form>
    </div>
</div>

{{-- Refund modal --}}
<div class="tp-modal-overlay" id="modal-refund" onclick="closeOnBg(event,'refund')">
    <div class="tp-modal">
        <div class="tp-modal-icon wa"><i class="icofont-reply"></i></div>
        <h3>{{ __("Record a refund") }}</h3>
        <p>{{ __("Record money you have sent back. Send it first, through the same way it came; this only keeps the record.") }}</p>
        <form method="POST" action="{{ route('tourpay.vendor.refund', $row->id) }}">
            @csrf
            <div class="row2">
                <div><label>{{ __("Amount") }}</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $row->refundDue() }}" value="{{ number_format($row->refundDue(), 2, '.', '') }}" required></div>
                <div><label>{{ __("Date") }}</label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div>
            </div>
            <label>{{ __("Method") }}</label>
            <select name="method">@foreach(['bank' => __('Bank transfer (EFT)'), 'cash' => __('Cash'), 'card' => __('Card'), 'mobile_money' => __('Mobile money'), 'paypal' => 'PayPal', 'stripe' => 'Stripe', 'paystack' => 'Paystack', 'other' => __('Other')] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <label>{{ __("Reference") }}</label><input type="text" name="reference" maxlength="191">
            <div class="tp-modal-actions"><button type="button" class="tp-modal-cancel" onclick="closeModal('refund')">{{ __("Cancel") }}</button><button type="submit" class="tp-modal-send email">{{ __("Record refund") }}</button></div>
        </form>
    </div>
</div>

{{-- Schedule modal --}}
<div class="tp-modal-overlay" id="modal-schedule" onclick="closeOnBg(event,'schedule')">
    <div class="tp-modal">
        <div class="tp-modal-icon email"><i class="icofont-calendar"></i></div>
        <h3>{{ __("Payment schedule") }}</h3>
        <p>{{ __("Split the :t into instalments. The client sees what is due next and can pay it online.", ['t' => $row->currency.' '.number_format($row->total, 2)]) }}</p>
        <form method="POST" action="{{ route('tourpay.vendor.schedule', $row->id) }}">
            @csrf
            <label>{{ __("How") }}</label>
            <select name="mode" onchange="document.getElementById('sch-dep').style.display=this.value==='deposit'?'':'none';document.getElementById('sch-split').style.display=this.value==='split'?'':'none';">
                <option value="deposit">{{ __("Deposit now, balance before travel") }}</option>
                <option value="split">{{ __("Equal parts, a month apart") }}</option>
                <option value="none">{{ __("One payment (remove schedule)") }}</option>
            </select>
            <div id="sch-dep" class="row2">
                <div><label>{{ __("Deposit %") }}</label><input type="number" name="percent" value="30" min="1" max="99"></div>
                <div><label>{{ __("Balance due (days before due date)") }}</label><input type="number" name="balance_days" value="14" min="0" max="365"></div>
            </div>
            <div id="sch-split" style="display:none"><label>{{ __("Number of payments") }}</label><input type="number" name="parts" value="3" min="2" max="12"></div>
            <div class="tp-modal-actions"><button type="button" class="tp-modal-cancel" onclick="closeModal('schedule')">{{ __("Cancel") }}</button><button type="submit" class="tp-modal-send email">{{ __("Save schedule") }}</button></div>
        </form>
    </div>
</div>

{{-- WhatsApp modal --}}
<div class="tp-modal-overlay" id="modal-wa" onclick="closeOnBg(event,'wa')">
    <div class="tp-modal">
        <div class="tp-modal-icon wa"><i class="icofont-brand-whatsapp"></i></div>
        <h3>{{ __("Send via WhatsApp") }}</h3>
        <p>{{ __("Sends the invoice link directly to the client via Vonage WhatsApp.") }}</p>
        <label>{{ __("WhatsApp Number") }} <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;font-size:10px;">+27831234567</span></label>
        <input type="tel" id="modal-wa-input" value="{{ $row->client_phone }}" placeholder="+27831234567">
        <div class="tp-modal-error" id="wa-error"></div>
        <div class="tp-modal-actions">
            <button type="button" class="tp-modal-cancel" onclick="closeModal('wa')">{{ __("Cancel") }}</button>
            <button type="button" class="tp-modal-send wa" id="wa-send-btn" onclick="sendWhatsApp()">
                <i class="icofont-brand-whatsapp"></i> <span id="wa-send-label">{{ __("Send") }}</span>
            </button>
        </div>
    </div>
</div>

<script>
var payUrl = '{{ route('tourpay.pay', $row->pay_token) }}';

/* ── Modal helpers ── */
function openModal(type) {
    document.getElementById('modal-' + type).classList.add('open');
    var inp = document.getElementById('modal-' + type + '-input');
    if (inp) { setTimeout(function(){ inp.focus(); inp.select(); }, 80); }
}
function closeModal(type) {
    document.getElementById('modal-' + type).classList.remove('open');
}
function closeOnBg(e, type) {
    if (e.target === document.getElementById('modal-' + type)) closeModal(type);
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal('email'); closeModal('wa'); closeModal('pay'); closeModal('schedule'); closeModal('credit'); closeModal('refund'); }
});

/* ── WhatsApp via Vonage ── */
function sendWhatsApp() {
    var phone  = document.getElementById('modal-wa-input').value.trim();
    var errBox = document.getElementById('wa-error');
    var btn    = document.getElementById('wa-send-btn');
    var label  = document.getElementById('wa-send-label');

    errBox.style.display = 'none';
    if (!phone) { document.getElementById('modal-wa-input').focus(); return; }

    btn.disabled = true;
    label.textContent = '{{ __("Sending…") }}';

    fetch('{{ route("tourpay.vendor.send-whatsapp", $row->id) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ phone: phone })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        label.textContent = '{{ __("Send") }}';
        if (data.success) {
            closeModal('wa');
            showToast('{{ __("WhatsApp message sent!") }}');
        } else {
            errBox.textContent = data.error || '{{ __("Failed to send. Please try again.") }}';
            errBox.style.display = 'block';
        }
    })
    .catch(function() {
        btn.disabled = false;
        label.textContent = '{{ __("Send") }}';
        errBox.textContent = '{{ __("Network error. Please try again.") }}';
        errBox.style.display = 'block';
    });
}

/* ── Copy link ── */
function copyPayLink() {
    var copy = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function(){ showToast('{{ __("Link copied to clipboard!") }}'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta);
            showToast('{{ __("Link copied to clipboard!") }}');
        }
    };
    copy(payUrl);
}

/* ── Toast ── */
function showToast(msg) {
    var t = document.getElementById('tp-toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 2800);
}
</script>
@endsection
