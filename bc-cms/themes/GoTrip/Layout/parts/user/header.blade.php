@php
    $user = Auth::user();
    $theme = \Modules\Theme\ThemeManager::currentProvider();
    $languages = \Modules\Language\Models\Language::getActive();
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
        padding: 0 20px 0 0;
        gap: 0;
    }
    .bc_wrap .header-margin { margin-top: 56px !important; }

    /* Logo area */
    .ph-logo {
        width: 220px;
        min-width: 220px;
        display: flex;
        align-items: center;
        padding: 0 20px;
        flex-shrink: 0;
        border-right: 1px solid #e8e8e8;
        height: 100%;
    }
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
        border-radius: 4px;
        color: #a0a0a0;
        font-size: 20px;
        cursor: pointer;
        transition: background .12s, color .12s;
        margin-left: 4px;
    }
    .ph-toggle:hover { background: #f5f5f5; color: #0a0a0a; }

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
        font-size: 12px;
        color: #222222;
        text-decoration: none;
        transition: background .1s;
    }
    .ph-menu a i { width: 14px; color: #a0a0a0; font-size: 12px; }
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
    <button class="ph-toggle btn-toggle-admin-menu" data-x-click="dashboard" aria-label="Menu">
        <i class="ion-ios-menu"></i>
    </button>

    {{-- Centre nav --}}
    <div class="ph-nav">
        @php
            $isVendor = $user && $user->hasPermission('dashboard_vendor_access');
            $isStaff  = $user && $user->hasPermission('dashboard_access');
        @endphp
        @if($isVendor)
            <a href="{{ route('user.integrations.index', [], false) ?? '#' }}">Integrations</a>
            <a href="{{ route('user.concierge.index', [], false) ?? '#' }}">Concierge</a>
            <a href="{{ route('admin.tanova.index', [], false) ?? '#' }}">Tanova</a>
            <a href="{{ route('tourpay.vendor.index', [], false) ?? '#' }}">TourPay</a>
        @elseif($isStaff)
            <a href="{{ route('admin.integrations.hub', [], false) ?? '#' }}">Integrations</a>
        @endif
        @if($isStaff)
            {{-- Staff who also run a business: always a visible way back to the admin area (and the admin header has the mirror image). --}}
            <a href="{{ route('admin.index', [], false) }}" class="ph-switch" title="Switch to the admin area">&larr; Admin</a>
        @endif
    </div>

    {{-- Right side --}}
    <div class="ph-right">

        {{-- Language picker --}}
        @if(!empty($languages) && is_enable_multi_lang())
        <div class="ph-dd" id="ph-lang-dd">
            <div class="ph-lang-trigger" onclick="phToggle('ph-lang-dd')">
                @foreach($languages as $language)
                    @if($locale == $language->locale)
                        @if($language->flag)<span class="flag-icon flag-icon-{{ $language->flag }}"></span>@endif
                        {{ $language->name }}
                    @endif
                @endforeach
                <i class="fa fa-angle-down ph-dd-caret"></i>
            </div>
            <div class="ph-menu" id="ph-lang-dd-menu">
                @foreach($languages as $language)
                    @php if($language->locale == $locale) continue; @endphp
                    <a href="{{ route('language.set-lang', ['locale' => $language->locale]) }}">
                        @if($language->flag)<span class="flag-icon flag-icon-{{ $language->flag }}"></span>@endif
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
                <div>
                    <div class="ph-dd-name">{{ $user->getDisplayName() }}</div>
                    <div class="ph-dd-role">{{ ucfirst($user->role->name ?? '') }}</div>
                </div>
                <i class="fa fa-angle-down ph-dd-caret"></i>
            </div>
            <div class="ph-menu" id="ph-user-dd-menu">
                @if($user->hasPermission('dashboard_vendor_access'))
                    <a href="{{ route('vendor.dashboard') }}"><i class="fa fa-line-chart"></i> {{ __('Vendor Dashboard') }}</a>
                    <div class="ph-menu-sep"></div>
                @endif
                <a href="{{ route('user.profile.index') }}"><i class="fa fa-address-card"></i> {{ __('My Profile') }}</a>
                <a href="{{ route('user.booking_history') }}"><i class="fa fa-clock-o"></i> {{ __('Booking History') }}</a>
                <a href="{{ route('user.change_password') }}"><i class="fa fa-lock"></i> {{ __('Change Password') }}</a>
                @if($user->hasPermission('dashboard_access'))
                    <div class="ph-menu-sep"></div>
                    <a href="{{ route('admin.index') }}"><i class="fa fa-dashboard"></i> {{ __('Admin Dashboard') }}</a>
                @endif
                <div class="ph-menu-sep"></div>
                <a href="#" onclick="event.preventDefault();document.getElementById('ph-logout-form').submit();">
                    <i class="fa fa-sign-out"></i> {{ __('Logout') }}
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
