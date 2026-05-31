@php
    $translation = $row->translate();
@endphp
<div class="card transition-3d-hover shadow-hover-2 item-loop h-100 {{ $wrap_class ?? '' }} d-none">
    <div class="position-relative mb-2">
        <a @if (!empty($blank)) target="_blank" @endif href="{{ $row->getDetailUrl($include_param ?? true) }}"
            class="d-block gradient-overlay-half-bg-gradient-v5">
            <img class="min-height-230 bg-img-hero card-img-top" src="{{ $row->image_url }}" alt="{!! clean($translation->title) !!}">
        </a>
        <div class="position-absolute top-0 left-0 pt-4 pl-3 featured">
            @if ($row->is_featured == '1')
                <span class="badge badge-pill bg-white text-primary px-4 mr-3 py-2 font-size-14 font-weight-normal">{{ __('Featured') }}</span>
            @endif
            @if ($row->discount_percent)
                <span class="badge badge-pill bg-white text-danger px-3  py-2 font-size-14 font-weight-normal">{{ $row->discount_percent }}%</span>
            @endif
        </div>
        <div class="position-absolute top-0 right-0 pt-4 pr-3 btn-wishlist">
            <button type="button" class="p-0 btn btn-sm btn-icon text-white rounded-circle service-wishlist {{ $row->isWishList() }}"
                data-id="{{ $row->id }}" data-type="{{ $row->type }}" data-toggle="tooltip" data-placement="top" title=""
                data-original-title="{{ __('Save for later') }}">
                <span class="flaticon-valentine-heart font-size-20"></span>
            </button>
        </div>
        <div class="position-absolute bottom-0 left-0 right-0 text-content">
            <div class="px-3 pb-2">
                <h2 class="h5 text-white mb-0 font-weight-bold">
                    <small class="mr-1 font-size-14">{{ __('From') }}</small>
                    <small class="mr-1 font-size-13 text-decoration-line-through">
                        {{ $row->original_price }}
                    </small>
                    {{ $row->price }}
                </h2>
            </div>
        </div>
        <div class="location d-none position-absolute bottom-0 left-0 right-0">
            <div class="px-4 pb-3">
                @if (!empty($row->location->name))
                    @php $location =  $row->location->translate(); @endphp
                    <a href="{{ $row->location->getDetailUrl() ?? '' }}" class="d-block">
                        <div class="d-flex align-items-center font-size-14 text-white">
                            <i class="icon flaticon-pin-1 mr-2 font-size-20"></i> {{ $location->name ?? '' }}
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body px-3 py-2">
        @if (!empty($row->country))
            <div class="d-block location">
                <div class="mb-1 d-flex align-items-center font-size-14 text-gray-1">
                    <i class="icon flaticon-pin-1 mr-2 font-size-15"></i> {{ $row->country }}
                </div>
            </div>
        @endif
        <a @if (!empty($blank)) target="_blank" @endif href="{{ $row->getDetailUrl($include_param ?? true) }}"
            class="card-title font-size-17 font-weight-bold mb-0 text-dark">
            {!! clean($translation->title) !!}
        </a>
        @if (setting_item('visa_enable_review'))
            <?php
            $reviewData = $row->getScoreReview();
            $score_total = $reviewData['score_total'];
            ?>
            <div class="my-2 service-review review-{{ $score_total }}">
                <div class="d-inline-flex align-items-center font-size-17 text-lh-1 text-primary">
                    <div class="list-star green-lighter mr-2">
                        <ul class="booking-item-rating-stars">
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                        </ul>
                        <div class="booking-item-rating-stars-active" style="width: {{ $score_total * 2 * 10 ?? 0 }}%">
                            <ul class="booking-item-rating-stars">
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                            </ul>
                        </div>
                    </div>
                    <span class="text-secondary font-size-14 mt-1">
                        @if ($reviewData['total_review'] > 1)
                            {{ __(':number Reviews', ['number' => $reviewData['total_review']]) }}
                        @else
                            {{ __(':number Review', ['number' => $reviewData['total_review']]) }}
                        @endif
                    </span>
                </div>
            </div>
        @endif
        <div class="g-price d-none">
            <div class="prefix">
                <span class="fr_text">{{ __('from') }}</span>
            </div>
            <div class="price">
                <span class="onsale">{{ $row->original_price }}</span>
                <span class="text-price">{{ $row->price }}</span>
            </div>
        </div>
        <div class="mb-1 d-flex align-items-center font-size-14 text-gray-1 duration">
            <i class="icon flaticon-clock-circular-outline mr-2 font-size-14"></i>
            {{ __(':amount day(s)', ['amount' => $row->processing_days]) }}
        </div>
    </div>
</div>


@php
    $translation = $row->translate();
@endphp
<div class="tourCard -type-1 rounded-4  {{ $wrap_class ?? '' }}">
    <div class="tourCard__image">
        <div class="cardImage ratio ratio-1:1">
            <a @if (!empty($blank)) target="_blank" @endif href="{{ $row->getDetailUrl() }}">
                <div class="cardImage__content">
                    @if ($row->image_url)
                        @if (!empty($disable_lazyload))
                            <img src="{{ $row->image_url }}" class="img-responsive rounded-4 col-12 js-lazy" alt="">
                        @else
                            {!! get_image_tag($row->image_id, 'medium', ['class' => 'img-responsive rounded-4 col-12 js-lazy', 'alt' => $translation->title]) !!}
                        @endif
                    @endif
                </div>
            </a>
            <div class="service-wishlist {{ $row->isWishList() }}" data-id="{{ $row->id }}" data-type="{{ $row->type }}">
                <div class="cardImage__wishlist">
                    <button class="button -blue-1 bg-white size-30 rounded-full shadow-2">
                        <i class="icon-heart text-12"></i>
                    </button>
                </div>
            </div>
            <div class="cardImage__leftBadge">
                @if ($row->is_featured == '1')
                    <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-yellow-1 text-dark-1">
                        {{ __('Featured') }}
                    </div>
                @endif
                @if ($row->discount_percent)
                    <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-blue-1 text-white mt-5">
                        {{ __('Sale off :number', ['number' => $row->discount_percent]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="tourCard__content mt-10">
        <div class="d-flex items-center lh-14 mb-5">
            <div class="text-14 text-light-1">{{ __(':amount day(s)', ['amount' => $row->processing_days]) }}</div>
        </div>
        <h4 class="tourCard__title text-dark-1 text-18 lh-16 fw-500">
            <a class="text-dark-1-i" @if (!empty($blank)) target="_blank" @endif href="{{ $row->getDetailUrl() }}">
                <span>{{ $translation->title }}</span></a>
        </h4>
        @if (!empty($row->location->name))
            @php $location = $row->location->translate() @endphp
            <p class="text-light-1 lh-14 text-14 mt-5">{{ $location->name }}</p>
        @endif
        <div class="row justify-between items-center pt-15">
            <div class="col-auto">
                @if (setting_item('visa_enable_review'))
                    <?php $reviewData = $row->getScoreReview();
                    $score_total = $reviewData['score_total']; ?>
                    <div class="d-flex items-center">
                        @include('Layout::common.rating', ['score_total' => $score_total])
                        <div class="text-14 text-light-1 ml-10">
                            @if ($reviewData['total_review'] > 1)
                                {{ __(':number Reviews', ['number' => $reviewData['total_review']]) }}
                            @else
                                {{ __(':number Review', ['number' => $reviewData['total_review']]) }}
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-auto">
                <div class="text-14 text-light-1">
                    {{ __('from') }}
                    <span class="text-14 text-red-1 line-through d-inline-flex">{{ $row->original_price }}</span>
                    <span class="fw-500 text-dark-1 d-inline-flex">{{ $row->price }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
