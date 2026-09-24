@extends('layouts.user')
@section('title', __('API Keys'))
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- ONE-TIME KEY REVEAL --}}
            @if(session('new_key'))
            <div class="alert alert-warning d-flex align-items-start gap-3">
                <div style="font-size:1.5rem">🔑</div>
                <div class="flex-grow-1">
                    <strong>{{ __('Copy your new API key now — it will not be shown again.') }}</strong>
                    <p class="mb-1 text-muted small">Key name: <em>{{ session('new_key_name') }}</em></p>
                    <div class="input-group mt-2" style="max-width:600px">
                        <input type="text" id="new-key-input" class="form-control font-monospace"
                               value="{{ session('new_key') }}" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('new-key-input').value);this.textContent='Copied!'">
                            {{ __('Copy') }}
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════ --}}
            {{-- TABS: API KEYS & DOCUMENTATION                                 --}}
            {{-- ═══════════════════════════════════════════════════════════ --}}
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys-panel" type="button" role="tab">
                        <i class="fa fa-key me-2"></i>{{ __('API Keys') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-panel" type="button" role="tab">
                        <i class="fa fa-book me-2"></i>{{ __('Documentation') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- TAB 1: API KEYS --}}
                <div class="tab-pane fade show active" id="keys-panel" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Your API Keys') }}</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-new-key">
                    + {{ __('Generate New Key') }}
                </button>
            </div>

            <div class="table-responsive mb-5">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Domain') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Annual usage') }}</th>
                            <th>{{ __('Last used') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($keys as $key)
                        @php $pct = $key->rate_limit > 0 ? round($key->annualUsageCount() / $key->rate_limit * 100) : 0; @endphp
                        <tr>
                            <td><strong>{{ $key->name }}</strong></td>
                            <td>
                                @if($key->isPublishable())
                                    <span class="badge bg-info-subtle text-info-emphasis">{{ __('Publishable') }}</span>
                                    <div class="small text-muted"><code>pk_live_…</code></div>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis">{{ __('Secret') }}</span>
                                    <div class="small text-muted"><code>sk_{{ $key->mode ?? 'live' }}_…</code></div>
                                @endif
                                @if(($key->mode ?? 'live') === 'test')<span class="badge bg-secondary">{{ __('Test') }}</span>@endif
                                @if(!$key->isPublishable())
                                    <div class="small mt-1">
                                        @if($key->scopes)
                                            @foreach($key->scopes as $sc)<span class="badge bg-light text-dark border">{{ $sc }}</span> @endforeach
                                        @else
                                            <span class="text-muted">{{ __('Full access') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($key->domain)
                                    <code>{{ $key->domain }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($key->active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Revoked') }}</span>
                                @endif
                            </td>
                            <td>
                                @php $annual = $key->annualUsageCount(); @endphp
                                <div class="progress" style="height:6px;width:120px;display:inline-block;vertical-align:middle">
                                    <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                         style="width:{{ min($pct,100) }}%"></div>
                                </div>
                                <small class="ms-2">{{ number_format($annual) }} / {{ number_format($key->rate_limit) }}</small>
                            </td>
                            <td>{{ $key->last_used_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                @if($key->active)
                                <form method="POST" action="{{ route('vendor.api_keys.rotate', $key->id) }}" style="display:inline"
                                      onsubmit="return confirm('{{ __('Rotate this key? Your current key will stop working immediately.') }}')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning">{{ __('Rotate') }}</button>
                                </form>
                                <form method="POST" action="{{ route('vendor.api_keys.revoke', $key->id) }}" style="display:inline"
                                      onsubmit="return confirm('{{ __('Revoke this key? This cannot be undone.') }}')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">{{ __('Revoke') }}</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                {{ __('No API keys yet. Generate one to start integrating your website.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

                {{-- END TAB 1 --}}
                </div>

                {{-- TAB 2: DOCUMENTATION --}}
                <div class="tab-pane fade" id="docs-panel" role="tabpanel">
                    <div class="alert alert-info mb-4">
                        <i class="fa fa-info-circle me-2"></i>
                        {{ __('All API requests return only YOUR data. You cannot access other vendors\' data.') }}
                    </div>
                    <div class="card mb-4"><div class="card-body">
                        <p class="mb-2">{{ __('The full API documentation now has its own page: guides, every endpoint with its fields and errors, examples in cURL, JavaScript, PHP and Python, and a box to try each call with a test key.') }}</p>
                        <p class="mb-3"><strong>{{ __('Base URL') }}</strong> <code>{{ rtrim(config('app.url'),'/') }}/api/v</code></p>
                        <a class="btn btn-primary" href="{{ route('vendor.api_docs') }}">{{ __('Open the API documentation') }}</a>
                        <a class="btn btn-outline-secondary" href="{{ rtrim(config('app.url'),'/') }}/api/v/openapi.json" target="_blank" rel="noopener">OpenAPI 3.1</a>
                        <a class="btn btn-outline-secondary" href="{{ rtrim(config('app.url'),'/') }}/api/v/postman.json" target="_blank" rel="noopener">Postman</a>
                    </div></div>
                </div>
                {{-- END TAB 2 --}}
            </div>

            {{-- ═══════════════════════════════════════════════════════════ --}}
            {{-- ALLOWED ORIGINS (CORS)                                       --}}
            {{-- ═══════════════════════════════════════════════════════════ --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Allowed Origins') }} <small class="text-muted fw-normal fs-6">{{ __('(for browser calls from your website)') }}</small></h5>
            </div>
            <form method="POST" action="{{ route('vendor.api_keys.origins.store') }}" class="d-flex gap-2 mb-3">
                @csrf
                <input type="text" name="origin" class="form-control" style="max-width:360px"
                       placeholder="https://yourwebsite.com" pattern="https://.*">
                <button class="btn btn-outline-primary">{{ __('Add Origin') }}</button>
            </form>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>{{ __('Origin') }}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($origins as $origin)
                        <tr>
                            <td><code>{{ $origin->origin }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('vendor.api_keys.origins.destroy', $origin->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')">{{ __('Remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center">{{ __('No origins added. Server-to-server calls work without this.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ═══════════════════════════════════════════════════════════ --}}
            {{-- WEBHOOKS                                                      --}}
            {{-- ═══════════════════════════════════════════════════════════ --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Webhooks') }} <small class="text-muted fw-normal fs-6">{{ __('(push booking events to your website)') }}</small></h5>
            </div>
            <form method="POST" action="{{ route('vendor.api_keys.webhooks.store') }}" class="card p-3 mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">{{ __('Endpoint URL') }}</label>
                        <input type="url" name="url" class="form-control" placeholder="https://yoursite.com/webhooks/tsoka" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">{{ __('Events') }}</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(array_keys(\Modules\Vendor\Services\WebhookEvents::CATALOGUE) as $evt)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="events[]" value="{{ $evt }}" id="evt-{{ $evt }}">
                                <label class="form-check-label small" for="evt-{{ $evt }}">{{ $evt }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100">{{ __('Register') }}</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('URL') }}</th>
                            <th>{{ __('Events') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Deliveries') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($webhooks as $wh)
                        <tr>
                            <td><code>{{ $wh->url }}</code></td>
                            <td>{{ implode(', ', $wh->events) }}</td>
                            <td>
                                @if($wh->active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td>{{ $wh->deliveries_count }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('vendor.api_keys.webhooks.destroy', $wh->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center">{{ __('No webhooks registered.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

{{-- Modal: Generate new key --}}
<div class="modal fade" id="modal-new-key" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('vendor.api_keys.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Generate API Key') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Key name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. dare2travel website') }}" required maxlength="100">
                        <small class="text-muted">{{ __('A label so you remember what this key is for.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Key type') }} <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type-publishable" value="publishable" checked>
                            <label class="form-check-label" for="type-publishable">
                                <strong>{{ __('Publishable') }}</strong> <code>pk_live_…</code>
                                <span class="badge bg-info-subtle text-info-emphasis ms-1">{{ __('read-only') }}</span><br>
                                <small class="text-muted">{{ __('Safe to use in your website\'s front-end (browser). Can read services, availability & start bookings, but cannot make changes.') }}</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="type" id="type-secret" value="secret">
                            <label class="form-check-label" for="type-secret">
                                <strong>{{ __('Secret') }}</strong> <code>sk_live_…</code>
                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1">{{ __('full access') }}</span><br>
                                <small class="text-muted">{{ __('Full read + write access. Use ONLY on your server — never expose it in browser/front-end code.') }}</small>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="scope-picker">
                        <label class="form-label">{{ __('What this key may do') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="scope-all" checked>
                            <label class="form-check-label" for="scope-all"><strong>{{ __('Everything') }}</strong> <small class="text-muted">({{ __('secret keys only; a publishable key can only read') }})</small></label>
                        </div>
                        <div id="scope-list" class="border rounded p-2 mt-2" style="display:none;max-height:220px;overflow:auto">
                            @foreach(\App\Support\ApiScopes::AREAS as $area => $what)
                            <div class="d-flex align-items-start gap-3 py-1 border-bottom">
                                <div class="flex-grow-1"><strong>{{ $area }}</strong><div class="small text-muted">{{ $what }}</div></div>
                                <label class="small text-nowrap"><input type="checkbox" name="scopes[]" value="{{ $area }}:read" class="scope-box"> {{ __('read') }}</label>
                                <label class="small text-nowrap"><input type="checkbox" name="scopes[]" value="{{ $area }}:write" class="scope-box"> {{ __('write') }}</label>
                            </div>
                            @endforeach
                        </div>
                        <small class="text-muted">{{ __('Give a key only what it needs: a booking website does not need invoices. Write includes read.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Mode') }}</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="mode" id="mode-live" value="live" checked>
                            <label class="btn btn-outline-success" for="mode-live"><i class="fa fa-circle-dot me-1"></i>{{ __('Live') }}</label>
                            <input type="radio" class="btn-check" name="mode" id="mode-test" value="test">
                            <label class="btn btn-outline-secondary" for="mode-test"><i class="fa fa-flask me-1"></i>{{ __('Test (sandbox)') }}</label>
                        </div>
                        <small class="text-muted d-block mt-1">{{ __('Test keys (…_test_…) work without a subscription and don\'t count against your limits — perfect for building before going live.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Website domain') }}</label>
                        <input type="text" name="domain" class="form-control" placeholder="dare2travel.com">
                        <small class="text-muted">{{ __('Your website domain. Browser calls from this domain will be allowed automatically (CORS). Leave blank for server-to-server use only.') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Annual request limit') }}</label>
                        <input type="number" name="rate_limit" class="form-control" value="100000" min="0" max="10000000">
                        <small class="text-muted">{{ __('Maximum API calls per year. 0 = unlimited.') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Generate Key') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var all = document.getElementById('scope-all'), list = document.getElementById('scope-list');
    if (!all || !list) return;
    var sync = function () {
        list.style.display = all.checked ? 'none' : 'block';
        list.querySelectorAll('.scope-box').forEach(function (b) { if (all.checked) b.checked = false; b.disabled = all.checked; });
    };
    all.addEventListener('change', sync); sync();
    var pub = document.getElementById('type-publishable'), sec = document.getElementById('type-secret'), box = document.getElementById('scope-picker');
    var toggle = function () { if (box) box.style.display = (sec && sec.checked) ? '' : 'none'; };
    if (pub) pub.addEventListener('change', toggle); if (sec) sec.addEventListener('change', toggle); toggle();
})();
</script>
@endsection
