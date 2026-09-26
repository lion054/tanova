@php
    $have = $languages->pluck('locale')->push((string) setting_item('site_locale'))->all();
    $addable = collect(config('languages_catalogue', []))->reject(fn ($n, $c) => in_array($c, $have, true));
@endphp
<form class="lg-add" id="lgAdd" method="post" action="{{ route('vendor.languages.add') }}">
    @csrf
    <select name="locale" class="form-control" required>
        <option value="">{{ __('Choose a language...') }}</option>
        @foreach($addable as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
    </select>
    <button class="lg-btn is-primary" type="submit">{{ __('Add') }}</button>
    <small>{{ __('A language you add is available across the platform, so guests can be served in it.') }}</small>
</form>
