@php
    $user = Auth::user();
    $theme = \Modules\Theme\ThemeManager::currentProvider();
    $languages = \Modules\Language\Models\Language::getActive();
    $languages = collect($languages)->unique('locale')->values();
    $locale = App::getLocale();
@endphp

<link href="{{ asset('themes/admin/dist/css/style.css') }}" rel="stylesheet">
<style>
    /* ── Portal top bar ─────────────────────────────────────────── */
    .main-header {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 56px;
        background: #ffffff;
        border-bottom: 1px solid #e8e8e8;
        display: flex;
        align-items: center;
        z-index: 10000;
        padding: 0 20px 0 264px;   /* clears the fixed sidebar; the sidebar's own rules narrow this for the rail and drop it on phones */
        gap: 8px;
        transition: padding-left .26s cubic-bezier(.4,0,.2,1);
    }
    .bc_wrap .header-margin { margin-top: 56px !important; }

    /* Logo area */
    .ph-logo { display: none; align-items: center; padding: 0 14px; flex-shrink: 0; height: 100%; }
    @media (max-width: 991px) { .ph-logo { display: flex; } }
    .ph-logo a {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    .ph-logo img { height: 28px; width: auto; }
    .ph-logo-text {
        font-size: 15px;
        font-weight: 600;
        color: #0a0a0a;
        letter-spacing: -.02em;
    }

    /* Centre nav links */
    .ph-nav {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 0 20px;
        flex: 1;
        height: 100%;
    }
    .ph-nav a,
    .main-header .ph-nav a {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #0a0a0a !important;
        text-decoration: none !important;
        padding: 0 16px !important;
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
        border-bottom: 2px solid transparent !important;
        letter-spacing: .01em !important;
        transition: color .12s, border-color .12s !important;
        line-height: 1 !important;
    }
    .ph-nav a.ph-switch { border: 1.5px solid #0a0a0a; border-radius: 999px; padding: 6px 14px; margin-left: 6px; font-weight: 700; }
    .ph-nav a.ph-switch:hover { background: #0a0a0a; color: #fff !important; }
    .ph-nav a:hover,
    .main-header .ph-nav a:hover {
        color: #0a0a0a !important;
        border-bottom-color: #0a0a0a !important;
        background: transparent !important;
    }
    .ph-nav-soon,
    .main-header .ph-nav-soon {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #a0a0a0 !important;
        padding: 0 16px !important;
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
        gap: 7px !important;
        cursor: default !important;
        letter-spacing: .01em !important;
    }
    .ph-soon-pill {
        font-size: 9px !important;
        font-weight: 700 !important;
        letter-spacing: .1em !important;
        text-transform: uppercase !important;
        background: #0a0a0a !important;
        color: #ffffff !important;
        border-radius: 3px !important;
        padding: 2px 6px !important;
        line-height: 1.4 !important;
    }

    /* Right widgets */
    .ph-right {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }

    /* Sidebar toggle button */
    .ph-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: none;
        background: transparent;
        border-radius: 8px;
        color: #5a5a5a;
        cursor: pointer;
        transition: background .12s, color .12s;
        margin-left: 0;
    }
    .ph-toggle:hover { background: #f0f0f0; color: #0a0a0a; }
    .ph-toggle svg { width: 20px; height: 20px; }
    .ph-ic { width: 14px; height: 14px; flex-shrink: 0; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .ph-caret { width: 12px; height: 12px; stroke: #a0a0a0; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* Plan chip: where the company stands, one click from the plan page */
    .ph-plan { display: inline-flex; align-items: center; gap: 8px; height: 32px; padding: 0 12px; border: 1px solid #e3e3e3; border-radius: 999px; font-size: 12px; color: #0a0a0a !important; text-decoration: none !important; white-space: nowrap; transition: border-color .12s, background .12s; }
    .ph-plan:hover { border-color: #0a0a0a; background: #fafafa; }
    .ph-plan b { font-weight: 700; }
    .ph-plan span { color: #6b6b6b; }
    .ph-plan.is-warn { border-color: #E0A23B; background: #fffaf0; }
    .ph-plan.is-warn span { color: #8a5d10; }
    .ph-plan.is-alert { border-color: #e11d48; background: #fff1f2; }
    .ph-plan.is-alert span { color: #e11d48; }
    @media (max-width: 575px) { .ph-plan span, .ph-dd-who, .ph-lang-trigger .ph-caret, .ph-dd-trigger .ph-caret { display: none; } .ph-plan { padding: 0 10px; } .ph-lang-trigger, .ph-dd-trigger { padding: 6px; } .main-header { gap: 4px; } }
    @media (max-width: 991px) { .main-header { padding-right: 10px; } }

    /* Dropdown */
    .ph-dd { position: relative; }
    .ph-dd-trigger {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: background .12s;
    }
    .ph-dd-trigger:hover { background: #f5f5f5; }
    .ph-dd-avatar {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: #e8e8e8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        color: #5a5a5a;
        overflow: hidden;
        flex-shrink: 0;
    }
    .ph-dd-avatar .avatar-cover {
        width: 100%; height: 100%;
        background-size: cover;
        background-position: center;
        border-radius: 50%;
    }
    .ph-dd-name {
        font-size: 12px;
        font-weight: 500;
        color: #0a0a0a;
        white-space: nowrap;
    }
    .ph-dd-role {
        font-size: 10px;
        color: #a0a0a0;
        white-space: nowrap;
    }
    .ph-dd-caret { color: #a0a0a0; font-size: 10px; }

    .ph-menu {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        min-width: 192px;
        background: #ffffff;
        border: 1px solid #e8e8e8;
        border-radius: 4px;
        padding: 4px 0;
        z-index: 9100;
        box-shadow: 0 4px 16px rgba(0,0,0,.08);
    }
    .ph-menu.open { display: block; }
    .ph-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        font-size: 13px;
        color: #222222;
        text-decoration: none;
        transition: background .1s;
    }
    .ph-menu a .ph-ic { color: #9a9a9a; }
    .ph-menu a:hover { background: #f5f5f5; color: #0a0a0a; }
    .ph-menu-sep { height: 1px; background: #e8e8e8; margin: 4px 0; }

    /* Language dropdown */
    .ph-lang-trigger {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        color: #5a5a5a;
        transition: background .12s;
    }
    .ph-lang-trigger:hover { background: #f5f5f5; }
</style>

<div class="main-header">

    {{-- Logo (same width as sidebar) --}}
    <div class="ph-logo">
        <a href="{{ url('/') }}">
            <img src="{{ url('/images/tanova/tanova-white.png') }}" alt="Tanova"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
            <span class="ph-logo-text" style="display:none;">Tanova</span>
        </a>
    </div>

    {{-- Toggle sidebar on mobile --}}
    <button class="ph-toggle" type="button" onclick="tnvNavToggle()" aria-label="{{ __('Menu') }}" title="{{ __('Menu') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
    </button>

    {{-- Centre nav --}}
    <div class="ph-nav">
        @php
            $isVendor = $user && $user->hasPermission('dashboard_vendor_access');
            $isStaff  = $user && $user->hasPermission('dashboard_access');
        @endphp
        @php
            $hTeam = request()->attributes->get('staff_team') ?: ($user ? \Modules\Vendor\Services\StaffAccess::membership(\Illuminate\Support\Facades\Auth::user()) : null);
            $hMay  = fn ($p) => !$hTeam || \Modules\Vendor\Services\StaffAccess::allows((array) $hTeam->permissions, $p);
        @endphp
        {{-- A company's own pages (Tanova AI, Concierge, TourPay, Integrations) are in its sidebar; the top bar keeps only the platform link. --}}
        @if(!$isVendor && $isStaff)
            <a href="{{ route('admin.integrations.hub', [], false) ?? '#' }}">Integrations</a>
        @endif
        @if($isStaff)
            {{-- Staff who also run a business: always a visible way back to the admin area (and the admin header has the mirror image). --}}
            <a href="{{ route('admin.index', [], false) }}" class="ph-switch" title="Switch to the admin area">&larr; Admin</a>
        @endif
    </div>

    {{-- Right side --}}
    <div class="ph-right">

        {{-- The plan (owners only: staff cannot open it) --}}
        @if($isVendor && !$isStaff && !$hTeam && is_enable_plan())
            @php $pc = \Modules\Vendor\Services\PlanLimits::state($user); @endphp
            <a class="ph-plan {{ in_array($pc['state'], ['none', 'expired']) ? 'is-alert' : ($pc['state'] === 'expiring' ? 'is-warn' : '') }}" href="{{ route('vendor.subscription.index') }}" title="{{ __('Plan & billing') }}">
                <b>{{ $pc['plan'] ?: __('No plan') }}</b>
                <span>@if($pc['state'] === 'none'){{ __('Choose a plan') }}@elseif($pc['state'] === 'expired'){{ __('Ended: renew') }}@elseif($pc['state'] === 'ok' && ($pc['days'] ?? 99) > 14){{ __('Active') }}@else{{ trans_choice(':n day left|:n days left', max(0, (int) $pc['days']), ['n' => max(0, (int) $pc['days'])]) }}@endif</span>
            </a>
        @endif

        {{-- Language picker --}}
        @if(!empty($languages) && is_enable_multi_lang())
        <div class="ph-dd" id="ph-lang-dd">
            <div class="ph-lang-trigger" onclick="phToggle('ph-lang-dd')">
                <svg class="ph-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.9 5.6 3.9 9s-1.3 6.4-3.9 9c-2.6-2.6-3.9-5.6-3.9-9S9.4 5.6 12 3z"/></svg>
                {{ optional($languages->firstWhere('locale', $locale))->name }}
                <svg class="ph-caret" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <div class="ph-menu" id="ph-lang-dd-menu">
                @foreach($languages as $language)
                    @php if($language->locale == $locale) continue; @endphp
                    <a href="{{ route('language.set-lang', ['locale' => $language->locale]) }}">
                        {{ $language->name }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- User menu --}}
        @if(Auth::check())
        <div class="ph-dd" id="ph-user-dd">
            <div class="ph-dd-trigger" onclick="phToggle('ph-user-dd')">
                <div class="ph-dd-avatar">
                    @if($avatar_url = $user->getAvatarUrl())
                        <div class="avatar-cover" style="background-image:url('{{ $avatar_url }}')"></div>
                    @else
                        {{ strtoupper($user->getDisplayName()[0]) }}
                    @endif
                </div>
                <div class="ph-dd-who">
                    <div class="ph-dd-name">{{ $user->getDisplayName() }}</div>
                    <div class="ph-dd-role">{{ $hTeam ? __('Staff') : ucfirst($user->role->name ?? '') }}</div>
                </div>
                <svg class="ph-caret" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <div class="ph-menu" id="ph-user-dd-menu">
                @if($user->hasPermission('dashboard_vendor_access'))
                    <a href="{{ route('vendor.dashboard') }}"><svg class="ph-ic" viewBox="0 0 24 24"><path d="M4 19V5M4 19h16M8 15l3-4 3 3 5-7"/></svg> {{ __('Vendor Dashboard') }}</a>
                    <div class="ph-menu-sep"></div>
                @endif
                <a href="{{ route('user.profile.index') }}"><svg class="ph-ic" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c1-4 4-5.5 7-5.5s6 1.5 7 5.5"/></svg> {{ __('My Profile') }}</a>
                <a href="{{ route('user.booking_history') }}"><svg class="ph-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> {{ __('Booking History') }}</a>
                <a href="{{ route('user.change_password') }}"><svg class="ph-ic" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg> {{ __('Change Password') }}</a>
                @if($user->hasPermission('dashboard_access'))
                    <div class="ph-menu-sep"></div>
                    <a href="{{ route('admin.index') }}"><svg class="ph-ic" viewBox="0 0 24 24"><path d="M4 13a8 8 0 0116 0M12 13l4-4"/><path d="M4 13h2M18 13h2"/></svg> {{ __('Admin Dashboard') }}</a>
                @endif
                <div class="ph-menu-sep"></div>
                <a href="#" onclick="event.preventDefault();document.getElementById('ph-logout-form').submit();">
                    <svg class="ph-ic" viewBox="0 0 24 24"><path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/></svg> {{ __('Logout') }}
                </a>
            </div>
            <form id="ph-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
        </div>
        @endif

    </div>
</div>

<script>
function phToggle(ddId) {
    var menu = document.getElementById(ddId + '-menu');
    var isOpen = menu.classList.contains('open');
    document.querySelectorAll('.ph-menu.open').forEach(function(m){ m.classList.remove('open'); });
    if (!isOpen) menu.classList.add('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.ph-dd')) {
        document.querySelectorAll('.ph-menu.open').forEach(function(m){ m.classList.remove('open'); });
    }
});
</script>
