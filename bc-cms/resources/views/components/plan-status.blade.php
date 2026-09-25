{{-- A business's plan at a glance, above its portal pages (only when plans are switched on and something needs attention). --}}
@php
    $u = Auth::user();
    $ps = ($u && is_enable_plan() && $u->hasPermission('dashboard_vendor_access') && !request()->is('admin*')) ? \Modules\Vendor\Services\PlanLimits::state($u) : null;
@endphp
@if($ps && $ps['state'] !== 'ok')
<div role="status" style="margin:14px 18px 0;padding:11px 16px;border:1px solid {{ $ps['state'] === 'expiring' ? '#e5e5e5' : '#0a0a0a' }};border-radius:10px;background:{{ $ps['state'] === 'expiring' ? '#fafafa' : '#fff' }};font-size:13px;line-height:1.5;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
    <span>
        @if($ps['state'] === 'none') <strong>{{ __('No active plan.') }}</strong> {{ __('You can run bookings and invoices, but you cannot add new listings until a plan is assigned.') }}
        @elseif($ps['state'] === 'expired') <strong>{{ __('Your :plan plan ended on :d.', ['plan' => $ps['plan'] ?: __('subscription'), 'd' => $ps['ends']]) }}</strong> {{ __('You cannot add new listings until it is renewed. Everything else keeps working.') }}
        @else <strong>{{ __('Your :plan plan ends in :n days (:d).', ['plan' => $ps['plan'] ?: __('subscription'), 'n' => $ps['days'], 'd' => $ps['ends']]) }}</strong> {{ __('Renew in time to keep adding listings.') }}
        @endif
    </span>
    <a href="{{ url('/vendor/subscription') }}" style="font-weight:700;color:#0a0a0a;text-decoration:underline;white-space:nowrap;">{{ __('My subscription') }} &rarr;</a>
</div>
@endif
