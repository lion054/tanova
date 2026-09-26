@php $aiOn = \Modules\Vendor\Services\AiTranslator::configured(); @endphp
<div class="lg-ai" id="lgAi">
    <div class="lg-ai-row lg-ai-start">
        <div><b>{{ __('Translate with AI') }}</b>
            <p>{{ $aiOn ? __('Let AI write the :l for everything that is not translated yet. It never replaces words you wrote yourself, and it can take a few minutes for a big catalogue: you can watch the progress and stop at any time.', ['l' => $language->name]) : __('Automatic translation is not set up on this platform yet.') }}</p></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="lg-btn" data-scope="{{ $type }}" {{ $aiOn ? '' : 'disabled' }}>{{ __('Translate all :t', ['t' => mb_strtolower(__($types[$type]['label']))]) }}</button>
            <button type="button" class="lg-btn is-primary" data-scope="all" {{ $aiOn ? '' : 'disabled' }}>{{ __('Translate everything into :l', ['l' => $language->name]) }}</button>
        </div>
    </div>
    <div class="lg-ai-run">
        <div class="lg-ai-msg"><span class="lg-spin" id="lgSpin"></span><b id="lgAiTitle">{{ __('Getting ready...') }}</b></div>
        <div class="lg-ai-bar"><i id="lgAiBar"></i></div>
        <div class="lg-ai-msg"><span id="lgAiNow"></span><span style="margin-left:auto;"><button type="button" class="lg-btn is-small" id="lgAiStop">{{ __('Stop') }}</button></span></div>
        <div class="lg-ai-err" id="lgAiErr" style="display:none;"></div>
    </div>
</div>
<script>
(function () {
    var box = document.getElementById('lgAi'); if (!box) return;
    var token = document.querySelector('input[name=_token]').value, lang = @json($language->locale), stop = false;
    var urls = { queue: @json(route('vendor.languages.ai.queue')), run: @json(route('vendor.languages.ai.run')) };
    var T = { ready: @json(__('Getting ready...')), of: @json(__('of')), nothing: @json(__('Everything is already translated.')), stopped: @json(__('Stopped. What was translated is saved.')), working: @json(__('Translating')), fail: @json(__('Something went wrong.')) };
    function post(url, body) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(body) })
            .then(function (r) { return r.json().catch(function () { return { error: T.fail }; }).then(function (j) { if (!r.ok) { throw new Error(j.error || T.fail); } return j; }); });
    }
    function el(id) { return document.getElementById(id); }
    function fail(m) { el('lgAiErr').style.display = 'block'; el('lgAiErr').textContent = m; el('lgSpin').style.display = 'none'; el('lgAiTitle').textContent = T.fail; }
    async function start(scope) {
        stop = false; box.classList.add('is-running'); el('lgAiErr').style.display = 'none'; el('lgSpin').style.display = ''; el('lgAiTitle').textContent = T.ready; el('lgAiBar').style.width = '0';
        var written = 0, done = 0;
        try {
            var q = await post(urls.queue, { lang: lang, scope: scope });
            if (!q.total) { el('lgSpin').style.display = 'none'; el('lgAiTitle').textContent = T.nothing; return; }
            for (var i = 0; i < q.items.length; i += 4) {
                if (stop) { el('lgSpin').style.display = 'none'; el('lgAiTitle').textContent = T.stopped; el('lgAiNow').textContent = done + ' ' + T.of + ' ' + q.total; return; }
                var batch = q.items.slice(i, i + 4);
                el('lgAiTitle').textContent = T.working + ' ' + Math.min(i + 1, q.total) + '-' + Math.min(i + batch.length, q.total) + ' ' + T.of + ' ' + q.total;
                el('lgAiNow').textContent = batch.map(function (b) { return b.title; }).join(' · ');
                var r = await post(urls.run, { lang: lang, items: batch.map(function (b) { return { type: b.type, id: b.id }; }) });
                done += r.done; written += r.written;
                el('lgAiBar').style.width = Math.round(Math.min(i + batch.length, q.items.length) / q.total * 100) + '%';
            }
            var u = new URL(location.href); u.searchParams.set('lang', lang); u.searchParams.set('ai', written); location.href = u.toString();
        } catch (e) { fail(e.message + ' ' + (done ? '(' + done + ' ' + T.of + ' ' + (q ? q.total : '') + ')' : '')); }
    }
    box.querySelectorAll('[data-scope]').forEach(function (b) { b.addEventListener('click', function () { start(b.dataset.scope); }); });
    el('lgAiStop').addEventListener('click', function () { stop = true; this.disabled = true; });
})();
</script>
