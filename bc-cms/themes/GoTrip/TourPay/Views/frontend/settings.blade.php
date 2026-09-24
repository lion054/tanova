@extends('layouts.user')
@section('content')
<style>
.tp * { box-sizing: border-box; }
.tp-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #aaa; text-decoration: none; margin-bottom: 20px; }
.tp-back:hover { color: #0a0a0a; }
.tp-h1 { font-size: 24px; font-weight: 800; letter-spacing: -.03em; margin: 0 0 3px; }
.tp-sub { font-size: 13px; color: #999; margin: 0 0 22px; }
.tps { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 26px; align-items: start; }
@media (max-width: 900px) { .tps { grid-template-columns: 1fr; } }
.tps-nav { position: sticky; top: 12px; background: #fff; border: 1px solid #ebebeb; border-radius: 10px; padding: 8px; }
.tps-nav a { display: block; padding: 9px 12px; border-radius: 7px; font-size: 13.5px; font-weight: 600; color: #555 !important; text-decoration: none !important; }
.tps-nav a:hover { background: #f5f5f5; }
.tps-nav a.on { background: #0a0a0a; color: #fff !important; }
.tps-nav small { display: block; font-weight: 400; opacity: .65; font-size: 11.5px; }
.card { background: #fff; border: 1px solid #ebebeb; border-radius: 12px; padding: 22px 24px; margin-bottom: 22px; scroll-margin-top: 16px; }
.card h2 { margin: 0 0 4px; font-size: 17px; font-weight: 800; letter-spacing: -.02em; }
.card .lead { color: #888; font-size: 13px; margin: 0 0 16px; line-height: 1.55; }
.f label { display: block; font-size: 11px; font-weight: 700; color: #777; text-transform: uppercase; letter-spacing: .05em; margin: 14px 0 5px; }
.f input[type=text], .f input[type=number], .f input[type=password], .f select, .f textarea { width: 100%; padding: 10px 12px; border: 1.5px solid #e4e4e4; border-radius: 7px; font-size: 14px; background: #fff; }
.f input:focus, .f select:focus, .f textarea:focus { outline: none; border-color: #0a0a0a; }
.f .hint { font-size: 12px; color: #999; margin-top: 5px; line-height: 1.5; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
@media (max-width: 700px) { .row2, .row3 { grid-template-columns: 1fr; } }
.check { display: flex; gap: 9px; align-items: flex-start; font-size: 13.5px; color: #333; margin-top: 12px; font-weight: 500; text-transform: none !important; letter-spacing: 0 !important; }
.check input { margin-top: 3px; width: auto !important; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 7px; font-size: 13.5px; font-weight: 700; border: 0; cursor: pointer; text-decoration: none !important; }
.btn-primary { background: #0a0a0a; color: #fff !important; }
.btn-ghost { background: #fff; color: #0a0a0a !important; border: 1.5px solid #e0e0e0; padding: 7px 14px; font-size: 12.5px; }
.foot { margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; }
.gw { border: 1.5px solid #ebebeb; border-radius: 10px; padding: 14px 16px; margin-top: 14px; }
.gw.on { border-color: #0a0a0a; }
.gw-h { display: flex; justify-content: space-between; gap: 10px; align-items: center; flex-wrap: wrap; }
.gw-h strong { font-size: 14.5px; }
.badge-live { font-size: 11px; font-weight: 700; color: #16a34a; }
.badge-off { font-size: 11px; font-weight: 700; color: #aaa; }
.cur { display: inline-block; font-size: 11px; border: 1px solid #e4e4e4; border-radius: 99px; padding: 1px 8px; margin: 2px 3px 0 0; color: #666; }
table.au { width: 100%; border-collapse: collapse; font-size: 13px; }
table.au th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #888; padding: 8px 10px; border-bottom: 1px solid #eee; }
table.au td { padding: 9px 10px; border-bottom: 1px solid #f6f6f6; vertical-align: top; }
.muted { color: #999; font-size: 12px; }
</style>
<div class="tp">
<a href="{{ route('tourpay.vendor.index') }}" class="tp-back"><i class="icofont-arrow-left"></i> {{ __('Back to TourPay') }}</a>
<h1 class="tp-h1">{{ __('TourPay settings') }}</h1>
<p class="tp-sub">{{ __('How your invoices look and are numbered, how clients pay you, and what happens when they do not.') }}</p>
@include('admin.message')
@if($errors->any())<div style="background:#fff1f2;color:#e11d48;border-radius:8px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:16px;">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="tps">
<nav class="tps-nav" id="tps-nav">
    <a href="#general">{{ __('Invoices') }}<small>{{ __('numbers, defaults, terms') }}</small></a>
    <a href="#payments">{{ __('Getting paid') }}<small>{{ __('online payments, bank details') }}</small></a>
    <a href="#currency">{{ __('Currencies') }}<small>{{ __('base currency, your rates') }}</small></a>
    <a href="#reminders">{{ __('Reminders') }}<small>{{ __('automatic payment reminders') }}</small></a>
    <a href="#activity">{{ __('Activity') }}<small>{{ __('who changed what') }}</small></a>
</nav>

<div>
{{-- ── Invoices ── --}}
<form method="POST" action="{{ route('tourpay.vendor.settings') }}" class="card f" id="general">
    @csrf <input type="hidden" name="section" value="general">
    <h2>{{ __('Invoices') }}</h2>
    <p class="lead">{{ __('Numbers run per business and per year, for example :ex. What you set here is the starting point for every new invoice; you can still change each one.', ['ex' => $settings->prefixFor('invoice').'-'.date('Y').'-001']) }}</p>
    <div class="row2">
        <div><label>{{ __('Invoice prefix') }}</label><input type="text" name="invoice_prefix" value="{{ $settings->invoice_prefix }}" maxlength="12" placeholder="INV"></div>
        <div><label>{{ __('Quotation prefix') }}</label><input type="text" name="quote_prefix" value="{{ $settings->quote_prefix }}" maxlength="12" placeholder="QUO"></div>
    </div>
    <div class="row3">
        <div><label>{{ __('Currency') }}</label><input type="text" name="default_currency" value="{{ $settings->default_currency }}" maxlength="3" placeholder="USD" style="text-transform:uppercase"></div>
        <div><label>{{ __('Tax rate (%)') }}</label><input type="number" name="default_tax_rate" value="{{ $settings->default_tax_rate !== null ? $settings->default_tax_rate + 0 : '' }}" step="0.01" min="0" max="100"></div>
        <div><label>{{ __('Prices') }}</label><select name="default_tax_mode"><option value="inclusive" {{ ($settings->default_tax_mode ?: 'inclusive') === 'inclusive' ? 'selected' : '' }}>{{ __('include tax') }}</option><option value="exclusive" {{ $settings->default_tax_mode === 'exclusive' ? 'selected' : '' }}>{{ __('exclude tax (added on top)') }}</option></select></div>
    </div>
    <div class="row3">
        <div><label>{{ __('Payment due after (days)') }}</label><input type="number" name="default_due_days" value="{{ $settings->default_due_days }}" min="0" max="365" placeholder="14"></div>
        <div><label>{{ __('Quotation valid for (days)') }}</label><input type="number" name="default_valid_days" value="{{ $settings->default_valid_days }}" min="1" max="365" placeholder="14"></div>
        <div><label>{{ __('Look') }}</label><select name="template">@foreach([1 => __('Classic'), 2 => __('Modern'), 3 => __('Bold'), 4 => __('Safari'), 5 => __('Minimal')] as $n => $l)<option value="{{ $n }}" {{ (int) ($settings->template ?: 1) === $n ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    </div>
    <label>{{ __('Payment terms') }}</label><textarea name="default_terms" rows="2" placeholder="{{ __('e.g. 30% deposit to confirm, balance 14 days before travel.') }}">{{ $settings->default_terms }}</textarea>
    <label>{{ __('Notes') }}</label><textarea name="default_notes" rows="2" placeholder="{{ __('Thank you for your business.') }}">{{ $settings->default_notes }}</textarea>
    <div class="foot"><button class="btn btn-primary">{{ __('Save invoice settings') }}</button></div>
</form>

{{-- ── Getting paid ── --}}
<form method="POST" action="{{ route('tourpay.vendor.settings') }}" class="card f" id="payments">
    @csrf <input type="hidden" name="section" value="payments">
    <h2>{{ __('Getting paid') }}</h2>
    <p class="lead">{{ __('Clients pay you directly. The money goes to YOUR account with each provider, never through the platform. Turn on the ones you use and paste your keys: they are stored encrypted and never shown again. Each provider only shows on an invoice whose currency it can take.') }}</p>

    @foreach($gateways as $gw => $label)
    @php $g = $settings->gateway($gw); $def = $gatewayFields[$gw]; $live = isset($settings->enabledGateways()[$gw]); @endphp
    <div class="gw {{ !empty($g['enabled']) ? 'on' : '' }}">
        <div class="gw-h">
            <label class="check" style="margin:0;"><input type="checkbox" name="gateways[{{ $gw }}][enabled]" value="1" {{ !empty($g['enabled']) ? 'checked' : '' }}> <strong>{{ $label }}</strong></label>
            <span class="{{ $live ? 'badge-live' : 'badge-off' }}">{{ $live ? '● '.__('live on your pay page') : __('not live') }}</span>
        </div>
        <div class="muted" style="margin:6px 0 2px;">{{ __($def['help']) }}
            @if(!empty($currencyHints[$gw]))<div>{{ __('Currencies') }}: @foreach($currencyHints[$gw] as $c)<span class="cur">{{ $c }}</span>@endforeach</div>@endif</div>
        <div class="row2">
        @foreach($def['fields'] as $f => [$fl, $ph])
            @php $isSecret = in_array($f, $secretFields, true); @endphp
            <div><label>{{ __($fl) }}</label><input type="{{ $isSecret ? 'password' : 'text' }}" name="gateways[{{ $gw }}][{{ $f }}]" value="{{ $isSecret ? '' : ($g[$f] ?? '') }}" autocomplete="off" placeholder="{{ $isSecret && !empty($g[$f]) ? '•••••••• '.__('saved (leave blank to keep)') : $ph }}"></div>
        @endforeach
        @if(!empty($def['mode']))
            <div><label>{{ __('Mode') }}</label><select name="gateways[{{ $gw }}][mode]"><option value="live" {{ ($g['mode'] ?? 'live') === 'live' ? 'selected' : '' }}>{{ __('Live') }}</option><option value="sandbox" {{ ($g['mode'] ?? '') === 'sandbox' ? 'selected' : '' }}>{{ __('Sandbox (testing)') }}</option></select></div>
        @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button type="button" class="btn btn-ghost" onclick="tpTestGw('{{ $gw }}', this)">{{ __('Test saved keys') }}</button><span class="tp-gw-out muted"></span>
        </div>
        @if(in_array($gw, ['paynow', 'selcom', 'pesapal']))<div class="muted" style="margin-top:8px;">{{ __('We tell :p to report payments to :u. Nothing to set up on your side.', ['p' => explode(' (', $label)[0], 'u' => $notifyUrls[$gw]]) }}</div>@endif
    </div>
    @endforeach

    <label style="margin-top:22px;">{{ __('Bank transfer') }}</label>
    <label class="check"><input type="checkbox" name="bank_enabled" value="1" {{ $settings->bank_enabled ? 'checked' : '' }}> {{ __('Offer bank transfer on the pay page. Clients can tell you they paid, with a reference and proof, and you confirm it.') }}</label>
    <div class="row2">
        @foreach(['bank' => __('Bank'), 'account_name' => __('Account name'), 'account_number' => __('Account number'), 'branch_code' => __('Branch code'), 'swift' => __('SWIFT')] as $k => $l)
        <div><label>{{ $l }}</label><input type="text" name="banking_details[{{ $k }}]" value="{{ $settings->banking_details[$k] ?? '' }}" maxlength="200"></div>
        @endforeach
    </div>
    <label style="margin-top:20px;">{{ __('Receipts') }}</label>
    <label class="check"><input type="checkbox" name="send_receipts" value="1" {{ $settings->send_receipts ? 'checked' : '' }}> {{ __('E-mail the client a receipt whenever a payment is received.') }}</label>
    <div class="foot"><button class="btn btn-primary">{{ __('Save payment settings') }}</button></div>
</form>

{{-- ── Currencies ── --}}
<form method="POST" action="{{ route('tourpay.vendor.settings') }}" class="card f" id="currency">
    @csrf <input type="hidden" name="section" value="currency">
    <h2>{{ __('Currencies') }}</h2>
    <p class="lead">{{ __('Every invoice has its own currency, and you can bill in as many as you like. To see one total across them, choose a base currency and say what each is worth in it. Your rates, not ours: nothing is converted unless you give a rate.') }}</p>
    <div class="row2">
        <div><label>{{ __('Base currency') }}</label><input type="text" name="base_currency" value="{{ $settings->base_currency }}" maxlength="3" placeholder="USD" style="text-transform:uppercase"></div>
        <div><label>{{ __('Your rates') }}</label><textarea name="rates_text" rows="3" placeholder="ZAR = 0.054&#10;EUR = 1.08">@foreach(($settings->rates ?? []) as $c => $r){{ $c }} = {{ $r + 0 }}&#10;@endforeach</textarea><div class="hint">{{ __('One per line: 1 unit of that currency in the base currency.') }}</div></div>
    </div>
    <div class="foot"><button class="btn btn-primary">{{ __('Save currencies') }}</button></div>
</form>

{{-- ── Reminders ── --}}
<form method="POST" action="{{ route('tourpay.vendor.settings') }}" class="card f" id="reminders">
    @csrf <input type="hidden" name="section" value="reminders">
    <h2>{{ __('Payment reminders') }}</h2>
    <p class="lead">{{ __('Off unless you turn them on. Only invoices you have sent that still have a balance, never more than one a day, and never after the limit you set. They go from your own e-mail or your connected WhatsApp number.') }}</p>
    <label class="check"><input type="checkbox" name="remind_enabled" value="1" {{ $settings->remind_enabled ? 'checked' : '' }}> <strong>{{ __('Send payment reminders automatically') }}</strong></label>
    <div class="row2">
        <div><label>{{ __('First reminder, days before due') }}</label><input type="number" name="remind_before_days" value="{{ $settings->remind_before_days ?? 3 }}" min="0" max="60"></div>
        <div><label>{{ __('Then every (days) once overdue') }}</label><input type="number" name="remind_overdue_every" value="{{ $settings->remind_overdue_every ?? 7 }}" min="1" max="60"></div>
        <div><label>{{ __('At most') }}</label><input type="number" name="remind_max" value="{{ $settings->remind_max ?? 4 }}" min="1" max="20"></div>
        <div><label>{{ __('Send by') }}</label><select name="remind_channel"><option value="email" {{ ($settings->remind_channel ?? 'email') === 'email' ? 'selected' : '' }}>{{ __('E-mail') }}</option><option value="whatsapp" {{ $settings->remind_channel === 'whatsapp' ? 'selected' : '' }}>{{ __('WhatsApp (your connected number)') }}</option></select></div>
    </div>
    <div class="foot"><button class="btn btn-primary">{{ __('Save reminders') }}</button></div>
</form>

{{-- ── Activity ── --}}
<div class="card" id="activity">
    <h2>{{ __('Activity') }}</h2>
    <p class="lead">{{ __('Every payment, refund, credit, void and settings change, with who did it. Keys are never recorded, only that they changed.') }}</p>
    @if($audit->isEmpty())<div class="muted">{{ __('Nothing yet.') }}</div>@else
    <div style="overflow-x:auto;"><table class="au"><thead><tr><th>{{ __('When') }}</th><th>{{ __('What') }}</th><th>{{ __('By') }}</th></tr></thead><tbody>
    @foreach($audit as $a)
        <tr><td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($a->created_at)->format('d M Y, H:i') }}</td><td>{{ $a->summary ?: $a->action }}<div class="muted">{{ $a->action }}</div></td><td>{{ $a->actor_type === 'api_key' ? __('API key') : ($a->actor_type === 'guest' ? __('Client') : ($a->actor_type === 'system' ? __('System') : __('You or your team'))) }}<div class="muted">{{ $a->ip }}</div></td></tr>
    @endforeach
    </tbody></table></div>
    @endif
</div>
</div>
</div>
</div>
<script>
function tpTestGw(gw, btn) {
    var out = btn.parentNode.querySelector('.tp-gw-out'); out.style.color = '#888'; out.textContent = '…';
    fetch("{{ url('user/tourpay/settings/gateways') }}/" + gw + "/test", { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
        .then(function (r) { return r.json().then(function (j) { return [r.ok, j]; }); })
        .then(function (a) { out.style.color = a[0] ? '#16a34a' : '#e11d48'; out.textContent = a[1].message || (a[0] ? 'OK' : 'Failed'); })
        .catch(function () { out.style.color = '#e11d48'; out.textContent = "{{ __('Could not reach the provider.') }}"; });
}
(function () {
    var links = document.querySelectorAll('#tps-nav a');
    var mark = function () { var h = location.hash || '#general'; links.forEach(function (a) { a.classList.toggle('on', a.getAttribute('href') === h); }); };
    window.addEventListener('hashchange', mark); mark();
})();
</script>
@endsection
