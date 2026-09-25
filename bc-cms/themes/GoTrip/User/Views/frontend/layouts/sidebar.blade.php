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

// Company staff (see ActAsCompany) only see what the owner gave them.
$staffTeam = request()->attributes->get('staff_team');
$staffMay  = fn ($url) => !$staffTeam || \Modules\Vendor\Services\StaffAccess::allows((array) $staffTeam->permissions, (string) parse_url((string) $url, PHP_URL_PATH));

foreach ($menus as $k => $item) {
    if (!empty($item['permission']) && !Auth::user()->hasPermission($item['permission'])) {
        unset($menus[$k]);
        continue;
    }
    if ($staffTeam && $k !== 'admin' && !empty($item['url']) && !$staffMay($item['url'])) {
        unset($menus[$k]);
        continue;
    }
    $menus[$k]['class'] = ($currentUrl === url($item['url'])) ? 'is-active' : '';
    if (!empty($item['children'])) {
        $menus[$k]['class'] .= ' has-children';
        foreach ($item['children'] as $k2 => $child) {
            if ((!empty($child['permission']) && !Auth::user()->hasPermission($child['permission'])) || ($staffTeam && !empty($child['url']) && !$staffMay($child['url']))) {
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

// Collect a list of keys into [accessible, plan-locked], tracking usedKeys.
$collect = function (array $keys) use ($menus, $gateMap, $planData, &$usedKeys) {
    $active = []; $locked = [];
    foreach ($keys as $key) {
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
    return [$active, $locked];
};

foreach ($navConfig['sections'] as $sectionId => $sectionCfg) {
    // Section with nested sub-groups (e.g. Catalog → Stays / Activities / …)
    if (!empty($sectionCfg['groups'])) {
        $subgroups = [];
        foreach ($sectionCfg['groups'] as $subId => $subCfg) {
            [$sa, $sl] = $collect($subCfg['keys'] ?? []);
            if (empty($sa) && empty($sl)) continue;
            $subgroups[$subId] = ['label' => __($subCfg['label']), 'active' => $sa, 'locked' => $sl];
        }
        if (empty($subgroups)) continue;
        $sections[$sectionId] = ['label' => __($sectionCfg['label']), 'subgroups' => $subgroups, 'active' => [], 'locked' => []];
        continue;
    }

    // Flat section
    [$active, $locked] = $collect($sectionCfg['keys'] ?? []);
    if (empty($active) && empty($locked)) continue;
    $sections[$sectionId] = ['label' => __($sectionCfg['label']), 'active' => $active, 'locked' => $locked];
}

// Overflow: items not assigned to any section (except admin handled separately)
$overflow = [];
foreach ($menus as $key => $item) {
    if (!in_array($key, $usedKeys) && $key !== 'admin') {
        $overflow[] = $item;
    }
}
$adminItem = $staffTeam ? null : ($menus['admin'] ?? null);

// ── 5. Helper: clean icon class (strip legacy 'icon ' prefix) ──────────
if (!function_exists('tsoka_icon')) {
    function tsoka_icon($class) {
        return str_starts_with((string)$class, 'icon ') ? substr($class, 5) : $class;
    }
}
?>
{{-- ─────────────────────────────────────────────────────────────────── --}}
<style>
/* ════════════════ Tanova vendor sidebar — refined monochrome + gold ════════════════ */
.tsoka-sidebar {
    --tnv-bg: #0d0d10; --tnv-raised: #16161a; --tnv-line: rgba(255,255,255,.06);
    --tnv-text: #ffffff; --tnv-muted: rgba(255,255,255,.55); --tnv-label: rgba(255,255,255,.30);
    --tnv-gold: #E0A23B; --tnv-w: 264px; --tnv-rail: 72px;
    width: var(--tnv-w) !important; min-width: var(--tnv-w) !important;
    background: var(--tnv-bg) !important;
    display: flex !important; flex-direction: column !important;
    height: 100vh !important; position: fixed !important; top: 0 !important; left: 0 !important;
    padding: 0 !important; flex-shrink: 0 !important; z-index: 10001 !important;
    overflow: hidden !important;
    transition: width .26s cubic-bezier(.4,0,.2,1), min-width .26s cubic-bezier(.4,0,.2,1) !important;
}
.dashboard { padding-left: 264px !important; display: block !important; transition: padding-left .26s cubic-bezier(.4,0,.2,1) !important; }
.dashboard__main { margin-left: 0 !important; }
body.tnv-rail .dashboard, body.tnv-rail .bc_user_profile.dashboard { padding-left: 72px !important; }
body.tnv-rail .tsoka-sidebar { width: 72px !important; min-width: 72px !important; }
body.tnv-rail .ph-logo { width: 72px !important; }

/* Brand */
.tsoka-sb-brand {
    height: 60px !important; display: flex !important; align-items: center !important;
    justify-content: space-between !important; gap: 8px !important;
    padding: 0 16px 0 20px !important; border-bottom: 1px solid var(--tnv-line) !important; flex-shrink: 0 !important;
}
.tsoka-sb-brand a { display:flex !important; align-items:center !important; text-decoration:none !important; }
.tsoka-sb-brand img { height:30px !important; width:auto !important; max-width:165px !important; object-fit:contain !important; transition:opacity .2s !important; }
.tnv-rail-btn {
    background:transparent !important; border:0 !important; color:var(--tnv-label) !important;
    cursor:pointer !important; padding:6px !important; border-radius:6px !important; line-height:0 !important;
    transition:color .15s, background .15s !important;
}
.tnv-rail-btn:hover { color:var(--tnv-text) !important; background:rgba(255,255,255,.06) !important; }
body.tnv-rail .tsoka-sb-brand img { opacity:0 !important; }

/* "Become a vendor" CTA */
.tsoka-sb-user { padding:14px 18px !important; }
.tsoka-sb-upgrade { display:inline-block !important; font-size:11px !important; font-weight:600 !important; letter-spacing:.04em !important; color:#0d0d10 !important; background:var(--tnv-gold) !important; border-radius:6px !important; padding:7px 14px !important; text-decoration:none !important; }

/* Scroll region */
.tsoka-sb-nav { flex:1 1 auto !important; overflow-y:auto !important; padding:8px 0 14px !important; }
.tsoka-sb-nav::-webkit-scrollbar { width:6px !important; }
.tsoka-sb-nav::-webkit-scrollbar-thumb { background:rgba(255,255,255,.08) !important; border-radius:6px !important; }
.tsoka-sb-nav:hover::-webkit-scrollbar-thumb { background:rgba(255,255,255,.16) !important; }

/* Group accordion */
.tnv-group { margin:1px 0 !important; }
.tnv-group-head {
    width:100% !important; display:flex !important; align-items:center !important; gap:8px !important;
    background:transparent !important; border:0 !important; cursor:pointer !important;
    padding:16px 22px 6px !important; color:var(--tnv-label) !important;
    font-size:11px !important; font-weight:700 !important; letter-spacing:.15em !important; text-transform:uppercase !important;
    transition:color .15s !important;
}
.tnv-group-head:hover { color:var(--tnv-muted) !important; }
.tnv-group-label { flex:1 !important; text-align:left !important; }
.tnv-chev { font-size:9px !important; transition:transform .26s cubic-bezier(.4,0,.2,1) !important; opacity:.7 !important; }
.tnv-group.is-open > .tnv-group-head .tnv-chev { transform:rotate(180deg) !important; }
/* grid-rows expand/collapse — smooth, content-height aware */
.tnv-group-body { display:grid !important; grid-template-rows:0fr !important; transition:grid-template-rows .26s cubic-bezier(.4,0,.2,1) !important; }
.tnv-group.is-open > .tnv-group-body { grid-template-rows:1fr !important; }
.tnv-group-body > div { overflow:hidden !important; min-height:0 !important; }
.tnv-overview .tnv-group-body { grid-template-rows:1fr !important; }  /* overview always open */

/* Nested sub-groups (e.g. Catalog → Stays / Activities / …) */
.tnv-subgroup { margin:0 !important; }
.tnv-subgroup > .tnv-group-head { padding:7px 20px 5px 28px !important; font-size:9px !important; letter-spacing:.12em !important; color:rgba(255,255,255,.32) !important; }
.tnv-subgroup > .tnv-group-head .tnv-chev { font-size:8px !important; }
.tsoka-sidebar .tnv-subgroup .tnv-link { margin-left:14px !important; }

/* Links */
.tsoka-sidebar .tnv-link {
    position:relative !important; display:flex !important; align-items:center !important; gap:13px !important;
    margin:2px 10px !important; padding:10px 14px !important; border-radius:9px !important;
    font-size:14.5px !important; font-weight:400 !important; color:var(--tnv-muted) !important;
    text-decoration:none !important; white-space:nowrap !important; background:transparent !important;
    transition:color .16s ease, background .16s ease, transform .16s ease !important;
}
.tsoka-sidebar .tnv-link i { font-size:19px !important; width:22px !important; text-align:center !important; flex-shrink:0 !important; color:var(--tnv-muted) !important; transition:color .16s, transform .16s !important; }
.tsoka-sidebar .tnv-link span.tnv-label { flex:1 !important; overflow:hidden !important; text-overflow:ellipsis !important; }
.tsoka-sidebar .tnv-link:hover { color:var(--tnv-text) !important; background:rgba(255,255,255,.05) !important; text-decoration:none !important; }
.tsoka-sidebar .tnv-link:hover i { color:var(--tnv-text) !important; transform:translateX(2px) !important; }

/* Active pill — gold bar + raised surface + soft glow */
.tsoka-sidebar .tnv-link.is-active {
    color:var(--tnv-text) !important; background:var(--tnv-raised) !important; font-weight:600 !important;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.04), 0 4px 16px -8px rgba(224,162,59,.5) !important;
}
.tsoka-sidebar .tnv-link.is-active::before {
    content:'' !important; position:absolute !important; left:-10px !important; top:8px !important; bottom:8px !important;
    width:3px !important; border-radius:0 3px 3px 0 !important; background:var(--tnv-gold) !important;
    box-shadow:0 0 10px 0 rgba(224,162,59,.7) !important;
}
.tsoka-sidebar .tnv-link.is-active i { color:var(--tnv-gold) !important; }

/* "New" pill + count badge */
.tnv-new { font-size:9px !important; font-weight:700 !important; letter-spacing:.08em !important; text-transform:uppercase !important;
    color:var(--tnv-gold) !important; border:1px solid rgba(224,162,59,.4) !important; border-radius:4px !important; padding:1px 5px !important; }
.tnv-badge { min-width:18px !important; height:18px !important; padding:0 5px !important; border-radius:9px !important;
    background:var(--tnv-gold) !important; color:#0d0d10 !important; font-size:11px !important; font-weight:700 !important;
    display:inline-flex !important; align-items:center !important; justify-content:center !important; }

/* Children (sub-items under hotel/car) */
.tnv-children { padding:0 0 4px 44px !important; }
.tsoka-sidebar .tnv-children a { display:block !important; font-size:13px !important; color:var(--tnv-muted) !important;
    text-decoration:none !important; padding:5px 0 !important; transition:color .15s !important; }
.tsoka-sidebar .tnv-children a:hover, .tsoka-sidebar .tnv-children a.is-active { color:var(--tnv-text) !important; }

/* Locked / upgrade block */
.tsoka-sb-locked { margin:4px 14px 8px !important; border:1px dashed rgba(255,255,255,.10) !important; border-radius:8px !important; padding:8px 10px 6px !important; }
.tsoka-sb-locked-label { font-size:9px !important; font-weight:700 !important; letter-spacing:.1em !important; text-transform:uppercase !important; color:var(--tnv-label) !important; margin-bottom:4px !important; display:block !important; }
.tsoka-sb-locked-item { display:flex !important; align-items:center !important; gap:10px !important; padding:5px 2px !important; color:var(--tnv-label) !important; font-size:12px !important; }
.tsoka-sb-locked-item i { font-size:15px !important; width:20px !important; text-align:center !important; flex-shrink:0 !important; }
.tsoka-sb-locked-item .tsoka-sb-lock { margin-left:auto !important; font-size:11px !important; }

/* Pinned footer area (settings + logout) */
.tnv-foot { flex-shrink:0 !important; border-top:1px solid var(--tnv-line) !important; padding:8px 0 12px !important; }

/* ───────── Rail (collapsed) mode ───────── */
body.tnv-rail .tnv-group-head, body.tnv-rail .tsoka-sb-user,
body.tnv-rail .tnv-link .tnv-label, body.tnv-rail .tnv-link .tnv-new,
body.tnv-rail .tnv-children, body.tnv-rail .tsoka-sb-locked,
body.tnv-rail .tnv-link span:not(.tnv-badge) { display:none !important; }
body.tnv-rail .tnv-group-body { grid-template-rows:1fr !important; }   /* show all icons in rail */
body.tnv-rail .tsoka-sidebar .tnv-link { justify-content:center !important; margin:2px 8px !important; padding:9px 0 !important; gap:0 !important; }
body.tnv-rail .tnv-badge { position:absolute !important; top:3px !important; right:8px !important; min-width:8px !important; height:8px !important; padding:0 !important; }

@media (prefers-reduced-motion: reduce) {
    .tsoka-sidebar, .dashboard, .tnv-chev, .tnv-group-body, .tsoka-sidebar .tnv-link, .tsoka-sidebar .tnv-link i { transition:none !important; }
}
</style>

<div class="tsoka-sidebar">

    {{-- Brand --}}
    <div class="tsoka-sb-brand">
        <a href="{{ url('/') }}">
            {{-- Tanova white wordmark for the dark vendor sidebar --}}
            <img src="{{ url('/images/tanova/tanova-white.png') }}" alt="Tanova">
        </a>
        <button type="button" class="tnv-rail-btn" onclick="tnvToggleRail()" title="{{ __('Collapse menu') }}" aria-label="{{ __('Collapse menu') }}">
            <i class="icofont-navigation-menu" style="font-size:16px"></i>
        </button>
    </div>

    {{-- Company details intentionally omitted here — shown in the top-right profile menu.
         Keep only the "Become a vendor" CTA for non-vendor users. --}}
    @if(!Auth::user()->hasPermission('dashboard_vendor_access') && setting_item('vendor_enable'))
        <div class="tsoka-sb-user">
            <a class="tsoka-sb-upgrade" href="{{ route('user.upgrade_vendor') }}">{{ __('Become a vendor') }}</a>
        </div>
    @endif

    {{-- Accordion nav (working groups; 'settings' is pinned in the footer below) --}}
    @php
        $settingsSection = $sections['settings'] ?? null;
        $workingSections = $sections; unset($workingSections['settings']);
    @endphp
    @php
        // true if a list of items (or their children) contains the active page
        $tnvScan = function ($items) {
            foreach ($items as $mi) {
                if (str_contains($mi['class'] ?? '', 'is-active')) return true;
                foreach (($mi['children'] ?? []) as $c) { if (str_contains($c['class'] ?? '', 'is-active')) return true; }
            }
            return false;
        };
    @endphp
    <nav class="tsoka-sb-nav" id="tnvNav">
        @foreach($workingSections as $sectionId => $section)
            @php
                $isOverview   = ($sectionId === 'overview');
                $hasSubgroups = !empty($section['subgroups']);
                $groupOpen    = false;
                if ($hasSubgroups) {
                    foreach ($section['subgroups'] as $sg) { if ($tnvScan($sg['active'])) { $groupOpen = true; break; } }
                } else {
                    $groupOpen = $tnvScan($section['active']);
                }
            @endphp
            <div class="tnv-group {{ $isOverview ? 'tnv-overview' : '' }} {{ ($groupOpen || $isOverview) ? 'is-open' : '' }}" data-group="{{ $sectionId }}">
                @unless($isOverview)
                    <button type="button" class="tnv-group-head" onclick="tnvToggleGroup(this)">
                        <span class="tnv-group-label">{{ $section['label'] }}</span>
                        <i class="icofont-simple-down tnv-chev"></i>
                    </button>
                @endunless
                <div class="tnv-group-body"><div>
                    @if($hasSubgroups)
                        {{-- Nested sub-groups (e.g. Catalog → Stays / Activities / …) --}}
                        @foreach($section['subgroups'] as $subId => $sub)
                            @php $subOpen = $tnvScan($sub['active']); @endphp
                            <div class="tnv-group tnv-subgroup {{ $subOpen ? 'is-open' : '' }}" data-group="{{ $sectionId }}:{{ $subId }}">
                                <button type="button" class="tnv-group-head tnv-subhead" onclick="tnvToggleGroup(this)">
                                    <span class="tnv-group-label">{{ $sub['label'] }}</span>
                                    <i class="icofont-simple-down tnv-chev"></i>
                                </button>
                                <div class="tnv-group-body"><div>
                                    @foreach($sub['active'] as $menuItem)
                                        @include('vendor.partials.sb-item')
                                    @endforeach
                                </div></div>
                            </div>
                        @endforeach
                    @else
                        @foreach($section['active'] as $menuItem)
                            @include('vendor.partials.sb-item')
                        @endforeach
                        @if(!empty($section['locked']))
                            <div class="tsoka-sb-locked">
                                <span class="tsoka-sb-locked-label">{{ __('Available with upgrade') }}</span>
                                @foreach($section['locked'] as $lockedItem)
                                    @php $lockedIcon = tsoka_icon($lockedItem['icon'] ?? 'icofont-box'); @endphp
                                    <div class="tsoka-sb-locked-item"><i class="{{ $lockedIcon }}"></i><span>{{ $lockedItem['title'] }}</span><i class="icofont-lock tsoka-sb-lock"></i></div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div></div>
            </div>
        @endforeach
    </nav>

    {{-- Pinned footer: Settings (collapsible) + admin + logout --}}
    <div class="tnv-foot">
        @php
            $settingsItems = $settingsSection['active'] ?? [];
            if (!empty($overflow)) { $settingsItems = array_merge($settingsItems, $overflow); }
            $settingsOpen = false;
            foreach ($settingsItems as $mi) { if (str_contains($mi['class'] ?? '', 'is-active')) { $settingsOpen = true; break; } }
        @endphp
        @if(!empty($settingsItems))
            <div class="tnv-group {{ $settingsOpen ? 'is-open' : '' }}" data-group="settings">
                <button type="button" class="tnv-group-head" onclick="tnvToggleGroup(this)">
                    <span class="tnv-group-label">{{ __('Settings') }}</span>
                    <i class="icofont-simple-down tnv-chev"></i>
                </button>
                <div class="tnv-group-body"><div>
                    @foreach($settingsItems as $menuItem)
                        @php $icon = tsoka_icon($menuItem['icon'] ?? ''); $active = str_contains($menuItem['class'] ?? '', 'is-active'); @endphp
                        <a href="{{ url($menuItem['url']) }}" class="tnv-link {{ $active ? 'is-active' : '' }}" title="{{ strip_tags($menuItem['title']) }}">
                            @if($icon)<i class="{{ $icon }}"></i>@endif
                            <span class="tnv-label">{!! clean($menuItem['title']) !!}</span>
                            @if(!empty($menuItem['is_new']))<span class="tnv-new">{{ __('New') }}</span>@endif
                        </a>
                    @endforeach
                </div></div>
            </div>
        @endif

        @if($adminItem)
            <a href="{{ url($adminItem['url']) }}" class="tnv-link" title="{{ strip_tags($adminItem['title']) }}">
                <i class="{{ tsoka_icon($adminItem['icon'] ?? 'icofont-crown') }}"></i>
                <span class="tnv-label">{{ $adminItem['title'] }}</span>
            </a>
        @endif

        <form id="logout-form-vendor" action="{{ route('logout') }}" method="POST" style="display:none;">{{ csrf_field() }}</form>
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form-vendor').submit();" class="tnv-link" title="{{ __('Log Out') }}">
            <i class="icofont-logout"></i>
            <span class="tnv-label">{{ __('Log Out') }}</span>
        </a>
    </div>

</div>

{{-- Phase 6 — command palette (Cmd/Ctrl+K), AI-inbox polling badge, inactivity timer --}}
@includeIf('vendor.partials.enhancements')
