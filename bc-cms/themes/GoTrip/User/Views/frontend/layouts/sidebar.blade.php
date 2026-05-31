<?php
/*
|--------------------------------------------------------------------------
| Vendor sidebar — data collection
|--------------------------------------------------------------------------
*/
$dataUser = Auth::user();

// ── 1. Seed with core items ────────────────────────────────────────────
$menus = [
    'dashboard' => [
        'url'        => route('vendor.dashboard'),
        'title'      => __('Dashboard'),
        'icon'       => 'icofont-home',
        'permission' => 'dashboard_vendor_access',
        'position'   => 10,
    ],
    'booking-history' => [
        'url'      => route('user.booking_history'),
        'title'    => __('Booking History'),
        'icon'     => 'icofont-history',
        'position' => 20,
    ],
    'admin' => [
        'url'        => route('admin.index'),
        'title'      => __('Admin Dashboard'),
        'icon'       => 'icofont-crown',
        'permission' => 'dashboard_access',
        'position'   => 999,
    ],
];

// ── 2. Collect menus from all module providers ─────────────────────────
$providerSets = [
    \Modules\ServiceProvider::getActivatedModules(),
];
foreach ($providerSets as $providerList) {
    foreach ($providerList as $module) {
        $moduleClass = $module['class'] ?? null;
        if (!$moduleClass || !class_exists($moduleClass)) continue;

        $menuConfig = call_user_func([$moduleClass, 'getUserMenu']);
        if (!empty($menuConfig)) $menus = array_merge($menus, $menuConfig);

        $subMenus = call_user_func([$moduleClass, 'getUserSubMenu']);
        if (!empty($subMenus)) {
            foreach ($subMenus as $k => $submenu) {
                $submenu['id'] = $submenu['id'] ?? '_' . $k;
                if (!empty($submenu['parent']) && isset($menus[$submenu['parent']])) {
                    $menus[$submenu['parent']]['children'][$submenu['id']] = $submenu;
                    $menus[$submenu['parent']]['children'] = array_values(
                        \Illuminate\Support\Arr::sort($menus[$submenu['parent']]['children'], fn($v) => $v['position'] ?? 100)
                    );
                }
            }
        }
    }
}

// Plugin modules
foreach (\Plugins\ServiceProvider::getModules() as $module) {
    $cls = "\\Plugins\\" . ucfirst($module) . "\\ModuleProvider";
    if (!class_exists($cls)) continue;
    $cfg = call_user_func([$cls, 'getUserMenu']);
    if (!empty($cfg)) $menus = array_merge($menus, $cfg);
}

// Custom modules
foreach (\Custom\ServiceProvider::getModules() as $module) {
    $cls = "\\Custom\\" . ucfirst($module) . "\\ModuleProvider";
    if (!class_exists($cls)) continue;
    $cfg = call_user_func([$cls, 'getUserMenu']);
    if (!empty($cfg)) $menus = array_merge($menus, $cfg);
}

// ── 3. Permission filtering + active state ─────────────────────────────
$currentUrl = url(\Illuminate\Support\Facades\Route::current()->uri());

foreach ($menus as $k => $item) {
    if (!empty($item['permission']) && !Auth::user()->hasPermission($item['permission'])) {
        unset($menus[$k]);
        continue;
    }
    $menus[$k]['class'] = ($currentUrl === url($item['url'])) ? 'is-active' : '';
    if (!empty($item['children'])) {
        $menus[$k]['class'] .= ' has-children';
        foreach ($item['children'] as $k2 => $child) {
            if (!empty($child['permission']) && !Auth::user()->hasPermission($child['permission'])) {
                unset($menus[$k]['children'][$k2]);
                continue;
            }
            $menus[$k]['children'][$k2]['class'] = ($currentUrl === url($child['url'])) ? 'is-active' : '';
        }
    }
}

// Sort by position — keep string keys (do NOT array_values)
$menus = \Illuminate\Support\Arr::sort($menus, fn($v) => $v['position'] ?? 100);

// ── 4. Section grouping ────────────────────────────────────────────────
$navConfig = config('vendor_nav', ['sections' => [], 'gated' => []]);
$gateMap   = $navConfig['gated'] ?? [];

// Vendor plan data: array keyed by post_type, or null
$planData = $dataUser->vendorPlanData;

$sections  = [];
$usedKeys  = [];

foreach ($navConfig['sections'] as $sectionId => $sectionCfg) {
    $active  = [];   // accessible items
    $locked  = [];   // gated items vendor can't access yet

    foreach ($sectionCfg['keys'] as $key) {
        if (!isset($menus[$key])) continue;
        $usedKeys[] = $key;

        $item     = $menus[$key];
        $gateType = $gateMap[$key] ?? null;

        if ($gateType && empty($planData[$gateType]['enable'])) {
            $locked[] = $item;
        } else {
            $active[] = $item;
        }
    }

    if (empty($active) && empty($locked)) continue;

    $sections[$sectionId] = [
        'label'  => __($sectionCfg['label']),
        'active' => $active,
        'locked' => $locked,
    ];
}

// Overflow: items not assigned to any section (except admin handled separately)
$overflow = [];
foreach ($menus as $key => $item) {
    if (!in_array($key, $usedKeys) && $key !== 'admin') {
        $overflow[] = $item;
    }
}
$adminItem = $menus['admin'] ?? null;

// ── 5. Helper: clean icon class (strip legacy 'icon ' prefix) ──────────
if (!function_exists('tsoka_icon')) {
    function tsoka_icon($class) {
        return str_starts_with((string)$class, 'icon ') ? substr($class, 5) : $class;
    }
}
?>
{{-- ─────────────────────────────────────────────────────────────────── --}}
<style>
.tsoka-sidebar {
    width: 220px !important;
    min-width: 220px !important;
    background: #111111 !important;
    display: flex !important;
    flex-direction: column !important;
    height: 100vh !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    overflow-y: auto !important;
    padding: 0 0 20px !important;
    flex-shrink: 0 !important;
    z-index: 10001 !important;
}
.tsoka-sidebar::-webkit-scrollbar { width: 0 !important; }
.dashboard { padding-left: 220px !important; display: block !important; }
.dashboard__main { margin-left: 0 !important; }
.tsoka-sidebar, .tsoka-sidebar * { color: #ffffff !important; }

/* Brand */
.tsoka-sb-brand {
    height: 56px !important;
    display: flex !important;
    align-items: center !important;
    padding: 0 20px !important;
    border-bottom: 1px solid rgba(255,255,255,.07) !important;
    flex-shrink: 0 !important;
}
.tsoka-sb-brand a { display:flex !important; align-items:center !important; text-decoration:none !important; gap:8px !important; }
.tsoka-sb-brand img { height:30px !important; width:auto !important; max-width:160px !important; object-fit:contain !important; }
.tsoka-sb-brand-text { font-size:16px !important; font-weight:700 !important; color:#fff !important; letter-spacing:-.02em !important; }

/* User block */
.tsoka-sb-user { padding:16px 20px !important; border-bottom:1px solid rgba(255,255,255,.07) !important; margin-bottom:4px !important; }
.tsoka-sb-name { font-size:13px !important; font-weight:600 !important; color:#fff !important; margin-bottom:1px !important; white-space:nowrap !important; overflow:hidden !important; text-overflow:ellipsis !important; }
.tsoka-sb-role { font-size:10px !important; font-weight:500 !important; letter-spacing:.08em !important; text-transform:uppercase !important; color:rgba(255,255,255,.4) !important; }
.tsoka-sb-since { font-size:10px !important; color:rgba(255,255,255,.3) !important; margin-top:1px !important; }
.tsoka-sb-upgrade { display:inline-block !important; margin-top:12px !important; font-size:11px !important; font-weight:500 !important; letter-spacing:.05em !important; color:#111 !important; background:rgba(255,255,255,.9) !important; border-radius:3px !important; padding:5px 12px !important; text-decoration:none !important; }
.tsoka-sb-upgrade:hover { background:#fff !important; color:#111 !important; }

/* Section labels */
.tsoka-sb-section {
    font-size: 9px !important;
    font-weight: 600 !important;
    letter-spacing: .14em !important;
    text-transform: uppercase !important;
    color: rgba(255,255,255,.25) !important;
    padding: 14px 20px 3px !important;
}

/* Nav */
.tsoka-sb-nav { flex:1 !important; }
.tsoka-sb-item { position:relative !important; }

.tsoka-sidebar .tsoka-sb-link,
.tsoka-sidebar a.tsoka-sb-link {
    display:flex !important; align-items:center !important; gap:10px !important;
    padding:8px 20px !important; font-size:13px !important; font-weight:400 !important;
    color:#fff !important; text-decoration:none !important;
    transition:color .12s, background .12s !important;
    border-left:2px solid transparent !important;
    white-space:nowrap !important; background:transparent !important; border-radius:0 !important;
}
.tsoka-sidebar .tsoka-sb-link i {
    font-size:14px !important; width:16px !important; text-align:center !important;
    flex-shrink:0 !important; color:#fff !important; transition:color .12s !important;
}
.tsoka-sidebar .tsoka-sb-link:hover,
.tsoka-sidebar a.tsoka-sb-link:hover { color:rgba(255,255,255,.6) !important; background:rgba(255,255,255,.04) !important; text-decoration:none !important; }
.tsoka-sidebar .tsoka-sb-link:hover i { color:rgba(255,255,255,.4) !important; }

.tsoka-sidebar .tsoka-sb-link.is-active,
.tsoka-sidebar a.tsoka-sb-link.is-active {
    color:#fff !important; background:rgba(255,255,255,.07) !important;
    border-left-color:#fff !important; font-weight:500 !important; text-decoration:none !important;
}
.tsoka-sidebar .tsoka-sb-link.is-active i { color:rgba(255,255,255,.65) !important; }

/* Children */
.tsoka-sb-children { padding:2px 0 6px 46px !important; }
.tsoka-sidebar .tsoka-sb-children a {
    display:block !important; font-size:12px !important; color:rgba(255,255,255,.75) !important;
    text-decoration:none !important; padding:5px 0 !important; transition:color .12s !important;
    background:transparent !important; border:none !important;
}
.tsoka-sidebar .tsoka-sb-children a:hover,
.tsoka-sidebar .tsoka-sb-children a.is-active { color:#fff !important; text-decoration:none !important; }

/* Locked / upgrade block */
.tsoka-sb-locked {
    margin:6px 20px 8px !important;
    border:1px dashed rgba(255,255,255,.12) !important;
    border-radius:4px !important;
    padding:8px 10px 6px !important;
}
.tsoka-sb-locked-label {
    font-size:9px !important; font-weight:600 !important; letter-spacing:.1em !important;
    text-transform:uppercase !important; color:rgba(255,255,255,.25) !important;
    margin-bottom:4px !important; display:block !important;
}
.tsoka-sb-locked-item {
    display:flex !important; align-items:center !important; gap:8px !important;
    padding:5px 2px !important; color:rgba(255,255,255,.3) !important; font-size:12px !important;
}
.tsoka-sb-locked-item i { font-size:13px !important; width:14px !important; text-align:center !important; flex-shrink:0 !important; }
.tsoka-sb-locked-item .tsoka-sb-lock { margin-left:auto !important; font-size:11px !important; }

/* Footer */
.tsoka-sb-foot { padding:14px 0 0 !important; border-top:1px solid rgba(255,255,255,.06) !important; margin-top:8px !important; }
</style>

<div class="tsoka-sidebar">

    {{-- Brand --}}
    <div class="tsoka-sb-brand">
        <a href="{{ url('/') }}">
            @php $logoId = setting_item('logo_id'); @endphp
            @if($logoId)
                <img src="{{ get_file_url($logoId,'full') }}" alt="{{ setting_item('site_title','Tsoka') }}">
            @else
                <span class="tsoka-sb-brand-text">Tsoka</span>
            @endif
        </a>
    </div>

    {{-- User info --}}
    <div class="tsoka-sb-user">
        <div class="tsoka-sb-name">{{ $dataUser->getDisplayName() }}</div>
        <div class="tsoka-sb-role">{{ $dataUser->role_name }}</div>
        <div class="tsoka-sb-since">{{ __('Since :t', ['t' => date('M Y', strtotime($dataUser->created_at))]) }}</div>
        @if(!Auth::user()->hasPermission('dashboard_vendor_access') && setting_item('vendor_enable'))
            <a class="tsoka-sb-upgrade" href="{{ route('user.upgrade_vendor') }}">{{ __('Become a vendor') }}</a>
        @endif
    </div>

    {{-- Sectioned nav --}}
    <nav class="tsoka-sb-nav">

        @foreach($sections as $sectionId => $section)

            {{-- Section label (skip for 'overview' so Dashboard sits flush at the top) --}}
            @if($sectionId !== 'overview')
                <div class="tsoka-sb-section">{{ $section['label'] }}</div>
            @endif

            {{-- Active / accessible items --}}
            @foreach($section['active'] as $menuItem)
                @php
                    $isActive      = str_contains($menuItem['class'], 'is-active');
                    $hasActiveChild = false;
                    if (!empty($menuItem['children'])) {
                        foreach ($menuItem['children'] as $c) {
                            if (str_contains($c['class'] ?? '', 'is-active')) { $hasActiveChild = true; break; }
                        }
                    }
                    $open = $isActive || $hasActiveChild;
                    $icon = tsoka_icon($menuItem['icon'] ?? '');
                @endphp
                <div class="tsoka-sb-item">
                    @if(!empty($menuItem['children']))
                        <a href="#"
                           onclick="event.preventDefault();tsokaToggle(this);"
                           class="tsoka-sb-link {{ $open ? 'is-active' : '' }}">
                            @if($icon)<i class="{{ $icon }}"></i>@endif
                            <span>{!! clean($menuItem['title']) !!}</span>
                            <i class="fa fa-angle-down tsoka-caret" style="margin-left:auto;font-size:10px;transition:transform .2s;{{ $open ? 'transform:rotate(180deg)' : '' }}"></i>
                        </a>
                        <div class="tsoka-sb-children" style="{{ $open ? '' : 'display:none;' }}">
                            @foreach($menuItem['children'] as $child)
                                <a href="{{ url($child['url']) }}"
                                   class="{{ str_contains($child['class'] ?? '', 'is-active') ? 'is-active' : '' }}">
                                    {!! clean($child['title']) !!}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a href="{{ url($menuItem['url']) }}"
                           class="tsoka-sb-link {{ $isActive ? 'is-active' : '' }}">
                            @if($icon)<i class="{{ $icon }}"></i>@endif
                            <span>{!! clean($menuItem['title']) !!}</span>
                        </a>
                    @endif
                </div>
            @endforeach

            {{-- Locked / upgrade items --}}
            @if(!empty($section['locked']))
                <div class="tsoka-sb-locked">
                    <span class="tsoka-sb-locked-label">{{ __('Available with upgrade') }}</span>
                    @foreach($section['locked'] as $lockedItem)
                        @php $lockedIcon = tsoka_icon($lockedItem['icon'] ?? 'icofont-box'); @endphp
                        <div class="tsoka-sb-locked-item">
                            <i class="{{ $lockedIcon }}"></i>
                            <span>{{ $lockedItem['title'] }}</span>
                            <i class="icofont-lock tsoka-sb-lock"></i>
                        </div>
                    @endforeach
                </div>
            @endif

        @endforeach

        {{-- Overflow: anything not in any section (except admin) --}}
        @if(!empty($overflow))
            <div class="tsoka-sb-section">{{ __('More') }}</div>
            @foreach($overflow as $menuItem)
                @php
                    $isActive = str_contains($menuItem['class'], 'is-active');
                    $icon     = tsoka_icon($menuItem['icon'] ?? '');
                @endphp
                <div class="tsoka-sb-item">
                    <a href="{{ url($menuItem['url']) }}"
                       class="tsoka-sb-link {{ $isActive ? 'is-active' : '' }}">
                        @if($icon)<i class="{{ $icon }}"></i>@endif
                        <span>{!! clean($menuItem['title']) !!}</span>
                    </a>
                </div>
            @endforeach
        @endif

    </nav>

    <script>
    function tsokaToggle(link) {
        var children = link.nextElementSibling;
        var caret    = link.querySelector('.tsoka-caret');
        var open     = children.style.display !== 'none';
        children.style.display = open ? 'none' : 'block';
        if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
    }
    </script>

    {{-- Admin dashboard link (pinned above logout for admins) --}}
    @if($adminItem)
        <div class="tsoka-sb-foot" style="border-top:none !important; padding-top:0 !important;">
            <a href="{{ url($adminItem['url']) }}" class="tsoka-sb-link">
                <i class="{{ tsoka_icon($adminItem['icon'] ?? 'icofont-crown') }}"></i>
                <span>{{ $adminItem['title'] }}</span>
            </a>
        </div>
    @endif

    {{-- Logout (always pinned at bottom) --}}
    <div class="tsoka-sb-foot">
        <form id="logout-form-vendor" action="{{ route('logout') }}" method="POST" style="display:none;">
            {{ csrf_field() }}
        </form>
        <a href="#"
           onclick="event.preventDefault();document.getElementById('logout-form-vendor').submit();"
           class="tsoka-sb-link">
            <i class="icofont-logout"></i>
            <span>{{ __('Log Out') }}</span>
        </a>
    </div>

</div>
