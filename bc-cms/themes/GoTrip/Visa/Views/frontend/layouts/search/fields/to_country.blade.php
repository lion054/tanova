<div class="searchMenu-loc js-form-dd js-liverSearch item">
    <?php
    $name = '';
    $list_json = [
        [
            'id' => '',
            'title' => __('Any Country'),
        ],
    ];
    $old = !empty($to_country) ? $to_country : '';
    $traverse = function ($countries, $prefix = '') use (&$traverse, &$list_json, &$name, $old) {
        foreach ($countries as $code => $country) {
            if ($old == $code) {
                $name = $country;
            }
            $list_json[] = [
                'id' => $code,
                'title' => $country,
            ];
        }
    };
    
    $traverse(\Modules\Visa\Models\VisaService::countryList());
    ?>

    <span class="clear-loc absolute bottom-0 text-12"><i class="icon-close"></i></span>
    <div data-x-dd-click="searchMenu-loc">
        <h4 class="text-15 fw-500 ls-2 lh-16">{{ $field['title'] }}</h4>
        <div class="text-15 text-light-1 ls-2 lh-16   smart-search  ">
            <input type="hidden" wire:model="to_country" name="to_country" class="js-search-get-id child_id" value="{{ $old ?? '' }}">
            <input type="text" autocomplete="off"
                class="smart-search-location
            parent_text js-search js-dd-focus"
                placeholder="{{ __('Where are you going?') }}" value="{{ $old ?? '' }}"
                data-onLoad="{{ __('Loading...') }}" data-default="{{ json_encode($list_json) }}">
        </div>
    </div>
    <div class="searchMenu-loc__field shadow-2 js-popup-window " data-x-dd="searchMenu-loc"
        data-x-dd-toggle="-is-active">
        <div class="bg-white px-30 py-30 sm:px-0 sm:py-15 rounded-4">
            <div class="y-gap-5 js-results">
                @foreach ($list_json as $item)
                    <div class="-link d-block col-12 text-left rounded-4 px-20 py-15 js-search-option"
                        data-id="{{ $item['id'] }}">
                        <div class="d-flex align-items-center">
                            <div class="icon-location-2 text-light-1 text-20 pt-4"></div>
                            <div class="ml-10">
                                <div class="text-15 lh-12 fw-500 js-search-option-target">{{ $item['title'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
