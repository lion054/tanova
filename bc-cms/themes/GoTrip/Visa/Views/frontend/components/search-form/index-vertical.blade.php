<form x-init="() => {
    window.Events.init();
    window.Events.ddInit();
    window.Events.liveSearch();
}" wire:submit.prevent="submit" method="get" class="gotrip_form_search bc_form_search bc_form form form-search-sidebar">
    <div class="field-items"  wire:ignore>
        <div class="row w-100 m-0">
            @php
                $visa_search_fields = setting_item_array('visa_search_fields');
                $visa_search_fields = array_values(
                    \Illuminate\Support\Arr::sort($visa_search_fields, function ($value) {
                        return $value['position'] ?? 0;
                    }),
                );
            @endphp
            @if (!empty($visa_search_fields))
                @foreach ($visa_search_fields as $field)
                    <div class="col-lg-{{ $field['size'] ?? '6' }} align-self-center px-30 lg:py-20 lg:px-0">
                        @php $field['title'] = $field['title_'.app()->getLocale()] ?? $field['title'] ?? "" @endphp
                        @switch($field['field'])
                            @case ('visa_type')
                                @include('Visa::frontend.layouts.search.fields.visa_type')
                            @break

                            @case ('to_country')
                                @include('Visa::frontend.layouts.search.fields.to_country')
                            @break

                            @case ('guests')
                                @include('Visa::frontend.layouts.search.fields.guests')
                            @break
                        @endswitch
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    <div class="button-item">
        <button class="mainSearch__submit button -dark-1 py-15 h-60 col-12 rounded-100 bg-blue-1 text-white w-100"
            type="submit">
            <i class="icon-search text-20 mr-10"></i>
            <span class="text-search">{{ __('Search') }}</span>
        </button>
    </div>
</form>
