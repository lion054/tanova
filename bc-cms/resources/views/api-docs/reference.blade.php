{{-- The API reference: one section at a time (a guide, the errors, or one area of endpoints). Built from the spec. --}}
@php
    $base = rtrim($base ?? '/api/v/docs', '/');
    $link = fn (string $s) => $base . (str_contains($base, '?') ? '&' : '?') . 'section=' . $s;
    $methodClass = ['GET' => 'get', 'POST' => 'post', 'PUT' => 'put', 'PATCH' => 'patch', 'DELETE' => 'delete'];
@endphp
<style>
.apidoc{--ad-ink:#111;--ad-mut:#5b6068;--ad-line:#e4e6ea;--ad-soft:#f6f7f9;--ad-acc:#111;display:grid;grid-template-columns:270px minmax(0,1fr);gap:28px;align-items:start;color:var(--ad-ink);font-size:14.5px;line-height:1.55}
.apidoc *{box-sizing:border-box}
.apidoc a{color:inherit}
.apidoc__nav{position:sticky;top:12px;max-height:calc(100vh - 24px);overflow:auto;border:1px solid var(--ad-line);border-radius:10px;padding:12px;background:#fff}
.apidoc__nav h6{margin:14px 6px 4px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ad-mut)}
.apidoc__nav a{display:flex;justify-content:space-between;gap:8px;padding:5px 8px;border-radius:6px;text-decoration:none;font-size:13.5px}
.apidoc__nav a:hover{background:var(--ad-soft)}
.apidoc__nav a.on{background:#111;color:#fff}
.apidoc__nav a small{opacity:.6}
.apidoc__search{width:100%;padding:8px 10px;border:1px solid var(--ad-line);border-radius:8px;font-size:13.5px}
.apidoc__hits{margin-top:6px}
.apidoc__hits a{display:block;padding:4px 6px}
.apidoc__hits .m{display:inline-block;min-width:44px;font-weight:700;font-size:10.5px}
.apidoc__main{min-width:0}
.apidoc h1.t{font-size:26px;margin:0 0 4px}
.apidoc h2{font-size:20px;margin:26px 0 8px}
.apidoc h3{font-size:16px;margin:20px 0 6px}
.apidoc p{margin:0 0 10px}
.apidoc code{background:var(--ad-soft);padding:1px 5px;border-radius:4px;font-size:.9em;word-break:break-word}
.apidoc pre{background:#0f1115;color:#e8eaee;padding:12px 14px;border-radius:8px;overflow:auto;font-size:12.5px;line-height:1.5;margin:8px 0}
.apidoc pre code{background:none;padding:0;color:inherit;font-size:inherit;word-break:normal}
.apidoc table{width:100%;border-collapse:collapse;margin:8px 0 14px;font-size:13.5px}
.apidoc th,.apidoc td{border-bottom:1px solid var(--ad-line);padding:6px 8px;text-align:left;vertical-align:top}
.apidoc th{font-size:11.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--ad-mut)}
.apidoc .tbl{overflow-x:auto}
.op{border:1px solid var(--ad-line);border-radius:10px;margin:14px 0;background:#fff}
.op__h{display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;padding:12px 14px;cursor:pointer}
.op__h:hover{background:var(--ad-soft);border-radius:10px}
.op__m{font-weight:800;font-size:11.5px;padding:3px 8px;border-radius:5px;min-width:58px;text-align:center;color:#fff}
.m-get{background:#127a4b}.m-post{background:#1b5fc4}.m-put{background:#a86400}.m-patch{background:#7a3fb5}.m-delete{background:#b3261e}
.op__p{font-family:ui-monospace,Menlo,monospace;font-size:13.5px;word-break:break-all}
.op__p b{color:#a4470b;font-weight:600}
.op__s{color:var(--ad-mut);flex:1 1 200px}
.op__b{display:none;padding:2px 16px 16px;border-top:1px solid var(--ad-line)}
.op.open .op__b{display:block}
.pill{display:inline-block;font-size:11.5px;border:1px solid var(--ad-line);border-radius:999px;padding:1px 9px;background:#fff;color:var(--ad-mut);white-space:nowrap}
.pill--scope{border-color:#111;color:#111}
.note{border-left:3px solid #a86400;background:var(--ad-soft);padding:8px 12px;border-radius:0 6px 6px 0;margin:8px 0;font-size:13.5px}
.tabs{display:flex;gap:4px;margin-top:10px;flex-wrap:wrap}
.tabs button{border:1px solid var(--ad-line);background:#fff;border-radius:6px 6px 0 0;padding:4px 12px;font-size:12.5px;cursor:pointer}
.tabs button.on{background:#0f1115;color:#fff;border-color:#0f1115}
.pane{display:none}.pane.on{display:block}
.pane pre{margin-top:0;border-top-left-radius:0}
.req{color:#b3261e;font-weight:700}
.ty{color:var(--ad-mut);font-size:12.5px}
.copy{float:right;margin:-2px 0 0 8px;font-size:11.5px;border:1px solid #444;background:#1c1f26;color:#ddd;border-radius:5px;padding:1px 8px;cursor:pointer}
.try{margin-top:14px;border:1px dashed var(--ad-line);border-radius:8px;padding:10px 12px;background:var(--ad-soft)}
.try label{display:block;font-size:12px;color:var(--ad-mut);margin:6px 0 2px}
.try input,.try textarea{width:100%;padding:6px 8px;border:1px solid var(--ad-line);border-radius:6px;font-family:ui-monospace,Menlo,monospace;font-size:12.5px;background:#fff}
.try textarea{min-height:110px}
.try button.go{margin-top:8px;background:#111;color:#fff;border:0;border-radius:6px;padding:6px 16px;cursor:pointer}
.try .out{margin-top:8px}
.sec-h{display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap;border-bottom:1px solid var(--ad-line);padding-bottom:10px;margin-bottom:8px}
@media(max-width:900px){.apidoc{grid-template-columns:1fr}.apidoc__nav{position:static;max-height:none}}
</style>

<div class="apidoc" id="apidoc" data-base="{{ $apiBase }}">
    <nav class="apidoc__nav">
        <input type="search" class="apidoc__search" id="ad-search" placeholder="{{ __('Search endpoints…') }}" autocomplete="off">
        <div class="apidoc__hits" id="ad-hits"></div>
        <h6>{{ __('Guides') }}</h6>
        @foreach($doc['guides'] as $g)
            <a href="{{ $link($g['slug']) }}" class="{{ $section === $g['slug'] ? 'on' : '' }}">{{ $g['title'] }}</a>
        @endforeach
        <h6>{{ __('Reference') }}</h6>
        @foreach($doc['tags'] as $t)
            <a href="{{ $link($t['slug']) }}" class="{{ $section === $t['slug'] ? 'on' : '' }}">{{ $t['name'] }} <small>{{ count($t['ops']) }}</small></a>
        @endforeach
        <h6>{{ __('Also') }}</h6>
        <a href="{{ $link('errors') }}" class="{{ $section === 'errors' ? 'on' : '' }}">{{ __('Error codes') }}</a>
        <a href="{{ $apiBase }}/swagger" target="_blank" rel="noopener">Swagger UI</a>
        <a href="{{ $apiBase }}/openapi.json?download=1">OpenAPI 3.1 <small>download</small></a>
        <a href="{{ $apiBase }}/postman.json" target="_blank" rel="noopener">Postman <small>json</small></a>
    </nav>

    <main class="apidoc__main">
    @if($current['kind'] === 'guide')
        <div class="sec-h"><h1 class="t">{{ $current['guide']['title'] }}</h1></div>
        {!! $current['guide']['html'] !!}
        @if($current['guide']['slug'] === 'guide-keys-scopes-and-modes')
            <h2>{{ __('Scope areas, as the API knows them') }}</h2>
            <div class="tbl"><table><tr><th>{{ __('Area') }}</th><th>{{ __('Covers') }}</th></tr>
            @foreach($doc['scopes'] as $area => $what)<tr><td><code>{{ $area }}</code></td><td>{{ $what }}</td></tr>@endforeach
            </table></div>
        @endif
    @elseif($current['kind'] === 'errors')
        <div class="sec-h"><h1 class="t">{{ __('Error codes') }}</h1></div>
        <p>{{ __('Every error is { "error": { "code", "message" } }. Branch on the code. The code is listed here with the number of endpoints that can return it; each endpoint page names its own.') }}</p>
        <div class="tbl"><table><tr><th>{{ __('Status') }}</th><th>{{ __('Code') }}</th><th>{{ __('When') }}</th><th>{{ __('Endpoints') }}</th></tr>
        @foreach($doc['errors'] as $e)<tr><td>{{ $e['status'] }}</td><td><code>{{ $e['code'] }}</code></td><td>{!! $e['when'] !!}</td><td>{{ $e['count'] }}</td></tr>@endforeach
        </table></div>
    @else
        @php $t = $current['tag']; @endphp
        <div class="sec-h"><h1 class="t">{{ $t['name'] }}</h1><span class="pill">{{ count($t['ops']) }} {{ __('endpoints') }}</span></div>
        {!! $t['description'] !!}
        @foreach($t['ops'] as $op)
        <section class="op" id="{{ $op['id'] }}" data-id="{{ $op['id'] }}">
            <div class="op__h" data-toggle>
                <span class="op__m m-{{ $methodClass[$op['method']] ?? 'get' }}">{{ $op['method'] }}</span>
                <span class="op__p">{!! preg_replace('/\{(\w+)\}/', '<b>{$1}</b>', e($op['path'])) !!}</span>
                <span class="op__s">{{ $op['summary'] }}</span>
                @if($op['scope'])<span class="pill pill--scope">{{ $op['scope'] }}</span>@else<span class="pill">{{ $op['guest'] ? __('guest token') : __('any key') }}</span>@endif
            </div>
            <div class="op__b">
                {!! $op['description'] !!}
                @if($op['legacy'])<div class="note"><strong>{{ __('Older response format.') }}</strong> {{ $op['legacy'] }}</div>@endif
                <p>
                    @if($op['scope'])<span class="pill pill--scope">{{ __('Key scope') }}: {{ $op['scope'] }}</span>@endif
                    @if($op['write'])<span class="pill">{{ __('Send an Idempotency-Key') }}</span>@endif
                    @if($op['guest'])<span class="pill">{{ __('Also needs') }} <code>X-Customer-Token</code></span>@endif
                </p>

                @if($op['params'])
                <h3>{{ __('Parameters') }}</h3>
                <div class="tbl"><table><tr><th>{{ __('Name') }}</th><th>{{ __('In') }}</th><th>{{ __('Type') }}</th><th>{{ __('Description') }}</th></tr>
                @foreach($op['params'] as $p)
                    <tr><td><code>{{ $p['name'] }}</code>@if($p['required']) <span class="req">*</span>@endif</td><td>{{ $p['in'] }}</td>
                    <td class="ty">{{ $p['type'] }}@if($p['default'] !== null)<br>{{ __('default') }} <code>{{ is_scalar($p['default']) ? $p['default'] : json_encode($p['default']) }}</code>@endif</td>
                    <td>{!! $p['desc'] !!}@if($p['enum'])<br>@foreach($p['enum'] as $v)<code>{{ $v }}</code> @endforeach @endif</td></tr>
                @endforeach
                </table></div>
                @endif

                @if($op['body'])
                <h3>{{ __('Request body') }} <span class="ty">application/json{{ $op['body']['required'] ? '' : ' · '.__('optional') }}</span></h3>
                @include('api-docs.fields', ['rows' => $op['body']['rows']])
                <pre><code>{{ $op['body']['example'] }}</code></pre>
                @endif

                @foreach($op['responses'] as $r)
                <h3>{{ __('Response') }} <span class="pill">{{ $r['status'] }}</span> <span class="ty">{{ strip_tags($r['desc']) }}</span></h3>
                @if($r['rows'])@include('api-docs.fields', ['rows' => $r['rows']])@endif
                @if($r['example'] !== null)<pre><code>{{ $r['example'] }}</code></pre>@endif
                @endforeach

                @if($op['errors'])
                <h3>{{ __('Errors') }}</h3>
                <div class="tbl"><table><tr><th>{{ __('Status') }}</th><th>{{ __('Code') }}</th><th>{{ __('When') }}</th></tr>
                @foreach($op['errors'] as $e)<tr><td>{{ $e['status'] }}</td><td><code>{{ $e['code'] }}</code></td><td>{!! $e['when'] !!}</td></tr>@endforeach
                </table></div>
                @endif

                <h3>{{ __('Examples') }}</h3>
                <div class="tabs">@foreach($op['samples'] as $i => $s)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-tab="{{ $op['id'] }}-{{ $i }}">{{ $s['label'] }}</button>@endforeach</div>
                @foreach($op['samples'] as $i => $s)
                <div class="pane {{ $i === 0 ? 'on' : '' }}" id="{{ $op['id'] }}-{{ $i }}"><pre><button type="button" class="copy">{{ __('Copy') }}</button><code>{{ $s['source'] }}</code></pre></div>
                @endforeach

                @php $tryJson = json_encode(['method' => $op['method'], 'path' => $op['path'], 'params' => array_values(array_map(fn ($p) => ['name' => $p['name'], 'in' => $p['in'], 'example' => $p['example'] ?? ($p['enum'][0] ?? null)], $op['params'])), 'body' => $op['body']['example'] ?? null, 'guest' => $op['guest'], 'write' => $op['write']]); @endphp
                <div class="try" data-try="{{ $tryJson }}">
                    <strong>{{ __('Try it') }}</strong> <span class="ty">{{ __('with a test key. It reads and writes your real data but sends nothing to guests.') }}</span>
                    <div class="try__form"></div>
                </div>
            </div>
        </section>
        @endforeach
    @endif
    </main>
</div>

<script>
window.__apidocIndex = @json($index);
</script>
<script>
(function () {
    var root = document.getElementById('apidoc'); if (!root) return;
    var apiBase = root.getAttribute('data-base');
    var sectionLink = function (s) { return @json($link('__S__')).replace('__S__', s); };
    var store = function (k, v) { try { if (v === undefined) return localStorage.getItem(k) || ''; localStorage.setItem(k, v); } catch (e) { return ''; } };

    // Open an endpoint from its header, or from the address (#operationId).
    root.querySelectorAll('[data-toggle]').forEach(function (h) { h.addEventListener('click', function () { h.parentNode.classList.toggle('open'); }); });
    var open = function () { var id = location.hash.slice(1); var el = id && document.getElementById(id); if (el && el.classList.contains('op')) { el.classList.add('open'); el.scrollIntoView(); } };
    window.addEventListener('hashchange', open); open();

    // Language tabs and copy.
    root.querySelectorAll('.tabs').forEach(function (bar) {
        bar.querySelectorAll('button').forEach(function (b) {
            b.addEventListener('click', function () {
                bar.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); });
                b.classList.add('on');
                var panes = []; var n = bar.nextElementSibling; while (n && n.classList.contains('pane')) { panes.push(n); n = n.nextElementSibling; }
                panes.forEach(function (p) { p.classList.toggle('on', p.id === b.getAttribute('data-tab')); });
            });
        });
    });
    root.querySelectorAll('.copy').forEach(function (b) {
        b.addEventListener('click', function () {
            var code = b.parentNode.querySelector('code').innerText;
            (navigator.clipboard ? navigator.clipboard.writeText(code) : Promise.reject()).then(function () { b.textContent = '✓'; setTimeout(function () { b.textContent = @json(__('Copy')); }, 1200); }, function () {});
        });
    });

    // Search over every endpoint, in every section.
    var box = document.getElementById('ad-search'), hits = document.getElementById('ad-hits');
    box.addEventListener('input', function () {
        var words = box.value.toLowerCase().split(/\s+/).filter(Boolean); hits.innerHTML = '';
        if (!words.length) return;
        window.__apidocIndex.filter(function (o) { var hay = (o.method + ' ' + o.path + ' ' + o.summary + ' ' + o.tag).toLowerCase(); return words.every(function (w) { return hay.indexOf(w) >= 0; }); })
            .slice(0, 25).forEach(function (o) {
                var a = document.createElement('a'); a.href = sectionLink(o.slug) + '#' + o.id;
                a.innerHTML = '<span class="m">' + o.method + '</span> ' + o.path.replace(/</g, '&lt;'); a.title = o.summary; hits.appendChild(a);
            });
    });

    // Try it: test keys only.
    root.querySelectorAll('[data-try]').forEach(function (box) {
        var spec = JSON.parse(box.getAttribute('data-try')), form = box.querySelector('.try__form'), fields = {};
        var mk = function (label, val, ta) { var l = document.createElement('label'); l.textContent = label; var i = document.createElement(ta ? 'textarea' : 'input'); i.value = val == null ? '' : val; form.appendChild(l); form.appendChild(i); return i; };
        var key = mk('API key (sk_test_… or pk_test_…)', store('apidoc_key')); key.type = 'password'; key.placeholder = 'sk_test_…';
        var guest = spec.guest ? mk('X-Customer-Token', store('apidoc_guest')) : null;
        spec.params.forEach(function (p) { fields[p.in + ':' + p.name] = mk(p.name + ' (' + p.in + ')', p.in === 'path' ? (p.example == null ? '' : p.example) : ''); });
        var body = spec.body ? mk('Body (JSON)', spec.body, true) : null;
        var go = document.createElement('button'); go.type = 'button'; go.className = 'go'; go.textContent = @json(__('Send')); form.appendChild(go);
        var out = document.createElement('div'); out.className = 'out'; form.appendChild(out);
        go.addEventListener('click', function () {
            var k = key.value.trim();
            if (!/^(sk|pk)_test_/.test(k)) { out.innerHTML = '<div class="note">' + @json(__('Use a test key here (sk_test_… or pk_test_…). Create one under Settings → API keys.')) + '</div>'; return; }
            store('apidoc_key', k); if (guest) store('apidoc_guest', guest.value.trim());
            var path = spec.path, qs = [];
            spec.params.forEach(function (p) { var v = fields[p.in + ':' + p.name].value.trim(); if (p.in === 'path') path = path.replace('{' + p.name + '}', encodeURIComponent(v)); else if (v !== '') qs.push(encodeURIComponent(p.name) + '=' + encodeURIComponent(v)); });
            var headers = { 'Authorization': 'Bearer ' + k, 'Accept': 'application/json' };
            if (guest && guest.value.trim()) headers['X-Customer-Token'] = guest.value.trim();
            if (spec.write) headers['Idempotency-Key'] = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random();
            var init = { method: spec.method, headers: headers };
            if (body && spec.method !== 'GET' && body.value.trim() !== '') { headers['Content-Type'] = 'application/json'; init.body = body.value; }
            var t0 = performance.now(); out.textContent = '…';
            fetch(apiBase + path + (qs.length ? '?' + qs.join('&') : ''), init).then(function (r) {
                return r.text().then(function (txt) {
                    var pretty = txt; try { pretty = JSON.stringify(JSON.parse(txt), null, 2); } catch (e) {}
                    var pre = document.createElement('pre'); var code = document.createElement('code'); code.textContent = r.status + ' ' + r.statusText + ' · ' + Math.round(performance.now() - t0) + ' ms · ' + (r.headers.get('X-Tsoka-Mode') || '') + '\n\n' + pretty; pre.appendChild(code);
                    out.innerHTML = ''; out.appendChild(pre);
                });
            }).catch(function (e) { out.textContent = 'Request failed: ' + e; });
        });
    });
})();
</script>
