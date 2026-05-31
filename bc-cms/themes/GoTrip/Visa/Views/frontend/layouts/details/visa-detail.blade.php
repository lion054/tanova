<section class="pt-40">
    <div class="container">
        <div class="row y-gap-15 justify-between items-end">
            <div class="col-auto">
                <h1 class="text-30 fw-600">{!! clean($translation->title) !!}</h1>
                <div class="row x-gap-20 y-gap-20 items-center pt-10">
                    @if (setting_item('visa_enable_review'))
                        <div class="col-auto">
                            <?php $reviewData = $row->getScoreReview();
                            $score_total = $reviewData['score_total']; ?>
                            @include('Layout::common.rating', ['score_total' => $score_total])
                        </div>
                        <div class="col-auto">
                            <div class="text-14 lh-14 text-light-1">
                                @if ($reviewData['total_review'] > 1)
                                    {{ __(':number Reviews', ['number' => $reviewData['total_review']]) }}
                                @else
                                    {{ __(':number Review', ['number' => $reviewData['total_review']]) }}
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-auto">
                <div class="row x-gap-10 y-gap-10">
                    <div class="col-auto">
                        <div class="dropdown">
                            <button class="button px-15 py-10 -blue-1 dropdown-toggle" type="button"
                                id="dropdownMenuShare" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <i class="icon-share mr-10"></i>
                                {{ __('Share') }}
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuShare">
                                <a class="dropdown-item facebook"
                                    href="https://www.facebook.com/sharer/sharer.php?u={{ $row->getDetailUrl() }}&amp;title={{ $translation->title }}"
                                    target="_blank" rel="noopener" original-title="{{ __('Facebook') }}">
                                    <i class="fa fa-facebook"></i> {{ __('Facebook') }}
                                </a>
                                <a class="dropdown-item twitter"
                                    href="https://twitter.com/share?url={{ $row->getDetailUrl() }}&amp;title={{ $translation->title }}"
                                    target="_blank" rel="noopener" original-title="{{ __('X') }}">
                                    <i class="fa fa-twitter"></i> {{ __('X') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-auto">
                        <div class="service-wishlist {{ $row->isWishList() }}" data-id="{{ $row->id }}"
                            data-type="{{ $row->type }}">
                            <button class="button px-15 py-10 -blue-1 bg-light-2">
                                <i class="icon-heart mr-10"></i>
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="pt-40 js-pin-container">
    <div class="container">
        <div class="row y-gap-30">
            <div class="col-lg-8">
                <div class="row y-gap-30  pt-20">
                    @if ($row->to_country)
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icon-globe text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    {{ __('Country') }}:<br> {{ $row->country }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($row->visaType)
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-beach text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    {{ __('Visa Type') }}:<br>
                                    {{ $row->visaType->name ?? '' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($row->code)
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-code text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    {{ __('Code') }}:<br>
                                    {{ $row->code ?? '' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($row->processing_days)
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-wall-clock text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    {{ __('Processing Days') }}:<br>
                                    {{ $row->processing_days ?? '' }}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($row->max_stay_days)
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-wall-clock text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    {{ __('Max Stay Days') }}:<br>
                                    {{__(':amount day(s)', ['amount' => $row->max_stay_days])}}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($row->multiple_entry)
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-wall-clock text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    {{ __('Multiple Entry') }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="border-top-light mt-40 mb-40"></div>
                @if(!empty($translation->content))
                    <div class="row x-gap-40 y-gap-40 gotrip-overview">
                        <div class="col-12">
                            <h3 class="text-22 fw-500">{{ __('Overview') }}</h3>
                            <div class="text-dark-1 text-15 mt-20 content-text">
                                {!! clean($translation->content) !!}
                            </div>
                            <span class="d-none btn-showmore pointer text-14 text-blue-1 fw-500 underline mt-10">
                                {{ __('Show More') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-lg-4">
                @livewire('visa::booking-form', ['row' => $row], key($row->id))
            </div>
        </div>
    </div>
</section>
