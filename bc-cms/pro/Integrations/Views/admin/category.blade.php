@extends('layouts.user')

@push('css')
<style>
/* ── Category page ───────────────────────────────────────────── */
.cat-hero {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; flex-wrap: wrap; margin-bottom: 24px;
}
.cat-hero__left {}
.cat-hero__eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #aaa; margin-bottom: 4px; }
.cat-hero__title   { font-size: 24px; font-weight: 800; color: #0a0a0a; letter-spacing: -.03em; margin: 0 0 2px; }
.cat-hero__sub     { font-size: 13px; color: #aaa; }
.cat-hero__back    {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 9px 16px; border-radius: 7px; border: 1.5px solid #e4e4e4;
    font-size: 12px; font-weight: 600; color: #333 !important; text-decoration: none !important;
    background: #fff; transition: border-color .12s; align-self: flex-start;
}
.cat-hero__back:hover { border-color: #0a0a0a; }

/* Filter bar */
.int-filter-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.int-filter-search {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1.5px solid #e4e4e4; border-radius: 8px;
    padding: 0 12px; height: 38px; transition: border-color .15s;
    flex: 1; min-width: 180px; max-width: 280px;
}
.int-filter-search:focus-within { border-color: #0a0a0a; }
.int-filter-search input {
    border: none; outline: none; background: transparent;
    font-size: 13px; color: #333; width: 100%;
}
.int-filter-search input::placeholder { color: #bbb; }
.int-filter-search i { color: #ccc; font-size: 1rem; flex-shrink: 0; }

.int-filter-tabs { display: flex; gap: 4px; flex-wrap: wrap; }
.int-filter-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 100px; border: 1.5px solid #e4e4e4;
    font-size: 11px; font-weight: 600; color: #555; background: #fff;
    cursor: pointer; transition: all .12s; white-space: nowrap;
}
.int-filter-btn:hover { border-color: #0a0a0a; color: #0a0a0a; }
.int-filter-btn.active { background: #0a0a0a; border-color: #0a0a0a; color: #fff; }
.int-filter-btn .int-count {
    background: rgba(255,255,255,.25); color: inherit;
    min-width: 18px; height: 18px; border-radius: 100px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 10px; padding: 0 4px;
}
.int-filter-btn:not(.active) .int-count { background: #f0f0f0; color: #888; }

/* Card grid */
.int-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
@media (max-width: 1100px) { .int-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .int-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Breadcrumb --}}
    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.integrations.hub') }}">Integrations</a></li>
            <li class="breadcrumb-item active">{{ $catMeta['label'] }}</li>
        </ol>
    </nav>

    @php
        $connectedCount = $saved->filter(fn($i) => $i->status === 'connected')->count();
        $totalCount     = count($items);
    @endphp

    {{-- Hero --}}
    <div class="cat-hero">
        <div class="cat-hero__left">
            <div class="cat-hero__eyebrow">{{ $catMeta['label'] }}</div>
            <h1 class="cat-hero__title">{{ $catMeta['label'] }}</h1>
            <div class="cat-hero__sub">
                <span style="color:{{ $connectedCount > 0 ? '#16a34a' : '#aaa' }};font-weight:600">
                    {{ $connectedCount }}
                </span>
                <span style="color:#aaa"> / {{ $totalCount }} connected</span>
            </div>
        </div>
        <a href="{{ route('admin.integrations.hub') }}" class="cat-hero__back">
            <i class="ion ion-ios-arrow-back"></i> All Integrations
        </a>
    </div>

    @include('admin.message')


    {{-- Filter bar --}}
    @php
        $statusCounts = ['all' => $totalCount, 'connected' => 0, 'disconnected' => 0, 'error' => 0];
        foreach ($items as $item) {
            $s = ($saved[$item['slug']] ?? null)?->status ?? 'disconnected';
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
        }
    @endphp
    <div class="int-filter-bar">
        <div class="int-filter-search">
            <i class="ion ion-ios-search"></i>
            <input type="text" id="int-search" placeholder="Search integrations…" autocomplete="off">
        </div>
        <div class="int-filter-tabs">
            <button class="int-filter-btn active" data-filter="all">
                All <span class="int-count">{{ $statusCounts['all'] }}</span>
            </button>
            @if($statusCounts['connected'] > 0)
            <button class="int-filter-btn" data-filter="connected">
                Connected <span class="int-count">{{ $statusCounts['connected'] }}</span>
            </button>
            @endif
            @if($statusCounts['disconnected'] > 0)
            <button class="int-filter-btn" data-filter="disconnected">
                Not Connected <span class="int-count">{{ $statusCounts['disconnected'] }}</span>
            </button>
            @endif
            @if($statusCounts['error'] > 0)
            <button class="int-filter-btn" data-filter="error">
                Error <span class="int-count">{{ $statusCounts['error'] }}</span>
            </button>
            @endif
        </div>
    </div>

    {{-- Integration cards --}}
    @if(count($items) > 0)
    <div class="int-grid" id="int-grid">
        @foreach($items as $item)
        @php
            $itemStatus = ($saved[$item['slug']] ?? null)?->status ?? 'disconnected';
        @endphp
        <div class="int-card-wrap" data-status="{{ $itemStatus }}" data-name="{{ strtolower($item['name']) }}">
            @include('Integrations::admin.partials.integration-card', [
                'item'         => $item,
                'saved'        => $saved[$item['slug']] ?? null,
                'cat'          => $cat,
                'rentals'      => $rentals,
                'roomChannels' => $roomChannels,
            ])
        </div>
        @endforeach
    </div>

    {{-- Empty state after filter --}}
    <div id="int-empty" style="display:none" class="text-center py-5">
        <i class="ion ion-ios-search" style="font-size:2rem;color:#d0d0d0;display:block;margin-bottom:10px"></i>
        <div style="font-size:13px;color:#aaa">No integrations match your filter.</div>
    </div>
    @else
    {{-- Category has no integrations yet --}}
    <div style="text-align:center;padding:72px 24px;background:#fff;border:1px solid #ebebeb;border-radius:10px">
        <i class="{{ $catMeta['icon'] }}"
           style="font-size:2.5rem;color:#e4e4e4;display:block;margin-bottom:16px"></i>
        <div style="font-size:16px;font-weight:700;color:#0a0a0a;margin-bottom:6px">
            {{ $catMeta['label'] }} — Coming Soon
        </div>
        <div style="font-size:13px;color:#aaa;max-width:340px;margin:0 auto;line-height:1.6">
            No integrations have been added to this category yet.
            They will appear here once configured.
        </div>
        <a href="{{ route('admin.integrations.hub') }}"
           style="display:inline-flex;align-items:center;gap:6px;margin-top:20px;padding:9px 18px;
                  border-radius:7px;border:1.5px solid #e0e0e0;font-size:12px;font-weight:600;
                  color:#0a0a0a;text-decoration:none;background:#fff;transition:border-color .12s"
           onmouseover="this.style.borderColor='#0a0a0a'" onmouseout="this.style.borderColor='#e0e0e0'">
            <i class="ion ion-ios-arrow-back"></i> All Integrations
        </a>
    </div>
    @endif

</div>
@endsection

@push('js')
<script>
(function () {
    const btns   = document.querySelectorAll('.int-filter-btn');
    const cards  = document.querySelectorAll('.int-card-wrap');
    const search = document.getElementById('int-search');
    const empty  = document.getElementById('int-empty');

    function applyFilter() {
        const active = document.querySelector('.int-filter-btn.active');
        const f      = active ? active.dataset.filter : 'all';
        const q      = (search ? search.value.toLowerCase() : '');
        let shown    = 0;

        cards.forEach(function (w) {
            const matchFilter = f === 'all' || w.dataset.status === f;
            const matchSearch = !q || (w.dataset.name || '').includes(q);
            const show = matchFilter && matchSearch;
            w.style.display = show ? '' : 'none';
            if (show) shown++;
        });

        empty.style.display = shown === 0 ? '' : 'none';
    }

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            btns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            applyFilter();
        });
    });

    if (search) {
        search.addEventListener('input', applyFilter);
    }
})();
</script>
@endpush
