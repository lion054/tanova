<!-- Results count and sort -->
<div class="row y-gap-10 items-center justify-between">
    <div class="col-auto">
        <div class="text-18 fw-500 result-count">
            @if ($rows->total() > 1)
                {!! __(':count visas found', ['count' => $rows->total()]) !!}
            @else
                {!! __(':count visa found', ['count' => $rows->total()]) !!}
            @endif
        </div>
    </div>

    <div class="col-auto">
        <div class="row x-gap-20 y-gap-20">
            <div class="col-auto bc-form-order">
                @include('Layout::global.search.orderby', ['routeName' => 'visa.search', 'hidden_map_button' => true])
            </div>
        </div>
    </div>
</div>

<!--End Filter mobile-->
<div class="ajax-search-result">
    <div class="pt-30 mt-30 border-top-light"></div>
    <div class="row y-gap-30">
        @if ($rows->total() > 0)
            @foreach ($rows as $row)
                <div class="col-md-6 col-xl-4 mb-3 mb-md-4 pb-1">
                    @include('Visa::frontend.layouts.search.loop-grid', ['disable_lazyload' => true])
                </div>
            @endforeach
        @else
            <div class="col-lg-12">
                {{ __('Visa not found') }}
            </div>
        @endif
    </div>

    <div class="bc-pagination">
        {{ $rows->appends(request()->except(['_ajax']))->links('Layout::global.livewire.pagination') }}
        @if ($rows->total() > 0)
            <div class="text-center mt-30 md:mt-10">
                <div class="text-14 text-light-1">
                    {{ __('Showing :from - :to of :total tours', ['from' => $rows->firstItem(), 'to' => $rows->lastItem(), 'total' => $rows->total()]) }}
                </div>
            </div>
        @endif
    </div>
</div>
