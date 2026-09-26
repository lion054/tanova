@php
    /** @var array $page  built by App\Support\PublicServicePage */
    $p = $page;
    $type = $p['type'];
    $g = $p['gallery'];
    $hasRooms = $type === 'hotel';
    $bookPartial = [
        'tour' => 'Tour::frontend.layouts.details.tour-form-book',
        'space' => 'Space::frontend.layouts.details.space-form-book',
        'car' => 'Car::frontend.layouts.details.form-book',
        'boat' => 'Boat::frontend.layouts.details.form-book',
        'event' => 'Event::frontend.layouts.details.form-book',
    ][$type] ?? null;
    $nav = array_filter([
        'overview' => __('Overview'),
        'highlights' => ($p['include'] || $p['exclude']) ? __('What is included') : null,
        'itinerary' => $p['itinerary'] ? __('Itinerary') : null,
        'rooms' => $hasRooms ? __('Rooms') : null,
        'amenities' => $p['amenities'] ? ($type === 'hotel' ? __('Amenities') : __('Details')) : null,
        'specs' => $p['specs'] ? __('Specifications') : null,
        'policies' => ($p['policy'] || $p['cancel_policy'] || trim(strip_tags($p['terms_information']))) ? __('Policies') : null,
        'faqs' => $p['faqs'] ? __('Questions') : null,
        'location' => ($p['map'] || $p['nearby']) ? __('Location') : null,
        'reviews' => setting_item($type . '_enable_review') ? __('Reviews') : null,
    ]);
    $canReview = Auth::check() && Auth::id() != $row->author_id && setting_item($type . '_enable_review');
@endphp
<div class="pv-wrap">
    {{-- ── Title ─────────────────────────────────────────────── --}}
    <section class="pv-head">
        <div class="pv-crumb">
            <span class="pv-kind">{{ $p['kind'] }}</span>
            @if ($p['location'])<span>{{ $p['location'] }}</span>@endif
        </div>
        <div class="pv-head-row">
            <div>
                <h1 class="pv-title">{{ $p['title'] }}</h1>
                <div class="pv-sub">
                    @if ($p['stars'])
                        <span class="pv-stars" title="{{ $p['stars'] }} {{ __('star') }}">@for ($i = 0; $i < $p['stars']; $i++)<svg viewBox="0 0 24 24"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>@endfor</span>
                    @endif
                    @if ($p['review'])
                        <a class="pv-score" href="#reviews"><b>{{ $p['review']['score'] }}</b> · {{ trans_choice(':n review|:n reviews', $p['review']['count'], ['n' => $p['review']['count']]) }}</a>
                    @endif
                    @if ($p['address'])
                        <span class="pv-addr"><svg viewBox="0 0 24 24" class="pv-i"><path d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>{{ $p['address'] }}</span>
                    @endif
                </div>
            </div>
            <div class="pv-actions">
                <button type="button" class="pv-btn" onclick="pvShare(this)" data-url="{{ $p['url'] }}" data-title="{{ $p['title'] }}">
                    <svg viewBox="0 0 24 24" class="pv-i"><path d="M12 15V4M8 8l4-4 4 4M5 12v7h14v-7"/></svg><span>{{ __('Share') }}</span>
                </button>
            </div>
        </div>
    </section>

    {{-- ── Photos ────────────────────────────────────────────── --}}
    <section class="pv-gallery n{{ min(count($g), 5) }}" aria-label="{{ __('Photos') }}">
        @forelse (array_slice($g, 0, 5) as $i => $img)
            <a href="{{ $img['large'] }}" class="pv-ph pv-ph{{ $i }}" data-i="{{ $i }}" onclick="return pvLightbox({{ $i }})">
                <img src="{{ $i === 0 ? $img['large'] : $img['thumb'] }}" alt="{{ $p['title'] }}" loading="{{ $i ? 'lazy' : 'eager' }}" onerror="pvBroken(this)">
                @if ($i === 4 && count($g) > 5)<span class="pv-more">{{ __('+:n photos', ['n' => count($g) - 5]) }}</span>@endif
            </a>
        @empty
            <div class="pv-ph pv-ph0 pv-noimg"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 18l5-5 4 4 3-3 4 4"/></svg><span>{{ __('Photos coming soon') }}</span></div>
        @endforelse
        @if (count($g) > 1)
            <button type="button" class="pv-allph" onclick="pvLightbox(0)">{{ __('Show all :n photos', ['n' => count($g)]) }}</button>
        @endif
    </section>
    <script>window.pvPhotos = @json(array_column($g, 'large'));</script>

    {{-- ── Facts ─────────────────────────────────────────────── --}}
    @if ($p['facts'])
        <ul class="pv-facts">
            @foreach ($p['facts'] as $f)
                <li><span class="pv-fi">@include('Layout::public.icon', ['n' => $f['icon']])</span><span><small>{{ $f['label'] }}</small><b>{{ $f['value'] }}</b></span></li>
            @endforeach
        </ul>
    @endif

    {{-- ── Section nav ───────────────────────────────────────── --}}
    <nav class="pv-nav" id="pvNav">
        @foreach ($nav as $id => $label)<a href="#{{ $id }}" data-s="{{ $id }}">{{ $label }}</a>@endforeach
    </nav>

    <div class="pv-grid">
        <div class="pv-main">
            <section id="overview" class="pv-sec">
                <h2>{{ \App\Support\PublicServicePage::about($type) }}</h2>
                @if ($p['short'])<p class="pv-lead">{{ $p['short'] }}</p>@endif
                <div class="pv-prose pv-clamp" id="pvProse">{!! clean($p['overview']) !!}</div>
                <button type="button" class="pv-link" id="pvMore" style="display:none" onclick="pvExpand()">{{ __('Read more') }}</button>
            </section>

            @if ($p['include'] || $p['exclude'])
                <section id="highlights" class="pv-sec">
                    <h2>{{ __('What is included') }}</h2>
                    <div class="pv-two">
                        @if ($p['include'])
                            <ul class="pv-ticks">@foreach ($p['include'] as $t)<li><svg viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"/></svg>{{ $t }}</li>@endforeach</ul>
                        @endif
                        @if ($p['exclude'])
                            <div>
                                <h3>{{ __('Not included') }}</h3>
                                <ul class="pv-ticks is-no">@foreach ($p['exclude'] as $t)<li><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>{{ $t }}</li>@endforeach</ul>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if ($p['itinerary'])
                <section id="itinerary" class="pv-sec">
                    <h2>{{ __('Itinerary') }}</h2>
                    <ol class="pv-tl">
                        @foreach ($p['itinerary'] as $i => $day)
                            <li class="{{ $i === 0 ? 'is-open' : '' }}">
                                <button type="button" onclick="this.parentNode.classList.toggle('is-open')">
                                    <span class="pv-dot">{{ $i + 1 }}</span>
                                    <span><b>{{ $day['title'] ?? '' }}</b>@if (!empty($day['desc']))<small>{{ $day['desc'] }}</small>@endif</span>
                                    <svg viewBox="0 0 24 24" class="pv-i pv-chev"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                @if (!empty($day['content']))<div class="pv-tl-body">{!! nl2br(e($day['content'])) !!}</div>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($hasRooms)
                <section id="rooms" class="pv-sec pv-rooms">
                    <h2>{{ __('Rooms and rates') }}</h2>
                    {{-- single-hotel.js mounts its booking app on #hotel-rooms --}}
                    <div id="hotel-rooms">@include('Hotel::frontend.layouts.details.hotel-rooms')</div>
                </section>
            @endif

            @if ($p['amenities'])
                <section id="amenities" class="pv-sec">
                    <h2>{{ $type === 'hotel' ? __('Amenities') : __('Good to know') }}</h2>
                    <div class="pv-amen">
                        @foreach ($p['amenities'] as $grp)
                            <div>
                                <h3>{{ $grp['name'] }}</h3>
                                <ul>@foreach ($grp['items'] as $it)<li>@if ($it['image'])<img src="{{ $it['image'] }}" alt="" onerror="pvBroken(this)">@elseif ($it['icon'])<i class="{{ $it['icon'] }}"></i>@else<svg viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"/></svg>@endif{{ $it['name'] }}</li>@endforeach</ul>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($p['specs'])
                <section id="specs" class="pv-sec">
                    <h2>{{ __('Specifications') }}</h2>
                    <dl class="pv-specs">@foreach ($p['specs'] as $s)<div><dt>{{ $s['title'] }}</dt><dd>{{ $s['content'] ?? '' }}</dd></div>@endforeach</dl>
                </section>
            @endif

            @if ($p['policy'] || $p['cancel_policy'] || trim(strip_tags($p['terms_information'])))
                <section id="policies" class="pv-sec">
                    <h2>{{ __('Policies') }}</h2>
                    <div class="pv-acc">
                        @foreach ($p['policy'] as $pol)
                            <details><summary>{{ $pol['title'] }}<svg viewBox="0 0 24 24" class="pv-i pv-chev"><path d="M6 9l6 6 6-6"/></svg></summary><div>{!! nl2br(e($pol['content'] ?? '')) !!}</div></details>
                        @endforeach
                        @if ($p['cancel_policy'])
                            <details><summary>{{ __('Cancellation') }}<svg viewBox="0 0 24 24" class="pv-i pv-chev"><path d="M6 9l6 6 6-6"/></svg></summary><div>{!! nl2br(e($p['cancel_policy'])) !!}</div></details>
                        @endif
                        @if (trim(strip_tags($p['terms_information'])))
                            <details><summary>{{ __('Terms') }}<svg viewBox="0 0 24 24" class="pv-i pv-chev"><path d="M6 9l6 6 6-6"/></svg></summary><div>{!! clean($p['terms_information']) !!}</div></details>
                        @endif
                    </div>
                </section>
            @endif

            @if ($p['faqs'])
                <section id="faqs" class="pv-sec">
                    <h2>{{ __('Questions and answers') }}</h2>
                    <div class="pv-acc">
                        @foreach ($p['faqs'] as $q)
                            <details><summary>{{ $q['title'] }}<svg viewBox="0 0 24 24" class="pv-i pv-chev"><path d="M6 9l6 6 6-6"/></svg></summary><div>{!! nl2br(e($q['content'] ?? '')) !!}</div></details>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($p['map'] || $p['nearby'])
                <section id="location" class="pv-sec">
                    <h2>{{ __('Where you will be') }}</h2>
                    @if ($p['address'])<p class="pv-addr-line">{{ $p['address'] }}</p>@endif
                    @if ($p['map'])<div id="map_content" class="pv-map"></div>@endif
                    @if ($p['nearby'])
                        <h3 class="pv-h3">{{ __('Nearby') }}</h3>
                        <ul class="pv-nearby">@foreach ($p['nearby'] as $n)<li><b>{{ $n['name'] }}</b>@if ($n['note'])<span>{{ $n['note'] }}</span>@endif</li>@endforeach</ul>
                    @endif
                </section>
            @endif

            @if (setting_item($type . '_enable_review'))
                <section id="reviews" class="pv-sec">
                    <h2>{{ __('Guest reviews') }}</h2>
                    @if ($p['review'])
                        <div class="pv-rate"><span class="pv-rate-n">{{ $p['review']['score'] }}</span><span>{{ trans_choice(':n review|:n reviews', $p['review']['count'], ['n' => $p['review']['count']]) }}</span></div>
                    @endif
                    @if (!empty($review_list) && count($review_list))
                        <div class="pv-reviews">
                            @foreach ($review_list as $rv)
                                @php $who = $rv->author; @endphp
                                <article>
                                    <div class="pv-rv-h">
                                        <span class="pv-av">{{ $who ? mb_strtoupper(mb_substr($who->getDisplayName(), 0, 1)) : '?' }}</span>
                                        <span><b>{{ $who ? $who->getDisplayName() : __('Guest') }}</b><small>{{ display_date($rv->created_at) }}</small></span>
                                        @if ($rv->rate_number)<span class="pv-stars pv-rv-s">@for ($i = 0; $i < (int) $rv->rate_number; $i++)<svg viewBox="0 0 24 24"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>@endfor</span>@endif
                                    </div>
                                    @if ($rv->title)<h4>{{ $rv->title }}</h4>@endif
                                    <p>{{ $rv->content }}</p>
                                </article>
                            @endforeach
                        </div>
                        @if (method_exists($review_list, 'links') && $review_list->total() > $review_list->perPage())
                            <div class="pv-pager">{{ $review_list->appends(request()->query())->fragment('reviews')->links() }}</div>
                        @endif
                    @else
                        <p class="pv-muted">{{ __('No reviews yet.') }}</p>
                    @endif

                    @if ($canReview)
                        <form class="pv-review-form" action="{{ route('review.store') }}" method="post">
                            @csrf
                            <h3>{{ __('Share your experience') }}</h3>
                            @include('admin.message')
                            @php $stats = json_decode((string) setting_item($type . '_review_stats')) ?: []; @endphp
                            @if ($stats)
                                <div class="pv-rate-in review-items">
                                    @foreach ($stats as $item)
                                        <div class="item"><span>{{ $item->title }}</span>
                                            <input class="review_stats" type="hidden" name="review_stats[{{ $item->title }}]">
                                            <div class="rates d-flex x-gap-5 items-center"><i class="fa fa-star-o grey"></i><i class="fa fa-star-o grey"></i><i class="fa fa-star-o grey"></i><i class="fa fa-star-o grey"></i><i class="fa fa-star-o grey"></i></div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <input type="text" name="review_title" required placeholder="{{ __('Title') }}">
                            <textarea name="review_content" required rows="4" minlength="10" placeholder="{{ __('Write your comment') }}"></textarea>
                            <input type="hidden" name="review_service_id" value="{{ $row->id }}"><input type="hidden" name="review_service_type" value="{{ $type }}">
                            <button type="submit" class="pv-cta">{{ __('Post review') }}</button>
                        </form>
                    @endif
                </section>
            @endif
        </div>

        {{-- ── Booking card ───────────────────────────────────── --}}
        <aside class="pv-side">
            <div class="pv-book" id="book">
                @if ($hasRooms)
                    <div class="pv-book-price">
                        <small>{{ __('From') }}</small>
                        @if ($p['sale_price'] && $p['sale_price'] !== $p['price'])<s>{{ $p['sale_price'] }}</s>@endif
                        <b>{{ $p['price'] }}</b><small>{{ __('per night') }}</small>
                    </div>
                    <a href="#rooms" class="pv-cta" onclick="document.getElementById('rooms').scrollIntoView({behavior:'smooth'});return false;">{{ __('Check availability') }}</a>
                    <p class="pv-book-note">{{ __('Pick your dates and room below. You will not be charged yet.') }}</p>
                @elseif ($bookPartial)
                    @include($bookPartial)
                @endif
                @if ($p['offered_by'])
                    <div class="pv-by"><span class="pv-by-i">{{ mb_strtoupper(mb_substr($p['offered_by'], 0, 1)) }}</span><span><small>{{ __('Offered by') }}</small><b>{{ $p['offered_by'] }}</b></span></div>
                @endif
            </div>
        </aside>
    </div>

    @if ($p['related'])
        <section class="pv-related">
            <h2>{{ __('You might also like') }}</h2>
            <div class="pv-cards">
                @foreach ($p['related'] as $c)
                    <a class="pv-card" href="{{ $c['url'] }}">
                        <span class="pv-card-img">@if ($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy" onerror="pvBroken(this)">@endif</span>
                        <b>{{ $c['title'] }}</b>
                        @if ($c['place'])<small>{{ $c['place'] }}</small>@endif
                        @if ($c['price'])<span class="pv-card-p">{{ __('From') }} <b>{{ $c['price'] }}</b></span>@endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

{{-- Phones: price and one button, always in reach --}}
<div class="pv-bar">
    <div><small>{{ __('From') }}</small><b>{{ $p['price'] }}</b></div>
    <a class="pv-cta" href="#{{ $hasRooms ? 'rooms' : 'book' }}" onclick="document.getElementById('{{ $hasRooms ? 'rooms' : 'book' }}').scrollIntoView({behavior:'smooth'});return false;">{{ $p['book_mode'] === 'book' ? ($hasRooms ? __('Check availability') : __('Book now')) : __('Enquire') }}</a>
</div>

<div class="pv-lb" id="pvLb" hidden onclick="if(event.target===this)pvClose()">
    <button type="button" class="pv-lb-x" onclick="pvClose()" aria-label="{{ __('Close') }}">×</button>
    <button type="button" class="pv-lb-p" onclick="pvStep(-1)" aria-label="{{ __('Previous') }}">‹</button>
    <img id="pvLbImg" alt="">
    <button type="button" class="pv-lb-n" onclick="pvStep(1)" aria-label="{{ __('Next') }}">›</button>
    <span class="pv-lb-c" id="pvLbC"></span>
</div>
