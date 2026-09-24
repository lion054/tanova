@extends('layouts.user')
@section('content')
<style>
/* ── Base ─────────────────────────────────────────────── */
.tp * { box-sizing: border-box; }
.tp-back {
    display: inline-flex; align-items: center; gap: 6px; font-size: 12px;
    color: #aaa; text-decoration: none; margin-bottom: 20px;
    transition: color .12s;
}
.tp-back:hover { color: #0a0a0a; }
.tp-form-title { font-size: 22px; font-weight: 800; color: #0a0a0a; margin: 0 0 3px; letter-spacing: -.03em; }
.tp-form-sub   { font-size: 13px; color: #aaa; margin: 0 0 24px; }

/* ── Layout ───────────────────────────────────────────── */
.tp-form-wrap { display: flex; gap: 20px; align-items: flex-start; }
.tp-form-main { flex: 1; min-width: 0; }
.tp-form-side { width: 272px; min-width: 272px; position: sticky; top: 72px; }

/* ── Cards ────────────────────────────────────────────── */
.tp-card {
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 20px 22px; margin-bottom: 14px;
}
.tp-card-header {
    display: flex; align-items: center; gap: 10px; margin-bottom: 18px;
    padding-bottom: 14px; border-bottom: 1px solid #f5f5f5;
}
.tp-card-num {
    width: 24px; height: 24px; border-radius: 6px; background: #0a0a0a;
    color: #fff; font-size: 11px; font-weight: 800; display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
}
.tp-card-title { font-size: 13px; font-weight: 700; color: #0a0a0a; letter-spacing: -.01em; }
.tp-card-title span { font-size: 11px; font-weight: 400; color: #aaa; margin-left: 6px; }

/* ── Type toggle ──────────────────────────────────────── */
.tp-type-toggle {
    display: flex; background: #f5f5f5; border-radius: 8px;
    padding: 3px; gap: 2px; margin-bottom: 20px;
}
.tp-type-btn {
    flex: 1; text-align: center; padding: 8px 12px; border-radius: 6px;
    font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s;
    user-select: none; border: none; background: transparent; color: #888;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.tp-type-btn.active { background: #fff; color: #0a0a0a; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.tp-type-btn:not(.active):hover { color: #555; }

/* ── Fields ───────────────────────────────────────────── */
.tp-field { margin-bottom: 14px; }
.tp-field:last-child { margin-bottom: 0; }
.tp-field label {
    display: block; font-size: 11px; font-weight: 600; color: #777;
    margin-bottom: 5px; text-transform: uppercase; letter-spacing: .06em;
}
.tp-field label .req { color: #e11d48; margin-left: 2px; }
.tp-field input,
.tp-field select,
.tp-field textarea {
    width: 100%; padding: 9px 12px; border: 1.5px solid #e8e8e8;
    border-radius: 7px; font-size: 13px; color: #222;
    background: #fff; transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.tp-field input:focus,
.tp-field select:focus,
.tp-field textarea:focus {
    border-color: #0a0a0a; box-shadow: 0 0 0 3px rgba(10,10,10,.06);
}
.tp-field textarea { min-height: 78px; resize: vertical; }
.tp-row { display: flex; gap: 12px; }
.tp-row .tp-field { flex: 1; min-width: 0; }

/* ── Items table ──────────────────────────────────────── */
.tp-items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.tp-items-table thead th {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #bbb; padding: 0 8px 10px; text-align: left;
}
.tp-items-table .tp-item-row td { padding: 4px 6px; vertical-align: top; }
.tp-items-table input {
    width: 100%; padding: 8px 10px; border: 1.5px solid #e8e8e8;
    border-radius: 6px; font-size: 12px; color: #222; background: #fff; outline: none;
    transition: border-color .15s;
}
.tp-items-table input:focus { border-color: #0a0a0a; }
.tp-items-table .tp-item-sub-input { margin-top: 4px; background: #fafafa !important; font-size: 11px !important; color: #aaa !important; }
.tp-item-total-cell {
    display: flex; align-items: center; justify-content: flex-end;
    padding: 8px 4px; font-size: 13px; font-weight: 700; color: #0a0a0a;
    min-width: 80px; white-space: nowrap;
}
.tp-add-item {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px;
    border: 1.5px dashed #e0e0e0; border-radius: 6px; background: none;
    font-size: 12px; font-weight: 600; color: #aaa; cursor: pointer;
    transition: all .12s; text-decoration: none; margin-top: 4px;
}
.tp-add-item:hover { border-color: #0a0a0a; color: #0a0a0a; }
.tp-remove-item {
    width: 28px; height: 28px; border: none; background: #f5f5f5;
    border-radius: 6px; cursor: pointer; color: #ccc; font-size: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: all .1s; margin-top: 6px;
}
.tp-remove-item:hover { background: #fff1f2; color: #e11d48; }

/* ── Summary ──────────────────────────────────────────── */
.tp-summary {
    background: #fafafa; border-radius: 8px; padding: 14px 16px; margin-top: 10px;
    border: 1px solid #f0f0f0;
}
.tp-summary-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 12px; color: #888; padding: 3px 0;
}
.tp-summary-row.total {
    font-size: 16px; font-weight: 800; color: #0a0a0a; letter-spacing: -.02em;
    border-top: 1px solid #e8e8e8; margin-top: 8px; padding-top: 10px;
}
.tp-summary-row.total span:last-child { font-size: 18px; }

/* ── Template picker ──────────────────────────────────── */
.tp-templates { display: grid; grid-template-columns: repeat(5,1fr); gap: 6px; }
.tp-tpl {
    border: 2px solid #e8e8e8; border-radius: 7px; cursor: pointer;
    text-align: center; padding: 0 0 7px; overflow: hidden;
    font-size: 9px; font-weight: 700; color: #aaa;
    text-transform: uppercase; letter-spacing: .05em;
    transition: all .12s; user-select: none;
}
.tp-tpl-preview {
    height: 42px; width: 100%; margin-bottom: 6px; position: relative; overflow: hidden;
}
/* Mini-preview bars representing each template's colour system */
.tp-tpl-1 .tp-tpl-preview { background: #fff8ee; }
.tp-tpl-1 .tp-tpl-preview::before { content:''; position:absolute; top:0; left:0; right:0; height:12px; background:#c8a96e; }
.tp-tpl-1 .tp-tpl-preview::after  { content:''; position:absolute; bottom:8px; left:8px; right:8px; height:2px; background:#f0ead8; }

.tp-tpl-2 .tp-tpl-preview { background: #f8faff; }
.tp-tpl-2 .tp-tpl-preview::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,#1a73e8,#0d47a1); }
.tp-tpl-2 .tp-tpl-preview::after  { content:''; position:absolute; top:14px; left:8px; width:40%; height:6px; border-radius:2px; background:#1a73e8; opacity:.2; }

.tp-tpl-3 .tp-tpl-preview { background: #111; }
.tp-tpl-3 .tp-tpl-preview::before { content:''; position:absolute; top:8px; right:10px; width:30%; height:8px; border-radius:2px; background:rgba(255,255,255,.25); }
.tp-tpl-3 .tp-tpl-preview::after  { content:''; position:absolute; bottom:8px; left:8px; right:8px; height:1px; background:rgba(255,255,255,.1); }

.tp-tpl-4 .tp-tpl-preview { background: #fdf6ee; }
.tp-tpl-4 .tp-tpl-preview::before { content:''; position:absolute; top:0; left:0; right:0; height:14px; background:#4a2c0a; }
.tp-tpl-4 .tp-tpl-preview::after  { content:''; position:absolute; bottom:8px; left:8px; right:8px; height:2px; background:#e8d5b5; }

.tp-tpl-5 .tp-tpl-preview { background: #fff; }
.tp-tpl-5 .tp-tpl-preview::before { content:''; position:absolute; top:10px; left:8px; right:8px; height:1px; background:#111; }
.tp-tpl-5 .tp-tpl-preview::after  { content:''; position:absolute; top:18px; left:8px; width:50%; height:4px; border-radius:2px; background:#f0f0f0; }

.tp-tpl.selected { border-color: #0a0a0a; color: #0a0a0a; }
.tp-tpl.selected .tp-tpl-preview { opacity: 1; }
.tp-tpl:not(.selected) .tp-tpl-preview { opacity: .85; }
.tp-tpl:hover { border-color: #888; }

/* ── Side card labels ─────────────────────────────────── */
.tp-side-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #aaa; margin-bottom: 6px; display: block;
}
.tp-side-total-wrap {
    background: #0a0a0a; border-radius: 8px; padding: 16px;
    margin-top: 10px; text-align: center;
}
.tp-side-total-label { font-size: 10px; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; }
.tp-side-total-amount { font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -.03em; line-height: 1; }
.tp-side-total-currency { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.5); margin-right: 3px; vertical-align: .15em; }

/* ── Save bar ─────────────────────────────────────────── */
.tp-save-bar {
    position: sticky; bottom: 0; z-index: 50;
    background: #fff; border: 1px solid #ebebeb; border-radius: 10px;
    padding: 12px 14px; display: flex; gap: 10px;
    box-shadow: 0 -4px 20px rgba(0,0,0,.06); margin-top: 14px;
}
.tp-save-btn {
    flex: 1; padding: 11px 14px; border-radius: 7px; font-size: 13px; font-weight: 700;
    border: none; cursor: pointer; transition: all .15s; display: flex;
    align-items: center; justify-content: center; gap: 7px;
    white-space: nowrap;
}
.tp-save-btn-draft { background: #f5f5f5; color: #555; }
.tp-save-btn-draft:hover { background: #ebebeb; }
.tp-save-btn-send  { background: #0a0a0a; color: #fff; }
.tp-save-btn-send:hover { background: #222; }

@media (max-width: 860px) {
    .tp-form-wrap { flex-direction: column; }
    .tp-form-side { width: 100%; position: static; min-width: 0; }
}

#tp-sug { display: none; position: absolute; z-index: 9999; background: #fff; border: 1px solid #e4e4e4; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.12); max-height: 320px; overflow-y: auto; font-size: 13px; }
#tp-sug .g { padding: 7px 12px 4px; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #999; }
#tp-sug .o { display: flex; gap: 10px; align-items: baseline; padding: 8px 12px; cursor: pointer; }
#tp-sug .o b { font-weight: 700; color: #0a0a0a; white-space: nowrap; }
#tp-sug .o span { color: #999; font-size: 12px; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; }
#tp-sug .o em { font-style: normal; font-size: 11.5px; color: #71717a; white-space: nowrap; margin-left: auto; }
#tp-sug .o:hover, #tp-sug .o.on { background: #f4f4f5; }
</style>

<div class="tp">
<a href="{{ route('tourpay.vendor.index') }}" class="tp-back">
    <i class="icofont-arrow-left"></i> {{ __("Back to TourPay") }}
</a>
<div class="tp-form-title">{{ $page_title }}</div>
<div class="tp-form-sub">{{ $type === 'quotation' ? __("Create a professional quotation for your client.") : __("Create a professional invoice for your client.") }}</div>

@include('admin.message')

<form method="POST" action="{{ route('tourpay.vendor.store', $row->id ?? -1) }}" id="tp-form">
    @csrf
    <input type="hidden" name="type"   id="tp-type-input"   value="{{ old('type',   $row->type   ?? $type) }}">
    <input type="hidden" name="action" id="tp-action-input" value="draft">

    <div class="tp-form-wrap">

        {{-- ── LEFT ── --}}
        <div class="tp-form-main">

            {{-- Type toggle --}}
            <div class="tp-type-toggle">
                <button type="button" class="tp-type-btn {{ ($row->type ?? $type) === 'invoice'   ? 'active' : '' }}" data-type="invoice"   onclick="setType('invoice')">
                    <i class="icofont-money"></i> {{ __("Invoice") }}
                </button>
                <button type="button" class="tp-type-btn {{ ($row->type ?? $type) === 'quotation' ? 'active' : '' }}" data-type="quotation" onclick="setType('quotation')">
                    <i class="icofont-file-document"></i> {{ __("Quotation") }}
                </button>
            </div>

            @if($errors->any())
            <div style="background:#fff1f2;color:#e11d48;border-radius:8px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:16px;">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif

            {{-- ① Client --}}
            <div class="tp-card">
                <div class="tp-card-header">
                    <div class="tp-card-num">1</div>
                    <div class="tp-card-title">{{ __("Client Details") }}</div>
                </div>
                <input type="hidden" name="customer_id" id="tp-customer-id" value="{{ old('customer_id', $row->customer_id) }}">
                <div class="tp-row">
                    <div class="tp-field">
                        <label>{{ __("Full Name") }}<span class="req">*</span></label>
                        <input type="text" name="client_name" value="{{ old('client_name', $row->client_name) }}" required placeholder="{{ __('Start typing a name, e-mail or phone…') }}" autocomplete="off" data-suggest="customers" data-picked="{{ old('customer_id', $row->customer_id) ? old('client_name', $row->client_name) : '' }}">
                    </div>
                    <div class="tp-field">
                        <label>{{ __("Email Address") }}</label>
                        <input type="email" name="client_email" value="{{ old('client_email', $row->client_email) }}" placeholder="jane@example.com">
                    </div>
                </div>
                <div class="tp-row">
                    <div class="tp-field">
                        <label>{{ __("Phone Number") }}</label>
                        <input type="text" name="client_phone" value="{{ old('client_phone', $row->client_phone) }}" placeholder="+27 83 000 0000">
                    </div>
                    <div class="tp-field">
                        <label>{{ __("Country") }}</label>
                        <input type="text" name="client_country" value="{{ old('client_country', $row->client_country) }}" placeholder="South Africa">
                    </div>
                </div>
                <div class="tp-field">
                    <label>{{ __("Address") }}</label>
                    <textarea name="client_address" rows="2" placeholder="{{ __('Street, City, Postal Code') }}">{{ old('client_address', $row->client_address) }}</textarea>
                </div>
            </div>

            {{-- ② Service --}}
            <div class="tp-card">
                <div class="tp-card-header">
                    <div class="tp-card-num">2</div>
                    <div class="tp-card-title">{{ __("Service / Project") }} <span>{{ __("optional") }}</span></div>
                </div>
                <div class="tp-field">
                    <label>{{ __("Title") }}</label>
                    <input type="text" name="title" value="{{ old('title', $row->title) }}" placeholder="{{ __('e.g. Safari Package — Kruger 2026') }}">
                </div>
                <div class="tp-field">
                    <label>{{ __("Description") }}</label>
                    <textarea name="description" rows="3" placeholder="{{ __('Brief summary of what is included…') }}">{{ old('description', $row->description) }}</textarea>
                </div>
            </div>

            {{-- ③ Items --}}
            <div class="tp-card">
                <div class="tp-card-header">
                    <div class="tp-card-num">3</div>
                    <div class="tp-card-title">{{ __("Line Items") }}</div>
                </div>
                <div style="font-size:12px;color:#999;margin:-2px 0 10px;">{{ __('Type in the item box to pick one of your tours, stays, cars, add-ons or something you billed before.') }}</div>
                <table class="tp-items-table">
                    <thead>
                        <tr>
                            <th style="width:38%">{{ __("Item / Description") }}</th>
                            <th style="width:12%">{{ __("Qty") }}</th>
                            <th style="width:20%">{{ __("Unit Price") }}</th>
                            <th style="width:18%;text-align:right">{{ __("Total") }}</th>
                            <th style="width:32px"></th>
                        </tr>
                    </thead>
                    <tbody id="tp-items-body">
                        @php $items = old('items', $row->items->toArray() ?? []); @endphp
                        @forelse($items as $i => $item)
                        <tr class="tp-item-row">
                            <td>
                                <input type="text" name="items[{{ $i }}][name]" value="{{ $item['name'] }}" placeholder="{{ __('Item name') }}" autocomplete="off" data-suggest="services">
                                <input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="{{ __('Optional detail') }}" class="tp-item-sub-input">
                            </td>
                            <td><input type="number" name="items[{{ $i }}][quantity]"   value="{{ $item['quantity']   ?? 1 }}" min="0" step="any" class="tp-qty"   oninput="recalc()"></td>
                            <td><input type="number" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" step="any" class="tp-price" oninput="recalc()"></td>
                            <td><div class="tp-item-total-cell tp-item-total">{{ number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2) }}</div></td>
                            <td><button type="button" class="tp-remove-item" onclick="removeItem(this)"><i class="icofont-trash"></i></button></td>
                        </tr>
                        @empty
                        <tr class="tp-item-row">
                            <td>
                                <input type="text" name="items[0][name]" autocomplete="off" data-suggest="services" placeholder="{{ __('Item name') }}">
                                <input type="text" name="items[0][description]" placeholder="{{ __('Optional detail') }}" class="tp-item-sub-input">
                            </td>
                            <td><input type="number" name="items[0][quantity]"   value="1" min="0" step="any" class="tp-qty"   oninput="recalc()"></td>
                            <td><input type="number" name="items[0][unit_price]" value="0" step="any" class="tp-price" oninput="recalc()"></td>
                            <td><div class="tp-item-total-cell tp-item-total">0.00</div></td>
                            <td><button type="button" class="tp-remove-item" onclick="removeItem(this)"><i class="icofont-trash"></i></button></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <button type="button" class="tp-add-item" onclick="addItem()">
                    <i class="icofont-plus-circle"></i> {{ __("Add Line Item") }}
                </button>
            </div>

            {{-- ④ Notes --}}
            <div class="tp-card">
                <div class="tp-card-header">
                    <div class="tp-card-num">4</div>
                    <div class="tp-card-title">{{ __("Notes & Terms") }} <span>{{ __("optional") }}</span></div>
                </div>
                <div class="tp-field">
                    <label>{{ __("Notes") }}</label>
                    <textarea name="notes" rows="3" placeholder="{{ __('Thank you for your business…') }}">{{ old('notes', $row->notes) }}</textarea>
                </div>
                <div class="tp-field">
                    <label>{{ __("Payment Terms") }}</label>
                    <textarea name="payment_terms" rows="2" placeholder="{{ __('e.g. 50% deposit on acceptance, balance before travel.') }}">{{ old('payment_terms', $row->payment_terms ?? setting_item('tourpay_default_payment_terms')) }}</textarea>
                </div>
            </div>

        </div>

        {{-- ── RIGHT ── --}}
        <div class="tp-form-side">

            {{-- Template --}}
            <div class="tp-card">
                <div class="tp-card-header" style="margin-bottom:14px;">
                    <div class="tp-card-num" style="background:#6366f1;">T</div>
                    <div class="tp-card-title">{{ __("Template") }}</div>
                </div>
                <div class="tp-templates">
                    @php $selectedTpl = old('template', $row->template ?? 1); @endphp
                    @foreach([1=>'Classic',2=>'Modern',3=>'Bold',4=>'Safari',5=>'Minimal'] as $tnum => $tname)
                    <div class="tp-tpl tp-tpl-{{ $tnum }} {{ $selectedTpl == $tnum ? 'selected' : '' }}" onclick="selectTemplate({{ $tnum }})">
                        <div class="tp-tpl-preview"></div>
                        {{ $tname }}
                    </div>
                    @endforeach
                </div>
                <input type="hidden" name="template" id="tp-template-input" value="{{ old('template', $row->template ?? 1) }}">
            </div>

            {{-- Financials --}}
            <div class="tp-card">
                <div class="tp-card-header" style="margin-bottom:14px;">
                    <div class="tp-card-num" style="background:#16a34a;">$</div>
                    <div class="tp-card-title">{{ __("Financials") }}</div>
                </div>
                <div class="tp-field">
                    <label>{{ __("Currency") }}</label>
                    <select name="currency">
                        @php
                            $currencies  = ['ZAR'=>'ZAR — Rand','USD'=>'USD — Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — Pound','KES'=>'KES — Shilling','TZS'=>'TZS — Tanzanian Sh.','BWP'=>'BWP — Pula','NAD'=>'NAD — Namibian $'];
                            $selectedCur = old('currency', $row->currency ?: ($settings->default_currency ?: 'USD'));
                            foreach (array_filter([$selectedCur, $settings->default_currency ?? null, 'USD']) as $extra) { if (!isset($currencies[$extra])) { $currencies[$extra] = $extra; } }
                        @endphp
                        @foreach($currencies as $code => $label)
                            <option value="{{ $code }}" {{ $selectedCur == $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tp-field">
                    <label>{{ __("Taxes") }} <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">({{ __('e.g. VAT 15, Tourism levy 1') }})</span></label>
                    @php
                        $taxRows = old('tax_lines', $row->tax_lines ?: (($row->tax_rate ?? 0) > 0 ? [['name' => 'VAT', 'rate' => $row->tax_rate + 0]] : []));
                    @endphp
                    <div id="tp-taxes">
                        @foreach(array_values($taxRows) as $ti => $tl)
                        <div class="tp-tax-row" style="display:flex;gap:6px;margin-bottom:6px;">
                            <input type="text" name="tax_lines[{{ $ti }}][name]" value="{{ $tl['name'] ?? '' }}" placeholder="{{ __('Name') }}" style="flex:1;min-width:0;">
                            <input type="number" name="tax_lines[{{ $ti }}][rate]" value="{{ ($tl['rate'] ?? 0) + 0 }}" min="0" max="100" step="any" class="tp-tax-rate" placeholder="%" style="width:72px;" oninput="recalc()">
                            <button type="button" onclick="this.parentNode.remove();recalc();" style="border:0;background:none;color:#bbb;cursor:pointer;">✕</button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" onclick="addTax()" style="border:1.5px dashed #ddd;background:#fff;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;">+ {{ __('Add a tax') }}</button>
                </div>
                <div class="tp-field">
                    <label>{{ __("Discount") }}</label>
                    <input type="number" name="discount" id="tp-discount" value="{{ old('discount', $row->discount ?? 0) + 0 }}" min="0" step="any" oninput="recalc()">
                </div>
                <div class="tp-field">
                    <label>{{ __("Prices") }}</label>
                    <select name="tax_mode" id="tp-tax-mode" onchange="recalc()">
                        <option value="inclusive" {{ old('tax_mode', $row->tax_mode ?: 'inclusive') === 'inclusive' ? 'selected' : '' }}>{{ __("Include tax") }}</option>
                        <option value="exclusive" {{ old('tax_mode', $row->tax_mode ?: 'inclusive') === 'exclusive' ? 'selected' : '' }}>{{ __("Exclude tax (add on top)") }}</option>
                    </select>
                </div>
                <div class="tp-summary">
                    <div class="tp-summary-row" id="tp-row-items"><span>{{ __("Items") }}</span><span id="tp-items-sum">0.00</span></div>
                    <div class="tp-summary-row" id="tp-row-discount" style="display:none"><span>{{ __("Discount") }}</span><span id="tp-discount-show">0.00</span></div>
                    <div class="tp-summary-row" id="tp-row-ex"><span id="tp-ex-label">{{ __("Excl. VAT") }}</span><span id="tp-subtotal">0.00</span></div>
                    <div class="tp-summary-row"><span>{{ __("VAT") }} (<span id="tp-tax-rate-label">0</span>%)</span><span id="tp-tax">0.00</span></div>
                </div>
                <div class="tp-side-total-wrap">
                    <div class="tp-side-total-label">{{ __("Total Due") }}</div>
                    <div class="tp-side-total-amount">
                        <span class="tp-side-total-currency" id="tp-currency-display">{{ $selectedCur }}</span><span id="tp-total">0.00</span>
                    </div>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="tp-card">
                <div class="tp-card-header" style="margin-bottom:14px;">
                    <div class="tp-card-num" style="background:#f59e0b;font-size:13px;"><i class="icofont-calendar"></i></div>
                    <div class="tp-card-title">{{ __("Schedule") }}</div>
                </div>
                @if($row->exists)<div class="tp-field"><label>{{ __("Number") }}</label><div style="font-weight:700;">{{ $row->invoice_number }} <span style="font-weight:500;color:#999;">· {{ $row->status_label }}</span></div></div>@endif
                <div class="tp-field">
                    <label>{{ __("Issue Date") }}</label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', $row->issue_date ? $row->issue_date->format('Y-m-d') : date('Y-m-d')) }}">
                </div>
                <div class="tp-field">
                    <label>{{ __("Due Date") }}</label>
                    <input type="date" name="due_date" id="tp-due" value="{{ old('due_date', $row->due_date ? $row->due_date->format('Y-m-d') : '') }}">
                    <div style="display:flex;gap:6px;margin-top:7px;flex-wrap:wrap;">
                        @foreach([0 => __('On receipt'), 7 => __('7 days'), 14 => __('14 days'), 30 => __('30 days')] as $d => $l)
                        <button type="button" class="tp-quick" onclick="setDue({{ $d }})" style="border:1.5px solid #e4e4e4;background:#fff;border-radius:99px;padding:3px 11px;font-size:11.5px;font-weight:600;cursor:pointer;">{{ $l }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="tp-field" id="tp-valid-days-wrap" style="{{ ($row->type ?? $type) !== 'quotation' ? 'display:none' : '' }}">
                    <label>{{ __("Valid For (days)") }}</label>
                    <input type="number" name="valid_days" value="{{ old('valid_days', $row->valid_days ?? ($settings->default_valid_days ?: 14)) }}" min="1">
                </div>
            </div>

            {{-- Save bar --}}
            <div class="tp-save-bar">
                <button type="button" class="tp-save-btn tp-save-btn-draft" onclick="submitForm('draft')">
                    <i class="icofont-save"></i> {{ __("Save Draft") }}
                </button>
                <button type="button" class="tp-save-btn tp-save-btn-send" onclick="submitForm('send')">
                    <i class="icofont-paper-plane"></i> {{ __("Save & Send") }}
                </button>
            </div>

        </div>{{-- /.tp-form-side --}}
    </div>{{-- /.tp-form-wrap --}}
</form>
</div>{{-- /.tp --}}

<script>
var itemIndex = {{ count($items ?? []) }};

function setType(t) {
    document.getElementById('tp-type-input').value = t;
    document.querySelectorAll('.tp-type-btn').forEach(function(b){
        b.classList.toggle('active', b.dataset.type === t);
    });
    document.getElementById('tp-valid-days-wrap').style.display = t === 'quotation' ? '' : 'none';
}

function selectTemplate(n) {
    document.getElementById('tp-template-input').value = n;
    document.querySelectorAll('.tp-tpl').forEach(function(el){ el.classList.remove('selected'); });
    document.querySelector('.tp-tpl-' + n).classList.add('selected');
}

function recalc() {
    var sum = 0;
    document.querySelectorAll('.tp-item-row').forEach(function(row) {
        var qty   = parseFloat(row.querySelector('.tp-qty').value)   || 0;
        var price = parseFloat(row.querySelector('.tp-price').value) || 0;
        var t     = Math.round(qty * price * 100) / 100;
        sum      += t;
        var td = row.querySelector('.tp-item-total');
        if (td) td.textContent = t.toFixed(2);
    });
    var rate = 0; document.querySelectorAll('.tp-tax-rate').forEach(function (i) { rate += parseFloat(i.value) || 0; });
    var disc = Math.min(parseFloat(document.getElementById('tp-discount').value) || 0, sum);
    var excl = document.getElementById('tp-tax-mode').value === 'exclusive';
    var base = Math.max(0, sum - disc), tax, total, ex;
    if (excl) { tax = Math.round(base * rate) / 100; total = Math.round((base + tax) * 100) / 100; ex = sum; }
    else      { tax = Math.round((base - base / (1 + rate / 100)) * 100) / 100; total = base; ex = Math.round((base - tax) * 100) / 100; }
    var f = function (id, v) { document.getElementById(id).textContent = v.toFixed(2); };
    f('tp-items-sum', sum); f('tp-discount-show', -disc); f('tp-subtotal', ex); f('tp-tax', tax); f('tp-total', total);
    document.getElementById('tp-row-discount').style.display = disc > 0 ? '' : 'none';
    document.getElementById('tp-ex-label').textContent = excl ? '{{ __("Subtotal") }}' : '{{ __("Excl. VAT") }}';
    document.getElementById('tp-tax-rate-label').textContent = rate;
}

var taxIndex = {{ count($taxRows ?? []) + 20 }};
function addTax() {
    var i = taxIndex++, d = document.createElement('div');
    d.className = 'tp-tax-row'; d.style.cssText = 'display:flex;gap:6px;margin-bottom:6px;';
    d.innerHTML = '<input type="text" name="tax_lines[' + i + '][name]" placeholder="{{ __("Name") }}" style="flex:1;min-width:0;"><input type="number" name="tax_lines[' + i + '][rate]" min="0" max="100" step="any" class="tp-tax-rate" placeholder="%" style="width:72px;" oninput="recalc()"><button type="button" onclick="this.parentNode.remove();recalc();" style="border:0;background:none;color:#bbb;cursor:pointer;">✕</button>';
    document.getElementById('tp-taxes').appendChild(d); d.querySelector('input').focus();
}

function setDue(days) {
    var issue = document.querySelector('input[name="issue_date"]').value;
    var d = issue ? new Date(issue + 'T00:00:00') : new Date();
    d.setDate(d.getDate() + days);
    document.getElementById('tp-due').value = d.toISOString().slice(0, 10);
}

function addItem() {
    var i    = itemIndex++;
    var body = document.getElementById('tp-items-body');
    var tr   = document.createElement('tr');
    tr.className = 'tp-item-row';
    tr.innerHTML =
        '<td>'
        + '<input type="text" name="items['+i+'][name]" placeholder="{{ __("Item name") }}" autocomplete="off" data-suggest="services">'
        + '<input type="text" name="items['+i+'][description]" placeholder="{{ __("Optional detail") }}" class="tp-item-sub-input">'
        + '</td>'
        + '<td><input type="number" name="items['+i+'][quantity]"   value="1" min="0" step="any" class="tp-qty"   oninput="recalc()"></td>'
        + '<td><input type="number" name="items['+i+'][unit_price]" value="0" step="any" class="tp-price" oninput="recalc()"></td>'
        + '<td><div class="tp-item-total-cell tp-item-total">0.00</div></td>'
        + '<td><button type="button" class="tp-remove-item" onclick="removeItem(this)"><i class="icofont-trash"></i></button></td>';
    body.appendChild(tr);
    tr.querySelector('input').focus();
    recalc();
}

function removeItem(btn) {
    if (document.querySelectorAll('.tp-item-row').length > 1) {
        btn.closest('.tp-item-row').remove();
        recalc();
    }
}

function submitForm(action) {
    document.getElementById('tp-action-input').value = action;
    document.getElementById('tp-form').submit();
}

// Sync currency display
document.querySelector('select[name="currency"]').addEventListener('change', function() {
    document.getElementById('tp-currency-display').textContent = this.value;
});

// ── Type-ahead: customers (on the client name) and services (on every item name) ─────────────────────────
(function () {
    var URL = "{{ route('tourpay.vendor.suggest', ['what' => 'WHAT']) }}";
    var box = document.createElement('div'); box.id = 'tp-sug'; box.setAttribute('role', 'listbox'); document.body.appendChild(box);
    var active = null, items = [], cur = -1, timer = null, seq = 0;

    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }
    function close() { box.style.display = 'none'; items = []; cur = -1; }
    function place() { var r = active.getBoundingClientRect(); box.style.left = (r.left + window.scrollX) + 'px'; box.style.top = (r.bottom + window.scrollY + 2) + 'px'; box.style.minWidth = Math.max(r.width, 280) + 'px'; }
    function render(kind, list) {
        items = list; cur = -1;
        if (!list.length) { close(); return; }
        var html = '', last = null;
        list.forEach(function (r, i) {
            var g = kind === 'customers' ? null : r.group;
            if (g !== last && g) { html += '<div class="g">' + esc(g) + '</div>'; last = g; }
            html += kind === 'customers'
                ? '<div class="o" data-i="' + i + '"><b>' + esc(r.name) + '</b><span>' + esc([r.email, r.phone].filter(Boolean).join(' · ')) + '</span><em>' + esc(r.source) + '</em></div>'
                : '<div class="o" data-i="' + i + '"><b>' + esc(r.name) + '</b><span>' + esc(r.description) + '</span><em>' + (r.price ? Number(r.price).toFixed(2) : '') + '</em></div>';
        });
        box.innerHTML = html; place(); box.style.display = 'block';
    }
    function ask(input) {
        var kind = input.dataset.suggest, my = ++seq;
        fetch(URL.replace('WHAT', kind) + '?q=' + encodeURIComponent(input.value.trim()), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : { results: [] }; })
            .then(function (j) { if (my === seq && active === input) render(kind, j.results || []); })
            .catch(function () { close(); });
    }
    function choose(i) {
        var r = items[i], input = active; if (!r || !input) return;
        if (input.dataset.suggest === 'customers') {
            var set = function (n, v) { var el = document.querySelector('[name="' + n + '"]'); if (el && v) el.value = v; };
            input.value = r.name; set('client_email', r.email); set('client_phone', r.phone); set('client_country', r.country); set('client_address', r.address);
            document.getElementById('tp-customer-id').value = r.customer_id || '';
            input.dataset.picked = r.name;
        } else {
            var row = input.closest('.tp-item-row');
            input.value = r.name;
            if (r.price) row.querySelector('.tp-price').value = r.price;
            var d = row.querySelector('input[name$="[description]"]'); if (d && !d.value && r.description) d.value = r.description;
            recalc();
        }
        close();
    }
    document.addEventListener('input', function (e) {
        var t = e.target; if (!t.dataset || !t.dataset.suggest) return;
        active = t;
        // Editing the name after picking a customer means it is no longer that customer.
        if (t.dataset.suggest === 'customers' && t.dataset.picked && t.value !== t.dataset.picked) { document.getElementById('tp-customer-id').value = ''; t.dataset.picked = ''; }
        clearTimeout(timer); timer = setTimeout(function () { ask(t); }, 150);
    });
    document.addEventListener('focusin', function (e) { var t = e.target; if (t.dataset && t.dataset.suggest) { active = t; ask(t); } });
    document.addEventListener('keydown', function (e) {
        if (box.style.display !== 'block') return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault(); cur = (cur + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            box.querySelectorAll('.o').forEach(function (o, i) { o.classList.toggle('on', i === cur); if (i === cur) o.scrollIntoView({ block: 'nearest' }); });
        } else if (e.key === 'Enter' && cur >= 0) { e.preventDefault(); choose(cur); }
        else if (e.key === 'Escape') { close(); }
    });
    box.addEventListener('mousedown', function (e) { var o = e.target.closest('.o'); if (o) { e.preventDefault(); choose(parseInt(o.dataset.i, 10)); } });
    document.addEventListener('click', function (e) { if (!e.target.dataset || !e.target.dataset.suggest) close(); });
    window.addEventListener('resize', function () { if (active && box.style.display === 'block') place(); });
})();

recalc();
</script>
@endsection
