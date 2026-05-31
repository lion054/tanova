<div>
    <div class="sidebar__item -no-border mb-30 lg:d-none">
        <div class="px-20 py-20 bg-light-2 rounded-4">
            <h5 class="text-18 fw-500 mb-10">{{ setting_item_with_lang('visa_page_search_title') }}</h5>
            <livewire:visa::search-form :layout="'vertical'" />
        </div>
    </div>

    <div class="bc_form_filter bc_filter lg:d-none" data-x="filterPopup" data-x-toggle="-is-active">
        <aside class="sidebar y-gap-40 p-4 p-lg-0">
            <div data-x-click="filterPopup" class="-icon-close is_mobile pb-0">
                <i class="icon-close"></i>
            </div>
            <div class="sidebar__item pb-30" wire:ignore x-data x-init="priceRangeVisaSidebar()">
                <h5 class="text-18 fw-500 mb-10">{{ __('Price') }}</h5>
                <div class="row x-gap-10 y-gap-30">
                    <div class="col-12">
                        <div class="js-price-searchPage">
                            <div class="text-14 fw-500"></div>
                            <?php
                           
                            $price_min = $pri_from = floor(App\Currency::convertPrice($min_max_price[0]));
                            $price_max = $pri_to = ceil(App\Currency::convertPrice($min_max_price[1]));
                            if (!empty(($price_range = Request::query('price_range')))) {
                                $pri_from = explode(';', $price_range)[0];
                                $pri_to = explode(';', $price_range)[1];
                            }
                            $currency = App\Currency::getCurrency(App\Currency::getCurrent());
                            ?>
                            <input type="hidden" class="filter-price irs-hidden-input"  name="price_range"
                                data-symbol=" {{ $currency['symbol'] ?? '' }}" data-min="{{ $price_min }}"
                                data-max="{{ $price_max }}" data-from="{{ $pri_from }}"
                                data-to="{{ $pri_to }}" readonly="" value="{{ $price_range }}">
                            <div class="d-flex justify-between mb-20">
                                <div class="text-15 text-dark-1">
                                    <span class="js-lower"></span>
                                    -
                                    <span class="js-upper"></span>
                                </div>  
                            </div>
                            <div class="px-5">
                                <div class="js-slider"></div>
                            </div>
                            <button type="submit"
                                class="flex-center bg-blue-1 rounded-4 px-3 py-1 mt-3 text-12 fw-600 text-white btn-apply-price-range mt-20 d-none">{{ __('APPLY') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bc-clear-filter hidden-lg-up" style="display: none;">
                <a href="#" onclick="return false" class="button px-15 py-10 -dark-1 bg-blue-1 text-white">
                    <i class="icon-loop-2 mr-10 text-12"></i>
                    <span>{{ __('Clear All') }}</span>
                </a>
            </div>
        </aside>
    </div>
</div>
@script
<script>
window.priceRangeVisaSidebar = function() {
    const targets = document.querySelectorAll('.js-price-searchPage')
    targets.forEach(el => {
      const slider = el.querySelector(".js-slider");
      const input_price = el.querySelector(".filter-price");
      const min = parseFloat(input_price.dataset.min);
      const max = parseFloat(input_price.dataset.max);
      const from = parseFloat(input_price.dataset.from);
      const to = parseFloat(input_price.dataset.to);
      const symbol = input_price.dataset.symbol;

      noUiSlider.create(slider, {
        start: [from, to],
        step: 1,
        connect: true,
        direction: bookingCore.rtl ? "rtl" : "ltr",
        range: {
          min: min,
          max: max,
        },
        format: {
          to: function (value) {
            return symbol + Math.floor(value);
          },

          from: function (value) {
            return Math.floor(value);
          },
        },
      });

      const snapValues = [el.querySelector(".js-lower"), el.querySelector(".js-upper")];

      slider.noUiSlider.on("update", function (values, handle) {
        snapValues[handle].innerHTML = values[handle];
        input_price.value = values[0].replace(symbol, "") + ";" + values[1].replace(symbol, "");
        $wire.set('price_range', values[0].replace(symbol, "") + ';' + values[1].replace(symbol, ""));
      });
      // slider.noUiSlider.on('end', function (values, handle) {
      //     input_price.value = values[0].replace(symbol, "")+';'+values[1].replace(symbol, "");
      // })
    })
  }
   
</script>
@endscript