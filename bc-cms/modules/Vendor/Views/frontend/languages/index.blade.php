@extends('layouts.user')
@section('content')
@php use Modules\Vendor\Services\ServiceTranslations as ST; @endphp
<style>
.lg { max-width: 1120px; }
.lg .lg-sub { color:#6b6b6b; font-size:13px; margin:-2px 0 16px; max-width:720px; }
.lg-tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
.lg-tab { display:inline-flex; align-items:center; gap:9px; padding:8px 14px; border:1px solid #e3e3e3; border-radius:999px; background:#fff; color:#0a0a0a !important; font-size:13px; font-weight:600; text-decoration:none !important; }
.lg-tab:hover { border-color:#0a0a0a; }
.lg-tab.is-on { background:#0a0a0a; color:#fff !important; border-color:#0a0a0a; }
.lg-tab small { font-weight:500; opacity:.7; }
.lg-card { border:1px solid #e6e6e6; border-radius:10px; background:#fff; padding:16px 18px; margin-bottom:14px; }
.lg-head { display:flex; flex-wrap:wrap; gap:16px; justify-content:space-between; align-items:center; }
.lg-big { font-size:22px; font-weight:700; letter-spacing:-.02em; }
.lg-bar { height:8px; background:#eee; border-radius:6px; overflow:hidden; margin:8px 0 4px; display:flex; }
.lg-bar i { display:block; height:100%; }
.lg-bar .d { background:#0a0a0a; } .lg-bar .p { background:#E0A23B; }
.lg-legend { font-size:12px; color:#6b6b6b; }
.lg-types { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.lg-type { display:block; min-width:130px; padding:9px 12px; border:1px solid #e6e6e6; border-radius:8px; text-decoration:none !important; color:#0a0a0a !important; font-size:12px; background:#fff; }
.lg-type b { display:block; font-size:13px; }
.lg-type span { color:#6b6b6b; }
.lg-type.is-on { border-color:#0a0a0a; box-shadow:inset 0 0 0 1px #0a0a0a; }
.lg-filter { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:12px; }
.lg-filter input[type=text] { max-width:260px; }
.lg-btn { display:inline-block; padding:9px 16px; border-radius:7px; font-size:13px; font-weight:600; border:1.5px solid #0a0a0a; background:#fff; color:#0a0a0a !important; cursor:pointer; text-decoration:none !important; }
.lg-btn.is-primary { background:#0a0a0a; color:#fff !important; }
.lg-btn.is-small { padding:4px 9px; font-size:11px; }
.lg-svc { border:1px solid #e6e6e6; border-radius:10px; background:#fff; margin-bottom:12px; overflow:hidden; }
.lg-svc-head { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:11px 16px; background:#fafafa; border-bottom:1px solid #eee; }
.lg-svc-head b { font-size:14px; }
.lg-pill { display:inline-block; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; padding:3px 8px; border-radius:20px; border:1px solid #d9d9d9; color:#6b6b6b; }
.lg-pill.done { border-color:#0a0a0a; color:#0a0a0a; }
.lg-pill.partial { border-color:#E0A23B; color:#8a5d10; background:#fffaf0; }
.lg-pill.missing { border-color:#e11d48; color:#e11d48; background:#fff1f2; }
.lg-field { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:12px 16px; border-top:1px solid #f0f0f0; }
.lg-field:first-of-type { border-top:0; }
.lg-list-title { padding:10px 16px 4px; background:#fbfbfb; border-top:1px solid #f0f0f0; font-size:12px; letter-spacing:.06em; text-transform:uppercase; color:#555; display:flex; justify-content:space-between; }
.lg-list-title span { color:#8a8a8a; font-weight:600; }
.lg-label { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#8a8a8a; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center; }
.lg-src { background:#f6f6f6; border-radius:8px; padding:9px 12px; font-size:13px; white-space:pre-wrap; color:#333; max-height:220px; overflow:auto; }
.lg-field textarea { width:100%; border:1px solid #d9d9d9; border-radius:8px; padding:9px 12px; font-size:13px; line-height:1.5; resize:vertical; min-height:42px; background:#fff; }
.lg-field textarea:focus { outline:none; border-color:#0a0a0a; }
.lg-field.is-done textarea { border-color:#cfcfcf; }
.lg-save { position:sticky; bottom:0; background:rgba(245,245,245,.95); padding:12px 0; display:flex; gap:12px; align-items:center; justify-content:space-between; }
.lg-add { display:none; gap:8px; align-items:center; flex-wrap:wrap; margin:-6px 0 14px; }
.lg-add.is-open { display:flex; }
.lg-add select { max-width:260px; }
.lg-add small { color:#6b6b6b; font-size:12px; }
.lg-tab.is-add { border-style:dashed; color:#555 !important; }
.lg-ai { border:1px solid #e6e6e6; border-radius:10px; background:#fff; padding:14px 18px; margin-bottom:14px; }
.lg-ai-row { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; }
.lg-ai b { font-size:14px; } .lg-ai p { margin:2px 0 0; font-size:12px; color:#6b6b6b; }
.lg-ai-run { display:none; margin-top:12px; }
.lg-ai.is-running .lg-ai-run { display:block; }
.lg-ai.is-running .lg-ai-start { display:none; }
.lg-spin { width:18px; height:18px; border:2px solid #ddd; border-top-color:#0a0a0a; border-radius:50%; animation:lgspin .8s linear infinite; flex-shrink:0; }
@keyframes lgspin { to { transform:rotate(360deg); } }
.lg-ai-bar { height:8px; background:#eee; border-radius:6px; overflow:hidden; margin:8px 0 6px; }
.lg-ai-bar i { display:block; height:100%; width:0; background:#0a0a0a; transition:width .4s; }
.lg-ai-msg { font-size:12px; color:#555; display:flex; gap:10px; align-items:center; }
.lg-ai-err { color:#e11d48; font-size:13px; margin-top:8px; }
.lg-note { background:#fffaf0; border:1px solid #f0d9a8; border-radius:8px; padding:9px 14px; font-size:13px; margin-bottom:14px; }
.lg-empty { text-align:center; padding:40px 20px; color:#6b6b6b; }
@media (max-width: 767px) { .lg-field { grid-template-columns:1fr; } }
</style>
<div class="container-fluid lg">
    <h2 class="title-bar">{{ __('Languages') }}</h2>
    @include('admin.message')
    <p class="lg-sub">{{ __('Write your services in more languages so guests read them in their own. Your words in :d are on the left; write the translation on the right. Anything you leave empty stays in :d.', ['d' => $defaultName]) }}</p>

    @if(!$enabled)
        <div class="lg-card lg-empty"><b>{{ __('Languages are switched off for this platform.') }}</b><br>{{ __('The platform team can switch them on under Settings.') }}</div>
    @elseif($languages->isEmpty())
        <div class="lg-card lg-empty"><b>{{ __('No other language is switched on yet.') }}</b><br>{{ __('Ask the platform team to add the languages your guests read.') }}</div>
    @elseif(empty($types))
        <div class="lg-card lg-empty"><b>{{ __('You have no kind of service to translate yet.') }}</b><br>{{ __('Choose what you operate under Plan & billing, then add a service.') }}</div>
    @else
        {{-- Language tabs --}}
        <div class="lg-tabs">
            @foreach($languages as $l)
                <a class="lg-tab {{ $l->locale === $language->locale ? 'is-on' : '' }}" href="{{ route('vendor.languages.index', ['lang' => $l->locale]) }}">{{ $l->name }}</a>
            @endforeach
            <a class="lg-tab is-add" href="#" onclick="document.getElementById('lgAdd').classList.toggle('is-open');return false;">+ {{ __('Add a language') }}</a>
        </div>
        @include('Vendor::frontend.languages.add')

        @if(request()->query('ai') !== null)
            <div class="lg-note"><b>{{ __('AI translation finished.') }}</b> {{ trans_choice(':n piece of text was translated.|:n pieces of text were translated.', (int) request()->query('ai'), ['n' => (int) request()->query('ai')]) }} {{ __('Please read them over: you can edit anything below and save.') }}</div>
        @endif

        {{-- Progress in this language --}}
        @php
            $tot = collect($summary)->sum('total'); $done = collect($summary)->sum('done'); $part = collect($summary)->sum('partial');
        @endphp
        <div class="lg-card">
            <div class="lg-head">
                <div><div class="lg-big">{{ $language->name }}</div>
                    <div class="lg-legend">@if($tot){{ __(':d of :t services fully translated', ['d' => $done, 't' => $tot]) }}@if($part) · {{ __(':p in progress', ['p' => $part]) }}@endif @else{{ __('You have no services yet.') }}@endif</div></div>
                <div class="lg-big" style="font-size:28px;">{{ $tot ? round($done / $tot * 100) : 0 }}%</div>
            </div>
            <div class="lg-bar"><i class="d" style="width:{{ $tot ? $done / $tot * 100 : 0 }}%"></i><i class="p" style="width:{{ $tot ? $part / $tot * 100 : 0 }}%"></i></div>
            <div class="lg-types">
                @foreach($types as $k => $t)
                    @php $c = $summary[$k] ?? ['done' => 0, 'total' => 0, 'partial' => 0, 'missing' => 0]; @endphp
                    <a class="lg-type {{ $k === $type ? 'is-on' : '' }}" href="{{ route('vendor.languages.index', ['lang' => $language->locale, 'type' => $k]) }}">
                        <b>{{ __($t['label']) }}</b><span>{{ $c['done'] }} / {{ $c['total'] }} {{ __('done') }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @include('Vendor::frontend.languages.ai')

        {{-- Filter --}}
        <form class="lg-filter" method="get" action="{{ route('vendor.languages.index') }}">
            <input type="hidden" name="lang" value="{{ $language->locale }}"><input type="hidden" name="type" value="{{ $type }}">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Search :t', ['t' => mb_strtolower(__($types[$type]['label']))]) }}">
            <label style="margin:0;"><input type="checkbox" name="missing" value="1" {{ $only ? 'checked' : '' }} onchange="this.form.submit()"> {{ __('Only ones still to translate') }}</label>
            <button class="lg-btn is-small" type="submit">{{ __('Search') }}</button>
        </form>

        @if($items->isEmpty())
            <div class="lg-card lg-empty">{{ $only ? __('Everything here is translated.') : __('Nothing here yet.') }}</div>
        @else
        <form method="post" action="{{ route('vendor.languages.store') }}">
            @csrf
            <input type="hidden" name="lang" value="{{ $language->locale }}"><input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="back" value="{{ request()->fullUrl() }}">
            @foreach($items as $m)
                @php $row = $rows->get($m->id); $pg = $progress[$m->id]; @endphp
                <div class="lg-svc">
                    <div class="lg-svc-head">
                        <b>{{ $m->title }}</b>
                        <span class="lg-pill {{ $pg['state'] }}">{{ ['done' => __('Translated'), 'partial' => __('In progress'), 'missing' => __('To translate')][$pg['state']] }}</span>
                    </div>
                    @foreach($types[$type]['fields'] as $f)
                        @php $meta = ST::FIELDS[$f]; @endphp
                        @if($meta['kind'] === 'list')
                            @php $lr = ST::listRows($m, $row, $f, $marks[$m->id] ?? []); $lrDone = collect($lr)->where('done', true)->count(); @endphp
                            @continue(!$lr)
                            <div class="lg-list-title"><b>{{ __($meta['label']) }}</b> <span>{{ $lrDone }} / {{ count($lr) }} {{ __('done') }}</span></div>
                            @foreach($lr as $e)
                                @php $long = mb_strlen($e['src']) > 90 || str_contains($e['src'], "\n"); @endphp
                                <div class="lg-field {{ $e['done'] ? 'is-done' : '' }}">
                                    <div>
                                        <div class="lg-label"><span>{{ $e['label'] }} · {{ $defaultName }}</span></div>
                                        <div class="lg-src">{{ $e['src'] }}</div>
                                    </div>
                                    <div>
                                        <div class="lg-label"><span>{{ $e['label'] }} · {{ $language->name }}</span>
                                            <a href="#" class="lg-btn is-small" onclick="var t=this.closest('.lg-field').querySelector('textarea');t.value=this.closest('.lg-field').querySelector('.lg-src').innerText;t.focus();return false;">{{ __('Copy') }}</a></div>
                                        <textarea name="t[{{ $m->id }}][{{ $f }}][{{ $e['path'] }}]" rows="{{ $long ? min(8, max(2, (int) ceil(mb_strlen($e['src']) / 70))) : 1 }}" placeholder="{{ __('Write it in :l', ['l' => $language->name]) }}">{{ $e['tr'] }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                            @continue
                        @endif
                        @php
                            $src = $m->getAttribute($f);
                            $srcText = $meta['kind'] === 'html' ? ST::toText($src) : trim((string) $src);
                            if ($srcText === '') continue;
                            $cur = $row ? $row->getAttribute($f) : null;
                            $curText = $meta['kind'] === 'html' ? ST::toText($cur) : trim((string) $cur);
                            $isDone = ($pg['fields'][$f] ?? 0) >= 1;
                            $long = mb_strlen($srcText) > 90 || str_contains($srcText, "\n");
                        @endphp
                        <div class="lg-field {{ $isDone ? 'is-done' : '' }}">
                            <div>
                                <div class="lg-label"><span>{{ __($meta['label']) }} · {{ $defaultName }}</span></div>
                                <div class="lg-src">{{ $srcText }}</div>
                            </div>
                            <div>
                                <div class="lg-label"><span>{{ __($meta['label']) }} · {{ $language->name }}</span>
                                    <a href="#" class="lg-btn is-small" onclick="var t=this.closest('.lg-field').querySelector('textarea');t.value=this.closest('.lg-field').querySelector('.lg-src').innerText;t.focus();return false;">{{ __('Copy') }}</a></div>
                                <textarea name="t[{{ $m->id }}][{{ $f }}]" rows="{{ $long ? min(10, max(3, (int) ceil(mb_strlen($srcText) / 70))) : 1 }}" placeholder="{{ __('Write it in :l', ['l' => $language->name]) }}">{{ $isDone ? ($curText !== '' ? $curText : $srcText) : '' }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div class="lg-save">
                <span class="lg-legend">{{ __('Page :p of :n', ['p' => $items->currentPage(), 'n' => $items->lastPage()]) }}</span>
                <button type="submit" class="lg-btn is-primary">{{ __('Save these translations') }}</button>
            </div>
            {{ $items->links() }}
        </form>
        @endif
    @endif
</div>
@endsection
