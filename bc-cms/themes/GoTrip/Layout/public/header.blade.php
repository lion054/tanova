@php
    $pvUser = Auth::user();
    $pvLangs = collect(\Modules\Language\Models\Language::getActive())->unique('locale')->values();
    $pvLocale = app()->getLocale();
    $pvHome = $pvUser && $pvUser->hasPermission('dashboard_vendor_access') ? url('/user/dashboard') : url('/');
@endphp
<header class="pv-top">
    <div class="pv-wrap pv-top-in">
        <a class="pv-brand" href="{{ url('/') }}" aria-label="Tanova"><img src="{{ url('/images/tanova/tanova-black.png') }}" alt="Tanova" height="26"></a>
        <span class="pv-grow"></span>
        @if (is_enable_multi_lang() && $pvLangs->count() > 1)
            <div class="pv-dd">
                <button type="button" class="pv-chip" onclick="pvDd(this)" aria-haspopup="true">
                    <svg viewBox="0 0 24 24" class="pv-i"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.9 5.6 3.9 9s-1.3 6.4-3.9 9c-2.6-2.6-3.9-5.6-3.9-9S9.4 5.6 12 3z"/></svg>
                    {{ optional($pvLangs->firstWhere('locale', $pvLocale))->name ?: strtoupper($pvLocale) }}
                    <svg viewBox="0 0 24 24" class="pv-i pv-i-sm"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="pv-menu">
                    @foreach ($pvLangs as $l)
                        <a href="{{ request()->fullUrlWithQuery(['lang' => $l->locale]) }}" class="{{ $l->locale === $pvLocale ? 'is-on' : '' }}">{{ $l->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($pvUser && $pvUser->hasPermission('dashboard_vendor_access'))
            <a class="pv-chip pv-chip-dark" href="{{ $pvHome }}">{{ __('My portal') }}</a>
        @elseif (!$pvUser)
            <a class="pv-chip" href="{{ url('/login') }}">{{ __('Sign in') }}</a>
        @endif
    </div>
</header>
