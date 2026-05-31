@php
    $user = Auth::user();
    $theme = \Modules\Theme\ThemeManager::currentProvider();
    $languages = \Modules\Language\Models\Language::getActive();
    $locale = App::getLocale();
@endphp

<link href="{{ asset('themes/admin/dist/css/style.css') }}" rel="stylesheet">
<style>
    /* Bootstrap 4 dropdown essentials (not in GoTrip Bootstrap 5 vendor) */
    .main-header .dropdown-menu{display:none;position:absolute;top:100%;right:0;left:auto;z-index:9000;min-width:10rem;padding:.5rem 0;background:#fff;border:1px solid rgba(0,0,0,.15);border-radius:.25rem}
    .main-header .dropdown-menu.show{display:block}
    .main-header .dropdown-item{display:block;width:100%;padding:.25rem 1.5rem;clear:both;font-weight:400;color:#212529;text-align:inherit;white-space:nowrap;background:transparent;border:0;text-decoration:none}
    .main-header .dropdown-item:hover,.main-header .dropdown-item:focus{color:#16181b;background-color:#f8f9fa}
    .main-header .dropdown-divider{height:0;margin:.5rem 0;overflow:hidden;border-top:1px solid #e9ecef}
    /* Ensure main-header is above GoTrip sticky nav */
    .main-header{z-index:10000!important}
    /* Push GoTrip fixed header below the tsoka bar */
    body.has-adminbar .header{top:56px!important}
    body.has-adminbar .header-margin{margin-top:146px!important}
</style>

<div class="main-header d-flex">
    <div class="header-logo flex-shrink-0">
        <h3 class="logo-text">
            <a href="{{ url('/') }}">{{ $theme::$name }}
                <span class="app-version">{{ $theme::$version }}</span>
            </a>
        </h3>
    </div>
    <div class="header-widgets d-flex flex-grow-1">
        <div class="widgets-left d-flex flex-grow-1 align-items-center">
            <div class="header-widget search-widget">
                <a href="{{ url('/') }}" class="btn btn-link" target="_blank">
                    <i class="fa fa-eye"></i> {{ __('Home') }}
                </a>
            </div>
        </div>
        <div class="widgets-right flex-shrink-0 d-flex">

            @if(!empty($languages) && is_enable_multi_lang())
            <div class="dropdown header-widget widget-user widget-language flex-shrink-0" id="tsoka-lang-dd">
                <div class="user-dropdown d-flex align-items-center" onclick="tsokaToggle('tsoka-lang-dd')">
                    @foreach($languages as $language)
                        @if($locale == $language->locale)
                            <div class="user-info flex-grow-1 d-flex">
                                @if($language->flag)
                                    <span class="flag-icon mr-2 flag-icon-{{ $language->flag }}"></span>
                                @endif
                                {{ $language->name }}
                            </div>
                        @endif
                    @endforeach
                    <i class="fa fa-angle-down"></i>
                </div>
                <div class="dropdown-menu">
                    @foreach($languages as $language)
                        @php if($language->locale == $locale) continue; @endphp
                        <a class="dropdown-item" href="{{ route('language.set-lang', ['locale' => $language->locale]) }}">
                            @if($language->flag)
                                <span class="flag-icon flag-icon-{{ $language->flag }}"></span>
                            @endif
                            {{ $language->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if(Auth::check())
            <div class="dropdown header-widget widget-user flex-shrink-0" id="tsoka-user-dd">
                <div class="user-dropdown d-flex align-items-center" onclick="tsokaToggle('tsoka-user-dd')">
                    <span class="user-avatar flex-shrink-0">
                        @if($avatar_url = $user->getAvatarUrl())
                            <div class="avatar avatar-cover" style="background-image:url('{{ $avatar_url }}')"></div>
                        @else
                            <span class="avatar-text">{{ ucfirst($user->getDisplayName()[0]) }}</span>
                        @endif
                    </span>
                    <div class="user-info flex-grow-1">
                        <div class="user-name">{{ $user->getDisplayName() }}</div>
                        <div class="user-role">{{ ucfirst($user->role->name ?? '') }}</div>
                    </div>
                    <i class="fa fa-angle-down"></i>
                </div>
                <div class="dropdown-menu">
                    @if($user->hasPermission('dashboard_vendor_access'))
                        <a class="dropdown-item" href="{{ route('vendor.dashboard') }}"><i class="fa fa-line-chart mr-10"></i> {{ __('Vendor Dashboard') }}</a>
                        <div class="dropdown-divider"></div>
                    @endif
                    <a class="dropdown-item" href="{{ route('user.profile.index') }}"><i class="fa fa-address-card mr-10"></i> {{ __('My profile') }}</a>
                    <a class="dropdown-item" href="{{ route('user.booking_history') }}"><i class="fa fa-clock-o mr-10"></i> {{ __('Booking History') }}</a>
                    <a class="dropdown-item" href="{{ route('user.change_password') }}"><i class="fa fa-lock mr-10"></i> {{ __('Change Password') }}</a>
                    @if($user->hasPermission('dashboard_access'))
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('admin.index') }}"><i class="fa fa-dashboard mr-10"></i> {{ __('Admin Dashboard') }}</a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault();document.getElementById('tsoka-bar-logout').submit();">
                        <i class="fa fa-sign-out mr-10"></i> {{ __('Logout') }}
                    </a>
                </div>
                <form id="tsoka-bar-logout" action="{{ route('logout') }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
            </div>
            @endif

        </div>
    </div>
</div>

<script>
function tsokaToggle(id) {
    var dd = document.getElementById(id);
    var isOpen = dd.classList.contains('show');
    document.querySelectorAll('.main-header .dropdown.show').forEach(function(el) {
        el.classList.remove('show');
        el.querySelector('.dropdown-menu').classList.remove('show');
    });
    if (!isOpen) {
        dd.classList.add('show');
        dd.querySelector('.dropdown-menu').classList.add('show');
    }
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.main-header .dropdown')) {
        document.querySelectorAll('.main-header .dropdown.show').forEach(function(el) {
            el.classList.remove('show');
            el.querySelector('.dropdown-menu').classList.remove('show');
        });
    }
});
</script>
