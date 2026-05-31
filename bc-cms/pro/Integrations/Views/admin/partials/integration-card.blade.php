{{--
  Integration card — TourPay design language.
  Variables: $item, $saved (Integration|null), $cat, $rentals, $roomChannels
--}}
@php
    $status    = $saved?->status ?? 'disconnected';
    $connected = $status === 'connected';
    $error     = $status === 'error';
    $required  = !empty($item['required']);
    $color     = $item['color'] ?? '#333';
    $logoDomain   = $item['logo_domain'] ?? null;
    $nameInitial  = strtoupper(substr($item['name'], 0, 1));
    $stripeColor  = $connected ? '#16a34a' : ($error ? '#e11d48' : '#e4e4e4');
@endphp

<div class="int-card" id="card-{{ $item['slug'] }}" data-status="{{ $status }}">

    {{-- Status stripe --}}
    <div class="int-card__stripe" style="background:{{ $stripeColor }}"></div>

    {{-- Header --}}
    <div class="int-card__head">
        {{-- Logo --}}
        <div class="int-logo" style="--brand:{{ $color }}">
            @if($logoDomain)
            <img src="https://logo.clearbit.com/{{ $logoDomain }}"
                 alt="{{ $item['name'] }}"
                 class="int-logo__img"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            @endif
            <span class="int-logo__fallback" @if($logoDomain) style="display:none" @endif>
                {{ $nameInitial }}
            </span>
        </div>

        {{-- Name + meta --}}
        <div class="int-card__meta">
            <div class="int-card__name">
                {{ $item['name'] }}
                @if($required)
                    <span class="int-tag int-tag--warn">Required</span>
                @endif
            </div>
            @if($saved?->last_verified_at && $connected)
                <div class="int-card__verified">Verified {{ $saved->last_verified_at->diffForHumans() }}</div>
            @endif
        </div>

        {{-- Status badge --}}
        <div class="int-card__status">
            @if($connected)
                <span class="tp-badge tp-badge--connected">
                    <span class="tp-badge__dot"></span>Connected
                </span>
            @elseif($error)
                <span class="tp-badge tp-badge--error">
                    <span class="tp-badge__dot"></span>Error
                </span>
            @else
                <span class="tp-badge tp-badge--idle">
                    <span class="tp-badge__dot"></span>Not Connected
                </span>
            @endif
        </div>
    </div>

    {{-- Body --}}
    <div class="int-card__body">
        <p class="int-card__desc">{{ $item['description'] }}</p>

        @if($saved?->last_error)
        <div class="int-card__error">
            <i class="ion ion-ios-warning"></i> {{ $saved->last_error }}
        </div>
        @endif
    </div>

    {{-- Actions --}}
    @if($connected)
        <div class="int-card__actions">
            <form method="POST" action="{{ route('admin.integrations.test', $item['slug']) }}" class="d-inline">
                @csrf
                <button type="submit" class="tp-ab-btn tp-ab-btn--outline">
                    <i class="ion ion-ios-pulse"></i> Test Connection
                </button>
            </form>
            <button class="tp-ab-btn tp-ab-btn--outline"
                    data-bs-toggle="collapse"
                    data-bs-target="#cfg-{{ $item['slug'] }}">
                <i class="ion ion-ios-create"></i> Edit Credentials
            </button>
            <form method="POST" action="{{ route('admin.integrations.disconnect', $item['slug']) }}" class="d-inline ms-auto">
                @csrf
                <button type="submit" class="tp-ab-btn tp-ab-btn--danger"
                        onclick="return confirm('Remove {{ $item['name'] }} integration?')">
                    <i class="ion ion-ios-close-circle"></i> Disconnect
                </button>
            </form>
        </div>

        {{-- Wetu panel --}}
        @if(!empty($item['panel']) && $item['panel'] === 'wetu')
        <div class="int-card__actions" style="border-top:none;padding-top:0">
            <a href="{{ route('admin.integrations.wetu.itineraries') }}" class="tp-ab-btn tp-ab-btn--primary">
                <i class="ion ion-ios-list"></i> Browse Itineraries
            </a>
            <form method="POST" action="{{ route('admin.integrations.wetu.sync') }}" class="d-inline">
                @csrf
                <button type="submit" class="tp-ab-btn tp-ab-btn--outline">
                    <i class="ion ion-ios-refresh"></i> Sync Itineraries
                </button>
            </form>
        </div>
        @endif

    @elseif(!empty($item['fields']))
        <div class="int-card__actions">
            <button class="tp-ab-btn tp-ab-btn--primary w-100"
                    data-bs-toggle="collapse"
                    data-bs-target="#cfg-{{ $item['slug'] }}">
                <i class="ion ion-ios-add-circle"></i> Set Up {{ $item['name'] }}
            </button>
        </div>
    @endif

    {{-- Credentials form --}}
    @if(!empty($item['fields']))
    <div id="cfg-{{ $item['slug'] }}" class="collapse int-card__form {{ $error ? 'show' : '' }}">
        <form method="POST" action="{{ route('admin.integrations.connect', $item['slug']) }}">
            @csrf
            @foreach($item['fields'] ?? [] as $field)
            <div class="tp-field">
                <label>
                    {{ $field['label'] }}@if(!empty($field['required'])) <span style="color:#e11d48">*</span>@endif
                </label>

                @if(($field['type'] ?? 'text') === 'select')
                    <select name="{{ $field['key'] }}" {{ !empty($field['required']) ? 'required' : '' }}>
                        @foreach($field['options'] ?? [] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ ($saved?->credential($field['key']) ?? ($field['default'] ?? '')) == $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                @elseif($field['type'] === 'password')
                    <input type="password"
                           name="{{ $field['key'] }}"
                           value="{{ $connected ? '••••••••' : '' }}"
                           autocomplete="new-password"
                           placeholder="{{ $connected ? 'Leave blank to keep current' : '' }}"
                           {{ (!$connected && !empty($field['required'])) ? 'required' : '' }}>
                @elseif($field['type'] === 'textarea')
                    <textarea name="{{ $field['key'] }}"
                              rows="3"
                              {{ !empty($field['required']) ? 'required' : '' }}>{{ $saved?->credential($field['key']) ?? ($field['default'] ?? '') }}</textarea>
                @else
                    <input type="text"
                           name="{{ $field['key'] }}"
                           value="{{ $saved?->credential($field['key']) ?? ($field['default'] ?? '') }}"
                           {{ !empty($field['required']) ? 'required' : '' }}>
                @endif

                @if(!empty($field['hint']))
                    <div class="tp-field__hint">{{ $field['hint'] }}</div>
                @endif
            </div>
            @endforeach

            <div class="tp-field-actions">
                <button type="submit" class="tp-ab-btn tp-ab-btn--primary">
                    <i class="ion ion-ios-checkmark-circle"></i>
                    {{ $connected ? 'Save Changes' : 'Connect ' . $item['name'] }}
                </button>
                <button type="button" class="tp-ab-btn tp-ab-btn--ghost"
                        data-bs-toggle="collapse"
                        data-bs-target="#cfg-{{ $item['slug'] }}">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Capabilities --}}
    @if(!empty($item['capabilities']))
    <div class="int-card__caps">
        @foreach($item['capabilities'] as $cap)
        <div class="int-cap">
            <span class="int-cap__check" style="color:{{ $color }}">✓</span>
            <span>{{ $cap }}</span>
        </div>
        @endforeach
    </div>
    @endif

</div>

@once
@push('css')
<style>
/* ── Integration Card ──────────────────────────────────────────── */
.int-card {
    background: #fff;
    border: 1px solid #ebebeb;
    border-radius: 10px;
    overflow: hidden;
    transition: box-shadow .15s, transform .15s;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.int-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.09); transform: translateY(-2px); }

.int-card__stripe { height: 3px; width: 100%; flex-shrink: 0; }

.int-card__head {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px 14px;
    border-bottom: 1px solid #f5f5f5;
}

/* Logo */
.int-logo {
    width: 48px; height: 48px;
    border-radius: 10px;
    border: 1px solid #ebebeb;
    background: #fafafa;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.int-logo__img { width: 32px; height: 32px; object-fit: contain; }
.int-logo__fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%; height: 100%;
    font-size: 18px;
    font-weight: 800;
    background: color-mix(in srgb, var(--brand) 15%, transparent);
    color: var(--brand);
    letter-spacing: -.02em;
}

/* Name / meta */
.int-card__meta { flex: 1; min-width: 0; }
.int-card__name {
    font-size: 14px;
    font-weight: 700;
    color: #0a0a0a;
    letter-spacing: -.01em;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
    line-height: 1.3;
}
.int-card__verified { font-size: 11px; color: #bbb; margin-top: 3px; }

/* Tags */
.int-tag {
    font-size: 10px; font-weight: 700; letter-spacing: .04em;
    padding: 1px 7px; border-radius: 100px; white-space: nowrap;
}
.int-tag--warn { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

/* Status badge */
.int-card__status { flex-shrink: 0; }
.tp-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 100px;
    font-size: 11px; font-weight: 600; letter-spacing: .01em;
    white-space: nowrap;
}
.tp-badge__dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: .7; flex-shrink: 0; }
.tp-badge--connected { background: #f0fdf4; color: #16a34a; }
.tp-badge--error     { background: #fff1f2; color: #e11d48; }
.tp-badge--idle      { background: #f4f4f5; color: #71717a; }

/* Body */
.int-card__body { padding: 12px 18px 14px; flex: 1; }
.int-card__desc { font-size: 13px; color: #888; line-height: 1.55; margin: 0 0 8px; }
.int-card__error {
    font-size: 12px; color: #e11d48;
    background: #fff1f2; border-radius: 6px;
    padding: 8px 10px; margin-top: 8px;
    display: flex; gap: 6px; align-items: flex-start;
    line-height: 1.45;
}

/* Actions bar */
.int-card__actions {
    padding: 10px 18px;
    border-top: 1px solid #f5f5f5;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}

/* Action buttons */
.tp-ab-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 13px; border-radius: 7px;
    font-size: 12px; font-weight: 600; letter-spacing: -.01em;
    text-decoration: none !important; white-space: nowrap;
    border: none; cursor: pointer; transition: all .12s;
    line-height: 1;
}
.tp-ab-btn.w-100 { justify-content: center; }
.tp-ab-btn--primary { background: #0a0a0a; color: #fff !important; }
.tp-ab-btn--primary:hover { background: #222; }
.tp-ab-btn--outline { background: #fff; color: #333 !important; border: 1.5px solid #e4e4e4; }
.tp-ab-btn--outline:hover { border-color: #0a0a0a; color: #0a0a0a !important; }
.tp-ab-btn--ghost  { background: #f5f5f5; color: #555 !important; }
.tp-ab-btn--ghost:hover  { background: #ebebeb; color: #0a0a0a !important; }
.tp-ab-btn--danger { background: #fff1f2; color: #e11d48 !important; border: 1.5px solid #fecdd3; }
.tp-ab-btn--danger:hover { background: #ffe4e6; }

/* Credentials form */
.int-card__form { background: #fafafa; border-top: 1px solid #f0f0f0; }
.int-card__form form { padding: 16px 18px; }

/* tp-field (TourPay form field) */
.tp-field { margin-bottom: 12px; }
.tp-field:last-of-type { margin-bottom: 0; }
.tp-field label {
    display: block;
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #888;
    margin-bottom: 5px;
}
.tp-field input,
.tp-field select,
.tp-field textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e8e8e8;
    border-radius: 7px;
    font-size: 13px; color: #222;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
    font-family: inherit;
}
.tp-field input:focus,
.tp-field select:focus,
.tp-field textarea:focus {
    border-color: #0a0a0a;
    box-shadow: 0 0 0 3px rgba(10,10,10,.06);
}
.tp-field textarea { resize: vertical; min-height: 72px; }
.tp-field__hint { font-size: 11px; color: #bbb; margin-top: 4px; line-height: 1.4; }

.tp-field-actions {
    display: flex; gap: 8px; margin-top: 16px;
    padding-top: 14px; border-top: 1px solid #f0f0f0;
}

/* Capabilities */
.int-card__caps {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 4px 12px;
    padding: 12px 18px;
    border-top: 1px solid #f5f5f5;
}
.int-cap { display: flex; gap: 6px; align-items: flex-start; font-size: 12px; color: #555; line-height: 1.4; }
.int-cap__check { font-size: 13px; font-weight: 700; flex-shrink: 0; line-height: 1.4; }

@media (max-width: 480px) {
    .int-card__caps { grid-template-columns: 1fr; }
}
</style>
@endpush
@endonce
