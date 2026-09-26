@extends('layouts.user')
@section('content')
<style>
.pb { max-width:1080px; }
.pb h3 { font-size:18px; font-weight:700; margin:34px 0 4px; }
.pb .pb-sub { color:#6b6b6b; font-size:13px; margin-bottom:14px; }
.pb-card { border:1px solid #e6e6e6; border-radius:10px; background:#fff; padding:18px 20px; }
.pb-status { display:flex; flex-wrap:wrap; gap:18px; align-items:center; justify-content:space-between; }
.pb-status .pb-plan { font-size:26px; font-weight:700; letter-spacing:-.02em; }
.pb-pill { display:inline-block; font-size:11px; font-weight:600; padding:3px 9px; border-radius:20px; border:1px solid #d9d9d9; margin-left:8px; vertical-align:middle; }
.pb-pill.is-warn { border-color:#E0A23B; color:#8a5d10; background:#fffaf0; }
.pb-pill.is-bad { border-color:#e11d48; color:#e11d48; background:#fff1f2; }
.pb-btn { display:inline-block; padding:9px 16px; border-radius:7px; font-size:13px; font-weight:600; border:1.5px solid #0a0a0a; background:#fff; color:#0a0a0a !important; text-decoration:none !important; cursor:pointer; }
.pb-btn:hover { background:#f4f4f4; }
.pb-btn.is-primary { background:#0a0a0a; color:#fff !important; }
.pb-btn.is-primary:hover { background:#222; }
.pb-btn.is-small { padding:6px 11px; font-size:12px; }
.pb-btn[disabled] { opacity:.4; cursor:not-allowed; }
.pb-os { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:10px; }
.pb-os-card { display:flex; gap:11px; align-items:flex-start; border:1px solid #e6e6e6; border-radius:9px; padding:12px 14px; background:#fff; }
.pb-os-card i { font-size:22px; color:#9a9a9a; margin-top:2px; }
.pb-os-card b { display:block; font-size:14px; }
.pb-os-card em { display:block; font-style:normal; font-size:12px; color:#7a7a7a; }
.pb-os-card.is-on { border-color:#0a0a0a; box-shadow:inset 0 0 0 1px #0a0a0a; }
.pb-os-card.is-on i { color:#E0A23B; }
.pb-os-card label { display:flex; gap:11px; align-items:flex-start; margin:0; cursor:pointer; width:100%; }
.pb-os-card .pb-tag { font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#8a5d10; }
.pb-meter { margin:8px 0; }
.pb-meter .pb-bar { height:7px; background:#eee; border-radius:6px; overflow:hidden; }
.pb-meter .pb-bar span { display:block; height:100%; background:#0a0a0a; }
.pb-meter .pb-bar span.is-full { background:#e11d48; }
.pb-meter small { color:#6b6b6b; }
.pb-plans { display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:12px; }
.pb-plan-card { border:1px solid #e6e6e6; border-radius:12px; padding:18px; background:#fff; display:flex; flex-direction:column; gap:6px; }
.pb-plan-card.is-current { border-color:#0a0a0a; box-shadow:inset 0 0 0 1px #0a0a0a; }
.pb-plan-card.is-pop { border-color:#E0A23B; }
.pb-plan-card h4 { margin:0; font-size:18px; font-weight:700; }
.pb-price { font-size:30px; font-weight:700; letter-spacing:-.02em; }
.pb-price small { font-size:12px; font-weight:400; color:#7a7a7a; }
.pb-plan-card ul { list-style:none; padding:0; margin:4px 0 8px; font-size:13px; color:#333; }
.pb-plan-card ul li { padding:2px 0; }
.pb-plan-card ul li:before { content:'\2713'; color:#E0A23B; margin-right:7px; font-weight:700; }
.pb-toggle { display:inline-flex; border:1px solid #d9d9d9; border-radius:8px; overflow:hidden; margin-bottom:14px; }
.pb-toggle button { border:0; background:#fff; padding:7px 15px; font-size:13px; font-weight:600; cursor:pointer; }
.pb-toggle button.is-on { background:#0a0a0a; color:#fff; }
.pb-ospick { border-top:1px dashed #e0e0e0; margin-top:6px; padding-top:8px; font-size:12.5px; }
.pb-ospick label { display:block; margin:2px 0; font-weight:400; }
.pb-table { width:100%; font-size:13px; }
.pb-table th { text-align:left; font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:#8a8a8a; padding:6px 8px; }
.pb-table td { padding:8px; border-top:1px solid #eee; }
.pb-pay { background:#fafafa; border:1px dashed #cfcfcf; border-radius:8px; padding:12px 14px; font-size:13px; white-space:pre-line; margin:8px 0; }
.pb-ref { font-family:monospace; font-weight:700; }
</style>
<div class="container-fluid pb">
    <h2 class="title-bar">{{ __('Plan & billing') }}</h2>
    @include('admin.message')

    {{-- ── Where you stand ─────────────────────────────────────────────── --}}
    <div class="pb-card">
        <div class="pb-status">
            <div>
                @if($plan)
                    <div class="pb-plan">{{ $plan->name }}
                        @if($state['state'] === 'expired')<span class="pb-pill is-bad">{{ __('Ended :d', ['d' => $state['ends']]) }}</span>
                        @elseif($onTrial)<span class="pb-pill is-warn">{{ __('Free trial: :n days left', ['n' => max(0, (int) $state['days'])]) }}</span>
                        @elseif($state['state'] === 'expiring')<span class="pb-pill is-warn">{{ __('Renews in :n days', ['n' => max(0, (int) $state['days'])]) }}</span>
                        @elseif($state['state'] === 'ok')<span class="pb-pill">{{ __('Active until :d', ['d' => $state['ends']]) }}</span>@endif
                    </div>
                    <div class="pb-sub" style="margin:4px 0 0;">{{ $plan->osSummary() }} · {{ $plan->max_staff ? __(':n staff seats', ['n' => $plan->max_staff]) : __('Unlimited staff') }}@if($subscription) · {{ $subscription->billing_cycle_label }}@endif</div>
                @else
                    <div class="pb-plan">{{ __('No plan yet') }}</div>
                    <div class="pb-sub" style="margin:4px 0 0;">{{ __('Choose a plan below to start adding listings.') }}</div>
                @endif
            </div>
            <div><a class="pb-btn is-primary" href="#plans">{{ $plan && !in_array($state['state'], ['none', 'expired']) ? __('Change or renew plan') : __('Choose a plan') }}</a></div>
        </div>
    </div>

    {{-- ── Tanova OS ───────────────────────────────────────────────────── --}}
    <h3 id="os">{{ __('What you operate') }}</h3>
    <p class="pb-sub">{{ __('Each Tanova OS is one kind of business. Your menu, and what you can create, follow this list.') }}
        @if($plan && $osLimit !== null){{ __('Your plan: :s.', ['s' => $plan->osSummary()]) }}@elseif($plan){{ __('Your plan covers every OS.') }}@endif</p>

    @if($plan && $osLimit !== null)
        <form method="post" action="{{ route('vendor.subscription.os') }}">
            @csrf
            <div class="pb-os">
                @foreach($osAll as $key => $os)
                    @php $isExtra = in_array($key, $osExtras, true); $on = in_array($key, $osChosen, true); @endphp
                    <div class="pb-os-card {{ $on ? 'is-on' : '' }}">
                        @if($isExtra)
                            <i class="{{ $os['icon'] }}"></i>
                            <span><b>{{ $os['name'] }}</b><em>{{ __($os['tagline']) }}</em><span class="pb-tag">{{ __('Extra OS') }}</span></span>
                        @else
                            <label><input type="checkbox" name="os[]" value="{{ $key }}" {{ $on ? 'checked' : '' }} class="pb-os-box"><i class="{{ $os['icon'] }}"></i>
                                <span><b>{{ $os['name'] }}</b><em>{{ __($os['tagline']) }}</em></span></label>
                        @endif
                    </div>
                @endforeach
            </div>
            <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <button class="pb-btn is-primary" type="submit">{{ __('Save my choice') }}</button>
                <small class="text-muted">{{ __('Tick up to :n (extras are on top of that).', ['n' => $osLimit]) }}</small>
            </div>
        </form>

        @php $canAdd = $plan->addon_price && collect(array_keys($osAll))->diff($osChosen)->isNotEmpty() && count(array_diff($osChosen, $osExtras)) >= $osLimit; @endphp
        @if($canAdd)
            <div class="pb-card" style="margin-top:14px;">
                <b>{{ __('Need another OS?') }}</b>
                <div class="pb-sub" style="margin:2px 0 10px;">{{ __('Add one on top of your plan for :p a month each.', ['p' => '$' . rtrim(rtrim(number_format($plan->addon_price, 2), '0'), '.')]) }}
                    {{ $onTrial ? __('Free during your trial.') : __('You pay only for the days left in this period.') }}</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @foreach($osAll as $key => $os)
                        @continue(in_array($key, $osChosen, true))
                        <form method="post" action="{{ route('vendor.subscription.order') }}">@csrf
                            <input type="hidden" name="kind" value="addon"><input type="hidden" name="os_key" value="{{ $key }}">
                            <button class="pb-btn is-small" type="submit">+ {{ $os['name'] }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="pb-os">
            @foreach($osAll as $key => $os)
                <div class="pb-os-card {{ $plan ? 'is-on' : '' }}"><i class="{{ $os['icon'] }}"></i><span><b>{{ $os['name'] }}</b><em>{{ __($os['tagline']) }}</em></span></div>
            @endforeach
        </div>
    @endif

    {{-- ── Usage ───────────────────────────────────────────────────────── --}}
    @if($plan)
    <h3>{{ __('What you have used') }}</h3>
    <div class="pb-card">
        @foreach($usage as $type => $u)
            @php $pct = $u['max'] ? min(100, round($u['used'] / $u['max'] * 100)) : 0; @endphp
            <div class="pb-meter">
                <div style="display:flex;justify-content:space-between;font-size:13px;"><span>{{ ucfirst($type) }} {{ __('listings') }}</span><span>{{ $u['used'] }} / {{ $u['max'] ?: __('unlimited') }}</span></div>
                <div class="pb-bar"><span class="{{ $u['max'] && $u['used'] >= $u['max'] ? 'is-full' : '' }}" style="width:{{ $u['max'] ? $pct : 0 }}%"></span></div>
            </div>
        @endforeach
        @php $seatMax = (int) $plan->max_staff; @endphp
        <div class="pb-meter">
            <div style="display:flex;justify-content:space-between;font-size:13px;"><span>{{ __('Staff seats') }} · <a href="{{ route('vendor.team.index') }}">{{ __('Team') }}</a></span><span>{{ $seatsUsed }} / {{ $seatMax ?: __('unlimited') }}</span></div>
            <div class="pb-bar"><span class="{{ $seatMax && $seatsUsed >= $seatMax ? 'is-full' : '' }}" style="width:{{ $seatMax ? min(100, round($seatsUsed / $seatMax * 100)) : 0 }}%"></span></div>
        </div>
        <small class="text-muted">{{ __('Reaching a limit stops you adding more, never your bookings, payments or customers.') }}</small>
    </div>
    @endif

    {{-- ── Plans ───────────────────────────────────────────────────────── --}}
    <h3 id="plans">{{ __('Plans') }}</h3>
    <p class="pb-sub">{{ __('Prices in US dollars. A change starts as soon as we confirm your payment. Dining, Tanova AI, TourPay and reports come with every plan.') }}</p>
    <div class="pb-toggle" id="pbToggle"><button type="button" data-cycle="monthly" class="is-on">{{ __('Monthly') }}</button><button type="button" data-cycle="yearly">{{ __('Yearly: 2 months free') }}</button></div>
    <div class="pb-plans">
        @foreach($plans as $p)
            @php $current = $plan && $plan->id === $p->id; @endphp
            <div class="pb-plan-card {{ $current ? 'is-current' : '' }} {{ $p->highlight ? 'is-pop' : '' }}">
                <h4>{{ $p->name }} @if($p->highlight)<span class="pb-pill is-warn">{{ __('Popular') }}</span>@endif @if($current)<span class="pb-pill">{{ __('Yours') }}</span>@endif</h4>
                <div class="pb-price"><span class="pb-p-monthly">${{ rtrim(rtrim(number_format($p->price, 2), '0'), '.') }}</span><span class="pb-p-yearly" style="display:none">${{ rtrim(rtrim(number_format($p->price_annual ?: $p->price * 12, 2), '0'), '.') }}</span>
                    <small class="pb-u-monthly">/{{ __('month') }}</small><small class="pb-u-yearly" style="display:none">/{{ __('year') }}</small></div>
                <div class="pb-sub" style="margin:0;">{{ $p->tagline }}</div>
                <ul>
                    <li><b>{{ $p->osSummary() }}</b></li>
                    <li>{{ $p->max_staff ? __(':n staff seats', ['n' => $p->max_staff]) : __('Unlimited staff') }}</li>
                    <li>{{ $p->coversAllOs() ? __('Unlimited listings') : __('Up to :n listings per type', ['n' => optional($p->meta->first())->maximum_create ?: '∞']) }}</li>
                    @if($p->addon_price)<li>{{ __('Extra OS: :p/month', ['p' => '$' . rtrim(rtrim(number_format($p->addon_price, 2), '0'), '.')]) }}</li>@endif
                </ul>
                <form method="post" action="{{ route('vendor.subscription.order') }}" style="margin-top:auto;">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $p->id }}"><input type="hidden" name="cycle" class="pb-cycle" value="monthly">
                    @unless($p->coversAllOs())
                        <div class="pb-ospick">
                            <b>{{ __('Your OS (choose :n)', ['n' => $p->os_limit]) }}</b>
                            @foreach($osAll as $key => $os)
                                @php $pre = in_array($key, $osChosen, true) && !in_array($key, $osExtras, true); @endphp
                                <label><input type="checkbox" name="os[]" value="{{ $key }}" data-max="{{ $p->os_limit }}" class="pb-planos" {{ $pre ? 'checked' : '' }} {{ in_array($key, $osExtras, true) ? 'disabled' : '' }}> {{ $os['name'] }}@if(in_array($key, $osExtras, true)) <small class="text-muted">({{ __('extra, kept') }})</small>@endif</label>
                            @endforeach
                        </div>
                    @endunless
                    <button type="submit" class="pb-btn {{ $current ? '' : 'is-primary' }}" style="width:100%;margin-top:10px;">
                        {{ $current ? ($state['state'] === 'ok' && !$onTrial ? __('Renew :p', ['p' => $p->name]) : __('Keep :p', ['p' => $p->name])) : ($plan ? __('Switch to :p', ['p' => $p->name]) : __('Choose :p', ['p' => $p->name])) }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    {{-- ── Orders and how to pay ───────────────────────────────────────── --}}
    <h3 id="orders">{{ __('Payments') }}</h3>
    @foreach($orders->where('status', 'pending') as $o)
        <div class="pb-card" style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div><b>{{ $o->kind === 'addon' ? __('Extra OS: :n', ['n' => \Modules\Vendor\Services\CompanyOs::names((array) $o->os_keys)]) : $o->plan->name . ' · ' . ($o->billing_cycle === 'yearly' ? __('yearly') : __('monthly')) }}</b>
                    <div class="pb-sub" style="margin:2px 0 0;">{{ __('Amount to pay') }}: <b>{{ \Modules\Vendor\Services\PlanBilling::money($o->amount, $o->currency) }}</b> · {{ __('Reference') }}: <span class="pb-ref">{{ $o->reference }}</span></div></div>
                <form method="post" action="{{ route('vendor.subscription.order.cancel', $o->id) }}">@csrf<button class="pb-btn is-small" type="submit">{{ __('Cancel order') }}</button></form>
            </div>
            <div class="pb-pay">{{ $instructions ?: __('Contact the platform team to arrange payment and quote the reference above.') }}</div>
            <small class="text-muted">{{ __('Quote the reference when you pay. Your plan starts once we confirm the payment, and you will get an email.') }}</small>
        </div>
    @endforeach
    @if($orders->where('status', '!=', 'pending')->isNotEmpty() || $history->isNotEmpty())
        <div class="pb-card">
            <table class="pb-table">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('What') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                @foreach($orders->where('status', '!=', 'pending') as $o)
                    <tr><td>{{ display_date($o->created_at) }}</td><td>{{ $o->kind === 'addon' ? __('Extra OS') . ': ' . \Modules\Vendor\Services\CompanyOs::names((array) $o->os_keys) : $o->plan->name }} <span class="pb-ref">{{ $o->reference }}</span></td><td>{{ \Modules\Vendor\Services\PlanBilling::money($o->amount, $o->currency) }}</td><td>{{ ucfirst($o->status) }}</td></tr>
                @endforeach
                @foreach($history as $h)
                    <tr><td>{{ display_date($h->starts_at) }}</td><td>{{ $h->plan->name }} · {{ $h->billing_cycle_label }} <small class="text-muted">→ {{ display_date($h->ends_at) }}</small></td><td>{{ $h->payment_gateway === 'trial' ? __('Free trial') : format_money($h->amount_paid) }}</td><td>{{ ucfirst($h->status) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @elseif($orders->where('status', 'pending')->isEmpty())
        <p class="pb-sub">{{ __('Nothing to pay yet.') }}</p>
    @endif
</div>
<script>
(function () {
    var btns = document.querySelectorAll('#pbToggle button');
    btns.forEach(function (b) { b.addEventListener('click', function () {
        var y = b.dataset.cycle === 'yearly';
        btns.forEach(function (x) { x.classList.toggle('is-on', x === b); });
        document.querySelectorAll('.pb-cycle').forEach(function (i) { i.value = b.dataset.cycle; });
        document.querySelectorAll('.pb-p-monthly, .pb-u-monthly').forEach(function (e) { e.style.display = y ? 'none' : ''; });
        document.querySelectorAll('.pb-p-yearly, .pb-u-yearly').forEach(function (e) { e.style.display = y ? '' : 'none'; });
    }); });
    // Keep the number of ticked OS within what the plan (or the plan card) allows.
    function limit(box, max, scope) {
        box.addEventListener('change', function () {
            if (scope.querySelectorAll('input[type=checkbox]:checked:not(:disabled)').length > max) { box.checked = false; }
        });
    }
    document.querySelectorAll('.pb-planos').forEach(function (b) { limit(b, parseInt(b.dataset.max, 10), b.closest('form')); });
    @if($plan && $osLimit !== null)
    document.querySelectorAll('.pb-os-box').forEach(function (b) { limit(b, {{ (int) $osLimit }}, b.closest('form')); });
    @endif
})();
</script>
@endsection
