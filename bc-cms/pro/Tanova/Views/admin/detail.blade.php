@extends('layouts.user')

@push('css')
<style>
.tp-ab-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; transition:all .12s; white-space:nowrap; }
.tp-ab-btn--primary { background:#0a0a0a; color:#fff !important; }
.tp-ab-btn--primary:hover { background:#333; }
.tp-ab-btn--outline { background:#fff; border:1.5px solid #e0e0e0; color:#0a0a0a !important; }
.tp-ab-btn--outline:hover { border-color:#0a0a0a; }
.tp-ab-btn--ghost  { background:transparent; color:#555 !important; border:1.5px solid #e4e4e4; }
.tp-ab-btn--ghost:hover  { border-color:#0a0a0a; color:#0a0a0a !important; }

.tp-card { background:#fff; border:1px solid #ebebeb; border-radius:10px; overflow:hidden; }
.tp-card-header { padding:14px 20px; border-bottom:1px solid #f0f0f0; font-size:13px; font-weight:700; color:#0a0a0a; display:flex; align-items:center; gap:8px; }
.tp-card-body { padding:20px; }

.tp-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:100px; font-size:11px; font-weight:600; }
.tp-badge::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.tp-badge--green { background:#f0fdf4; color:#16a34a; } .tp-badge--green::before { background:#16a34a; }
.tp-badge--amber { background:#fffbeb; color:#d97706; } .tp-badge--amber::before { background:#d97706; }
.tp-badge--blue  { background:#eff6ff; color:#2563eb; } .tp-badge--blue::before  { background:#2563eb; }
.tp-badge--gray  { background:#f4f4f5; color:#71717a; } .tp-badge--gray::before  { background:#71717a; }

/* Package tabs */
.tn-pkg-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.tn-pkg-tab { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:100px; border:1.5px solid #e4e4e4; font-size:12px; font-weight:600; color:#555; background:#fff; cursor:pointer; transition:all .12s; }
.tn-pkg-tab:hover { border-color:#0a0a0a; color:#0a0a0a; }
.tn-pkg-tab.active { background:#0a0a0a; border-color:#0a0a0a; color:#fff; }
.tn-pkg-tab .cost { font-size:10px; opacity:.75; }
.tn-pkg-pane { display:none; }
.tn-pkg-pane.active { display:block; }

/* Summary table */
.tn-summary { width:100%; border-collapse:collapse; font-size:13px; }
.tn-summary td { padding:9px 0; border-bottom:1px solid #f7f7f7; vertical-align:top; }
.tn-summary td:first-child { color:#aaa; width:40%; font-size:12px; }
.tn-summary td:last-child  { font-weight:500; color:#0a0a0a; }
.tn-summary tr:last-child td { border-bottom:none; }

/* Day card */
.tn-day { margin-bottom:20px; }
.tn-day-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.tn-day-num { width:32px; height:32px; background:#0a0a0a; color:#fff; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; flex-shrink:0; }
.tn-day-title { font-size:14px; font-weight:700; color:#0a0a0a; }
.tn-day-date  { font-size:12px; color:#aaa; }
.tn-day-weather { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#444; background:#f0f5ff; border:1px solid #dbe8ff; border-radius:100px; padding:2px 9px; margin-top:3px; }

/* Activity row */
.tn-activity { display:flex; gap:10px; padding:10px 0; border-bottom:1px solid #f7f7f7; align-items:flex-start; }
.tn-activity:last-child { border-bottom:none; }
.tn-activity__time { font-size:12px; color:#aaa; min-width:46px; padding-top:2px; flex-shrink:0; }
.tn-activity__body { flex:1; min-width:0; }
.tn-activity__name { font-size:13px; font-weight:600; color:#0a0a0a; }
.tn-activity__desc { font-size:12px; color:#888; margin-top:2px; line-height:1.4; }
.tn-activity__incl { display:inline-block; font-size:10px; font-weight:700; background:#f0fdf4; color:#16a34a; padding:1px 6px; border-radius:4px; margin-left:6px; }
.tn-activity__excl { display:inline-block; font-size:10px; font-weight:700; background:#fff7ed; color:#c2410c; padding:1px 6px; border-radius:4px; margin-left:6px; }
.tn-activity__cost { font-size:11px; color:#aaa; margin-top:2px; }

/* Edit controls */
.tn-act-controls { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.tn-act-pax-wrap { display:flex; align-items:center; gap:4px; }
.tn-act-pax-wrap label { font-size:10px; font-weight:700; text-transform:uppercase; color:#bbb; letter-spacing:.05em; }
.tn-act-pax { width:46px; border:1.5px solid #e4e4e4; border-radius:6px; padding:4px 6px; font-size:12px; font-weight:600; text-align:center; outline:none; color:#0a0a0a; }
.tn-act-pax:focus { border-color:#0a0a0a; }
.tn-act-remove { background:none; border:none; cursor:pointer; color:#ccc; font-size:15px; line-height:1; padding:3px 5px; border-radius:4px; transition:all .12s; }
.tn-act-remove:hover { color:#e11d48; background:#fff1f2; }

/* Add activity button */
.tn-add-act-btn { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:8px 12px; margin-top:10px; border:1.5px dashed #e4e4e4; border-radius:8px; background:transparent; color:#bbb; font-size:12px; font-weight:600; cursor:pointer; transition:all .12s; }
.tn-add-act-btn:hover { border-color:#2563eb; color:#2563eb; background:#eff6ff; }

/* Stay / meals */
.tn-stay { display:flex; align-items:center; gap:8px; padding:10px 14px; background:#fafafa; border-radius:8px; margin-top:10px; font-size:12px; color:#555; }
.tn-stay i { color:#aaa; }
.tn-meals { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
.tn-meal { font-size:10px; font-weight:700; background:#f0f0f0; color:#555; padding:2px 8px; border-radius:4px; }

/* Price card */
.price-pill { display:inline-flex; align-items:baseline; gap:4px; background:#0a0a0a; color:#fff; border-radius:8px; padding:8px 14px; font-size:22px; font-weight:800; letter-spacing:-.03em; }
.price-pill span { font-size:12px; font-weight:400; opacity:.6; }

/* tp-field */
.tp-field { margin-bottom:14px; }
.tp-field label { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#777; margin-bottom:4px; }
.tp-field select, .tp-field input { width:100%; border:1.5px solid #e8e8e8; border-radius:7px; padding:8px 12px; font-size:13px; color:#222; background:#fff; outline:none; transition:border-color .12s; }
.tp-field select:focus, .tp-field input:focus { border-color:#0a0a0a; }

/* Save bar */
#tn-save-bar { background:#0a0a0a; color:#fff; border-radius:10px; padding:12px 16px; display:none; align-items:center; gap:10px; margin-top:14px; box-shadow:0 4px 20px rgba(0,0,0,.2); }
.tn-save-msg { font-size:12px; color:rgba(255,255,255,.65); flex:1; }
#tn-save-btn { background:#fff; color:#0a0a0a; border:none; border-radius:6px; padding:7px 16px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; }
#tn-save-btn:hover { background:#f0f0f0; }
#tn-save-btn:disabled { opacity:.5; cursor:default; }
#tn-saved-flash { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; border-radius:8px; padding:9px 14px; font-size:12px; font-weight:600; margin-top:10px; text-align:center; display:none; }

/* Add activity modal */
.tn-add-filter { padding:4px 12px; border-radius:100px; border:1.5px solid #e4e4e4; background:#fff; font-size:11px; font-weight:600; color:#555; cursor:pointer; transition:all .12s; }
.tn-add-filter:hover { border-color:#0a0a0a; color:#0a0a0a; }
.tn-add-filter.active { background:#0a0a0a; border-color:#0a0a0a; color:#fff; }
.tn-result-item { display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-bottom:1px solid #f7f7f7; }
.tn-result-item:last-child { border-bottom:none; }

.portal-eyebrow { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#aaa; margin-bottom:4px; }
.portal-h1 { font-size:22px; font-weight:800; color:#0a0a0a; letter-spacing:-.03em; margin:0 0 2px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <nav class="mb-3"><ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.tanova.index') }}">Tanova</a></li>
        <li class="breadcrumb-item active">{{ Str::limit($trip->title, 40) }}</li>
    </ol></nav>

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="portal-eyebrow">Tanova Itinerary</p>
            <h1 class="portal-h1">{{ $trip->title }}</h1>
            <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap">
                @if($trip->status === 'created')
                    <span class="tp-badge tp-badge--amber">Awaiting Action</span>
                @elseif($trip->status === 'booked')
                    <span class="tp-badge tp-badge--green">Booked</span>
                @else
                    <span class="tp-badge tp-badge--gray">{{ ucfirst($trip->status) }}</span>
                @endif
                <span style="font-size:12px;color:#aaa">Generated {{ $trip->created_at?->diffForHumans() }}</span>
            </div>
        </div>
        <a href="{{ route('admin.tanova.index') }}" class="tp-ab-btn tp-ab-btn--ghost">
            <i class="ion ion-ios-arrow-back"></i> Back
        </a>
    </div>

    @include('admin.message')

    @php
        $packages = $trip->itinerary ?? [];
        $isMulti  = !empty($packages) && isset($packages[0]['package']);
        if (!$isMulti && !empty($packages)) {
            $packages = [['package' => 1, 'itinerary' => $packages, 'total_cost' => $trip->estimated_price, 'stay_cost' => 0, 'activity_cost' => $trip->estimated_price, 'hotel' => null, 'price_per_person' => $trip->guests > 0 ? $trip->estimated_price / $trip->guests : 0]];
        }
        $canEdit = $trip->isMovable();
    @endphp

    <div class="row g-4">

        {{-- Left — packages + itinerary --}}
        <div class="col-md-8">

            {{-- Package selector tabs --}}
            @if(count($packages) > 1)
            <div class="tn-pkg-tabs" id="tn-tabs">
                @foreach($packages as $pkg)
                <button class="tn-pkg-tab {{ $loop->first ? 'active' : '' }}"
                        data-pkg="{{ $pkg['package'] }}">
                    Package {{ $pkg['package'] }}
                    <span class="cost">USD {{ number_format($pkg['total_cost'] ?? 0, 0) }}</span>
                </button>
                @endforeach
            </div>
            @endif

            @foreach($packages as $pkg)
            <div class="tn-pkg-pane {{ $loop->first ? 'active' : '' }}" id="pkg-{{ $pkg['package'] }}">

                {{-- Hotel summary strip --}}
                @if(!empty($pkg['hotel']))
                <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;background:#fafafa;border:1px solid #ebebeb;border-radius:8px;margin-bottom:16px;font-size:12px;">
                    <i class="ion ion-ios-bed" style="color:#aaa"></i>
                    <span style="font-weight:600;color:#0a0a0a">{{ $pkg['hotel']['name'] }}</span>
                    <span style="color:#aaa">· {{ $pkg['hotel']['type'] ?? '' }}</span>
                    <span style="color:#aaa">· {{ $pkg['hotel']['rooms'] ?? 1 }} room(s) @ USD {{ number_format($pkg['hotel']['cost_per_night'] ?? 0, 0) }}/night</span>
                    <span style="margin-left:auto;font-weight:700;color:#0a0a0a">Stay: USD {{ number_format($pkg['stay_cost'] ?? 0, 0) }}</span>
                </div>
                @endif

                <div class="tp-card">
                    <div class="tp-card-header">
                        <i class="ion ion-ios-calendar" style="color:#2563eb"></i>
                        Itinerary — {{ $trip->nightCount() }} nights
                        <span style="margin-left:auto;font-size:12px;color:#aaa;font-weight:400" id="act-cost-{{ $pkg['package'] }}">
                            Activities: USD {{ number_format($pkg['activity_cost'] ?? 0, 0) }}
                        </span>
                    </div>
                    <div class="tp-card-body">
                        @forelse($pkg['itinerary'] ?? [] as $day)
                        <div class="tn-day">
                            <div class="tn-day-header">
                                <div class="tn-day-num">{{ $day['day'] }}</div>
                                <div>
                                    <div class="tn-day-title">{{ $day['title'] ?? 'Day '.$day['day'] }}</div>
                                    @if(!empty($day['date']))<div class="tn-day-date">{{ $day['date'] }}</div>@endif
                                    @if(!empty($day['weather']))
                                    <div class="tn-day-weather">
                                        {{ $day['weather']['icon'] ?? '' }}
                                        {{ $day['weather']['temp_min'] ?? '?' }}–{{ $day['weather']['temp_max'] ?? '?' }}°C
                                        <span style="color:#aaa;margin-left:2px">· {{ str_replace('_', ' ', $day['weather']['condition'] ?? '') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Activities — JS renders into this container --}}
                            <div class="tn-day-activities" id="day-acts-{{ $pkg['package'] }}-{{ $day['day'] }}">
                                {{-- populated by JS on load --}}
                            </div>

                            @if(!empty($day['accommodation']))
                            <div class="tn-stay">
                                <i class="ion ion-ios-bed"></i>
                                {{ $day['accommodation']['name'] ?? '' }}
                                @if(!empty($day['accommodation']['type'])) · {{ $day['accommodation']['type'] }} @endif
                            </div>
                            @endif

                            @if(!empty($day['meals']))
                            <div class="tn-meals">
                                @if($day['meals']['breakfast'] ?? false)<span class="tn-meal" title="Own expense — not included in package">Breakfast ⁽*⁾</span>@endif
                                @if($day['meals']['lunch']     ?? false)<span class="tn-meal">Lunch</span>@endif
                                @if($day['meals']['dinner']    ?? false)<span class="tn-meal" title="Own expense — not included in package">Dinner ⁽*⁾</span>@endif
                            </div>
                            @endif

                            @if($canEdit)
                            <button class="tn-add-act-btn"
                                    data-pkg="{{ $pkg['package'] }}"
                                    data-day="{{ $day['day'] }}">
                                <i class="ion ion-ios-add-circle"></i> Add Activity to Day {{ $day['day'] }}
                            </button>
                            @endif

                            @if(!$loop->last)<hr style="margin:16px 0;border-color:#f0f0f0">@endif
                        </div>
                        @empty
                        <p style="color:#aaa;font-size:13px;text-align:center;padding:40px 0">No itinerary data.</p>
                        @endforelse
                    </div>
                </div>

            </div>{{-- /pkg-pane --}}
            @endforeach

        </div>

        {{-- Right — Summary + Book action --}}
        <div class="col-md-4">

            {{-- Price display (updates live) --}}
            <div class="tp-card" style="margin-bottom:14px">
                <div class="tp-card-body" style="text-align:center;padding:20px">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aaa;margin-bottom:10px">Total Cost</div>
                    <div class="price-pill" id="tn-price-display">
                        {{ number_format($packages[0]['total_cost'] ?? $trip->estimated_price ?? 0, 0) }}
                        <span>USD</span>
                    </div>
                    <div style="font-size:12px;color:#aaa;margin-top:8px">
                        <span id="tn-ppp">
                        @php
                            $firstPkg = $packages[0] ?? [];
                            $ppp = $firstPkg['price_per_person'] ?? ($trip->guests > 0 && isset($firstPkg['total_cost']) ? round($firstPkg['total_cost'] / $trip->guests, 0) : 0);
                        @endphp
                        USD {{ number_format($ppp, 0) }} per person
                        </span>
                        · {{ $trip->guests }} guests · {{ $trip->nightCount() }} nights
                    </div>
                </div>
            </div>

            {{-- Trip summary --}}
            <div class="tp-card" style="margin-bottom:14px">
                <div class="tp-card-header"><i class="ion ion-ios-information-circle"></i> Trip Summary</div>
                <div class="tp-card-body" style="padding:16px 20px">
                    @php
                        $bk = $trip->booking_id ? $trip->booking : null;
                        $displayName  = $trip->guest_name  ?? ($bk ? trim($bk->first_name.' '.$bk->last_name) : null);
                        $displayEmail = $trip->guest_email ?? $bk?->email;
                        $displayPhone = $trip->guest_phone ?? $bk?->phone;
                    @endphp
                    <table class="tn-summary">
                        @if($displayName)
                        <tr><td>Guest</td><td style="font-weight:600">{{ $displayName }}</td></tr>
                        @endif
                        @if($displayEmail)
                        <tr><td>Email</td><td><a href="mailto:{{ $displayEmail }}" style="color:#2563eb;text-decoration:none">{{ $displayEmail }}</a></td></tr>
                        @endif
                        @if($displayPhone)
                        <tr><td>Phone</td><td><a href="tel:{{ $displayPhone }}" style="color:#0a0a0a;text-decoration:none">{{ $displayPhone }}</a></td></tr>
                        @endif
                        <tr><td>Destination</td><td>{{ $trip->destination }}</td></tr>
                        <tr><td>Dates</td><td>{{ $trip->start_date?->format('d M Y') }} → {{ $trip->end_date?->format('d M Y') }}</td></tr>
                        <tr><td>Nights</td><td>{{ $trip->nightCount() }}</td></tr>
                        <tr><td>Guests</td><td>{{ $trip->guests }}</td></tr>
                        <tr><td>Packages</td><td>{{ count($packages) }}</td></tr>
                        <tr><td>Package</td><td style="font-weight:700" id="tn-summary-pkg">
                            @if($trip->booked_package) Package {{ $trip->booked_package }} @else Package 1 @endif
                        </td></tr>
                        <tr><td>Status</td><td>
                            @if($trip->status === 'created')
                                <span class="tp-badge tp-badge--amber">Awaiting Action</span>
                            @elseif($trip->status === 'booked')
                                <span class="tp-badge tp-badge--green">Booked</span>
                            @else
                                <span class="tp-badge tp-badge--gray">{{ ucfirst($trip->status) }}</span>
                            @endif
                        </td></tr>
                        @if($trip->booking_id)
                        <tr><td>Booking</td><td>
                            <a href="{{ route('booking.admin.edit', ['id' => $trip->booking_id]) }}" style="color:#2563eb;font-weight:600">#{{ $trip->booking_id }}</a>
                        </td></tr>
                        @endif
                    </table>

                    {{-- Invoice action --}}
                    @php
                        $existingInvoice = \Modules\TourPay\Models\Invoice::where('tanova_trip_id', $trip->id)
                            ->where('author_id', Auth::id())
                            ->first();
                    @endphp
                    @if($existingInvoice)
                        <a href="{{ route('tourpay.vendor.edit', $existingInvoice->id) }}"
                           style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;padding:8px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:12px;font-weight:600;color:#16a34a;text-decoration:none">
                            <i class="ion ion-ios-document" style="font-size:15px"></i>
                            View Invoice {{ $existingInvoice->invoice_number }}
                        </a>
                    @else
                        <form method="POST" action="{{ route('admin.tanova.createInvoice', $trip) }}" style="margin-top:14px">
                            @csrf
                            <input type="hidden" name="package" id="tn-invoice-pkg"
                                   value="{{ $trip->booked_package ?? 1 }}">
                            <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#0a0a0a;border:none;border-radius:6px;font-size:12px;font-weight:600;color:#fff;cursor:pointer">
                                <i class="ion ion-ios-document" style="font-size:15px"></i>
                                Create Invoice
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Save bar (shown when dirty) --}}
            @if($canEdit)
            <div id="tn-save-bar" style="display:none">
                <div class="tn-save-msg"><i class="ion ion-ios-alert" style="margin-right:4px"></i> Unsaved changes</div>
                <button id="tn-save-btn"><i class="ion ion-ios-save"></i> Save Itinerary</button>
            </div>
            <div id="tn-saved-flash">✓ Itinerary saved successfully</div>
            @endif

            {{-- Book action --}}
            @if($canEdit)
            <div class="tp-card" id="tn-book-card" style="margin-top:14px">
                <div class="tp-card-header">
                    <i class="ion ion-ios-checkmark-circle" style="color:#16a34a"></i> Book Selected Package
                </div>
                <div class="tp-card-body" style="padding:16px 20px">
                    <div style="display:flex;align-items:center;gap:10px;background:#f8f9fa;border:1px solid #ebebeb;border-radius:8px;padding:10px 14px;margin-bottom:14px">
                        <div style="width:32px;height:32px;background:#0a0a0a;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;flex-shrink:0" id="tn-sel-num">1</div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#aaa">Selected</div>
                            <div style="font-size:13px;font-weight:700;color:#0a0a0a" id="tn-sel-label">Package 1 — USD {{ number_format($packages[0]['total_cost'] ?? 0, 0) }}</div>
                        </div>
                    </div>
                    <p style="font-size:12px;color:#888;margin-bottom:14px;line-height:1.5">
                        Switch package tabs to choose, edit activities, save, then book.
                    </p>
                    <select id="tn-book-pkg" style="display:none">
                        @foreach($packages as $pkg)
                        <option value="{{ $pkg['package'] }}">Package {{ $pkg['package'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="tn-open-modal"
                            class="tp-ab-btn tp-ab-btn--primary" style="width:100%;justify-content:center">
                        <i class="ion ion-ios-checkmark-circle"></i>
                        <span id="tn-book-btn-label">Book Package 1</span>
                    </button>
                </div>
            </div>
            @endif

            {{-- Guest booking modal --}}
            <div id="tn-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center">
                <div style="background:#fff;border-radius:12px;width:100%;max-width:460px;margin:20px;box-shadow:0 24px 64px rgba(0,0,0,.18);overflow:hidden">
                    <div style="padding:18px 22px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between">
                        <div style="font-size:14px;font-weight:700;color:#0a0a0a">
                            <i class="ion ion-ios-person-add" style="color:#2563eb;margin-right:6px"></i>
                            Guest Details
                        </div>
                        <button id="tn-modal-close" type="button" style="background:none;border:none;font-size:20px;color:#aaa;cursor:pointer;line-height:1">&times;</button>
                    </div>
                    <div style="padding:20px 22px">
                        <form method="POST" action="{{ route('admin.tanova.move', $trip) }}" id="tn-book-form">
                            @csrf
                            <input type="hidden" name="package" id="tn-modal-pkg">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="tp-field">
                                        <label>First Name <span style="color:#e11d48">*</span></label>
                                        @php $guestParts = explode(' ', $trip->guest_name ?? $trip->user?->name ?? '', 2); @endphp
                                        <input type="text" name="first_name" id="tn-first" required
                                               value="{{ $trip->user?->first_name ?? $guestParts[0] ?? '' }}"
                                               placeholder="First name">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="tp-field">
                                        <label>Last Name</label>
                                        <input type="text" name="last_name" id="tn-last"
                                               value="{{ $trip->user?->last_name ?? ($guestParts[1] ?? '') }}"
                                               placeholder="Last name">
                                    </div>
                                </div>
                            </div>
                            <div class="tp-field">
                                <label>Email <span style="color:#e11d48">*</span></label>
                                <input type="email" name="email" id="tn-email" required
                                       value="{{ $trip->guest_email ?? $trip->user?->email ?? '' }}"
                                       placeholder="guest@example.com">
                            </div>
                            <div class="tp-field">
                                <label>Phone</label>
                                <input type="text" name="phone" id="tn-phone"
                                       value="{{ $trip->guest_phone ?? $trip->user?->phone ?? '' }}"
                                       placeholder="+1 555 000 0000">
                            </div>
                            <div class="tp-field" style="margin-bottom:0">
                                <label>Notes</label>
                                <input type="text" name="customer_notes" placeholder="Any special requests…">
                            </div>
                            <div style="margin-top:18px;display:flex;gap:8px">
                                <button type="submit" class="tp-ab-btn tp-ab-btn--primary" style="flex:1;justify-content:center">
                                    <i class="ion ion-ios-checkmark-circle"></i> Confirm Booking
                                </button>
                                <button type="button" id="tn-modal-cancel" class="tp-ab-btn tp-ab-btn--ghost">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Add Activity Modal --}}
<div id="tn-add-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:540px;margin:20px;box-shadow:0 24px 64px rgba(0,0,0,.18);display:flex;flex-direction:column;max-height:90vh;overflow:hidden">
        {{-- Modal header --}}
        <div style="padding:16px 22px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
            <div style="font-size:14px;font-weight:700;color:#0a0a0a" id="tn-add-modal-title">
                <i class="ion ion-ios-add-circle" style="color:#2563eb;margin-right:6px"></i>
                Add Activity
            </div>
            <button id="tn-add-close" type="button" style="background:none;border:none;font-size:20px;color:#aaa;cursor:pointer;line-height:1">&times;</button>
        </div>

        {{-- Search + filters --}}
        <div style="padding:14px 22px;border-bottom:1px solid #f0f0f0;flex-shrink:0">
            <input type="text" id="tn-add-search"
                   style="width:100%;border:1.5px solid #e4e4e4;border-radius:8px;padding:9px 12px;font-size:13px;color:#222;outline:none;transition:border-color .12s"
                   placeholder="Search tours and restaurants for this destination…">
            <div style="display:flex;gap:6px;margin-top:10px">
                <button class="tn-add-filter active" data-filter="all">All</button>
                <button class="tn-add-filter" data-filter="tour">Tours &amp; Activities</button>
            </div>
        </div>

        {{-- Results --}}
        <div id="tn-add-results" style="flex:1;overflow-y:auto;padding:0 22px;min-height:120px">
            <p style="color:#aaa;font-size:12px;text-align:center;padding:30px 0">Start typing to search activities for this destination.</p>
        </div>

        {{-- Time + Pax picker --}}
        <div style="padding:14px 22px;border-top:1px solid #f0f0f0;background:#fafafa;flex-shrink:0">
            <div style="display:flex;gap:12px;align-items:flex-end">
                <div style="flex:1">
                    <label style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#777;margin-bottom:4px">Time Slot</label>
                    <select id="tn-add-time" style="width:100%;border:1.5px solid #e4e4e4;border-radius:7px;padding:8px 10px;font-size:13px;color:#222;background:#fff;outline:none">
                        <option value="07:30">07:30 — Breakfast</option>
                        <option value="08:00">08:00 — Morning</option>
                        <option value="09:00" selected>09:00 — Morning</option>
                        <option value="10:00">10:00 — Morning</option>
                        <option value="11:00">11:00 — Late Morning</option>
                        <option value="13:00">13:00 — Lunch</option>
                        <option value="14:00">14:00 — Afternoon</option>
                        <option value="15:00">15:00 — Afternoon</option>
                        <option value="16:00">16:00 — Late Afternoon</option>
                        <option value="19:00">19:00 — Evening</option>
                        <option value="19:30">19:30 — Dinner</option>
                        <option value="20:00">20:00 — Dinner</option>
                    </select>
                </div>
                <div style="width:80px">
                    <label style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#777;margin-bottom:4px">Pax</label>
                    <input type="number" id="tn-add-pax" min="1" max="50" value="{{ $trip->guests }}"
                           style="width:100%;border:1.5px solid #e4e4e4;border-radius:7px;padding:8px 10px;font-size:13px;color:#222;background:#fff;outline:none;text-align:center">
                </div>
            </div>
            <p style="font-size:11px;color:#aaa;margin-top:8px;margin-bottom:0">Click an activity in the results above, then confirm the time and pax below.</p>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
(function(){
    // ── State ─────────────────────────────────────────────────────────────────
    var pkgState  = @json(collect($packages)->keyBy('package')->toArray());
    var guests    = {{ (int) $trip->guests }};
    var activePkg = 1;
    var dirty     = false;
    var canEdit   = {{ $canEdit ? 'true' : 'false' }};

    var saveUrlTpl  = '{{ route('admin.tanova.savePackage', [$trip, '__PKG__']) }}';
    var searchUrl   = '{{ route('admin.tanova.activities', $trip) }}';
    var csrfToken   = '{{ csrf_token() }}';

    // ── Utilities ─────────────────────────────────────────────────────────────
    function esc(s) {
        return String(s||'')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmt(n) {
        return Math.round(n||0).toLocaleString('en-US', {maximumFractionDigits:0});
    }

    // Ensure all activities have pax defaulting to guests
    Object.keys(pkgState).forEach(function(pkgNum) {
        (pkgState[pkgNum].itinerary || []).forEach(function(day) {
            (day.activities || []).forEach(function(act) {
                if (!act.pax) act.pax = guests;
            });
        });
    });

    // ── Render activities for a day ───────────────────────────────────────────
    function renderActivitiesForDay(pkgNum, dayNum) {
        var container = document.getElementById('day-acts-' + pkgNum + '-' + dayNum);
        if (!container) return;

        var pkg = pkgState[pkgNum];
        if (!pkg) return;
        var day = (pkg.itinerary || []).find(function(d){ return d.day == dayNum; });
        if (!day) return;

        var acts = day.activities || [];
        if (!acts.length) {
            container.innerHTML = '<p style="color:#bbb;font-size:12px;padding:8px 0;text-align:center">No activities. Use the button below to add one.</p>';
            bindDayEvents(container, pkgNum, dayNum);
            return;
        }

        var html = '';
        acts.forEach(function(act, idx) {
            var isRestaurant = act.type === 'Restaurant' || /^(Breakfast|Dinner) at /i.test(act.name || '');
            var incl = isRestaurant
                ? '<span class="tn-activity__excl">Excl.</span>'
                : (act.included ? '<span class="tn-activity__incl">Incl.</span>' : '');
            var cost = (!act.included && act.cost > 0)
                ? '<div class="tn-activity__cost">Est. USD ' + fmt(act.cost) + ' p.p.' + (canEdit && !isRestaurant ? ' &times; ' + (act.pax||guests) + ' pax = USD ' + fmt(act.cost * (act.pax||guests)) : '') + '</div>'
                : '';
            var desc = act.description
                ? '<div class="tn-activity__desc">' + esc(String(act.description).substring(0,120)) + (act.description.length > 120 ? '…' : '') + '</div>'
                : '';

            var controls = '';
            if (canEdit) {
                var paxCtrl = !act.included
                    ? '<div class="tn-act-pax-wrap"><label>Pax</label><input type="number" class="tn-act-pax" min="1" max="50" value="' + (act.pax||guests) + '" data-pkg="' + pkgNum + '" data-day="' + dayNum + '" data-idx="' + idx + '"></div>'
                    : '';
                controls = '<div class="tn-act-controls">'
                    + paxCtrl
                    + '<button class="tn-act-remove" data-pkg="' + pkgNum + '" data-day="' + dayNum + '" data-idx="' + idx + '" title="Remove activity">&#10005;</button>'
                    + '</div>';
            }

            var duration = act.duration ? '<span style="font-size:11px;color:#aaa;margin-left:4px">(' + act.duration + 'h)</span>' : '';
            html += '<div class="tn-activity">'
                + '<div class="tn-activity__time">' + esc(act.time||'') + '</div>'
                + '<div class="tn-activity__body">'
                    + '<div class="tn-activity__name">' + esc(act.name||'') + duration + incl + '</div>'
                    + desc + cost
                + '</div>'
                + controls
                + '</div>';
        });

        container.innerHTML = html;
        bindDayEvents(container, pkgNum, dayNum);
    }

    function bindDayEvents(container, pkgNum, dayNum) {
        container.querySelectorAll('.tn-act-pax').forEach(function(input) {
            input.addEventListener('change', function() {
                var p = +this.dataset.pkg, d = +this.dataset.day, i = +this.dataset.idx;
                var v = Math.max(1, Math.min(50, parseInt(this.value)||1));
                this.value = v;
                var day = pkgState[p].itinerary.find(function(x){ return x.day == d; });
                if (day && day.activities[i]) day.activities[i].pax = v;
                recalcAndUpdate(p);
                markDirty();
            });
        });

        container.querySelectorAll('.tn-act-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var p = +this.dataset.pkg, d = +this.dataset.day, i = +this.dataset.idx;
                var day = pkgState[p].itinerary.find(function(x){ return x.day == d; });
                if (day) day.activities.splice(i, 1);
                renderActivitiesForDay(p, d);
                recalcAndUpdate(p);
                markDirty();
            });
        });
    }

    // Render all packages on load
    function renderAll() {
        Object.keys(pkgState).forEach(function(pkgNum) {
            (pkgState[pkgNum].itinerary || []).forEach(function(day) {
                renderActivitiesForDay(parseInt(pkgNum), day.day);
            });
        });
    }
    renderAll();

    // ── Cost recalculation ────────────────────────────────────────────────────
    function recalcAndUpdate(pkgNum) {
        var pkg = pkgState[pkgNum];
        var actCost = 0;
        (pkg.itinerary || []).forEach(function(day) {
            (day.activities || []).forEach(function(act) {
                if (!act.included) actCost += (act.cost||0) * (act.pax||guests);
            });
        });
        pkg.activity_cost    = actCost;
        pkg.total_cost       = actCost + (pkg.stay_cost||0);
        pkg.price_per_person = guests > 0 ? pkg.total_cost / guests : 0;

        // Update activity cost label in card header
        var actCostEl = document.getElementById('act-cost-' + pkgNum);
        if (actCostEl) actCostEl.textContent = 'Activities: USD ' + fmt(actCost);

        // Update tab label
        var tab = document.querySelector('.tn-pkg-tab[data-pkg="' + pkgNum + '"]');
        if (tab) { var cs = tab.querySelector('.cost'); if (cs) cs.textContent = 'USD ' + fmt(pkg.total_cost); }

        // Update right-panel displays if this is the active package
        if (pkgNum == activePkg) updatePriceDisplay(pkgNum);
    }

    function updatePriceDisplay(pkgNum) {
        var pkg = pkgState[pkgNum];
        var priceEl   = document.getElementById('tn-price-display');
        var pppEl     = document.getElementById('tn-ppp');
        var selLabel  = document.getElementById('tn-sel-label');
        var selNum    = document.getElementById('tn-sel-num');
        var btnLabel  = document.getElementById('tn-book-btn-label');
        var summaryEl = document.getElementById('tn-summary-pkg');

        if (priceEl) priceEl.childNodes[0].textContent = fmt(pkg.total_cost) + ' ';
        if (pppEl)   pppEl.textContent = 'USD ' + fmt(pkg.price_per_person) + ' per person';
        if (selNum)  selNum.textContent = pkgNum;
        if (selLabel) selLabel.textContent = 'Package ' + pkgNum + ' — USD ' + fmt(pkg.total_cost);
        if (btnLabel) btnLabel.textContent = 'Book Package ' + pkgNum;
        if (summaryEl) summaryEl.textContent = 'Package ' + pkgNum;
    }

    // ── Package tab switching ─────────────────────────────────────────────────
    var tabs  = document.querySelectorAll('.tn-pkg-tab');
    var panes = document.querySelectorAll('.tn-pkg-pane');
    var bookPkg      = document.getElementById('tn-book-pkg');
    var invoicePkgIn = document.getElementById('tn-invoice-pkg');

    function selectPackage(pkgNum) {
        activePkg = parseInt(pkgNum);
        tabs.forEach(function(t){ t.classList.remove('active'); });
        panes.forEach(function(p){ p.classList.remove('active'); });
        var activeTab  = document.querySelector('.tn-pkg-tab[data-pkg="' + pkgNum + '"]');
        var activePane = document.getElementById('pkg-' + pkgNum);
        if (activeTab)  activeTab.classList.add('active');
        if (activePane) activePane.classList.add('active');
        if (bookPkg)      bookPkg.value      = pkgNum;
        if (invoicePkgIn) invoicePkgIn.value = pkgNum;
        updatePriceDisplay(activePkg);
    }

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(){ selectPackage(this.dataset.pkg); });
    });

    // ── Dirty / save bar ─────────────────────────────────────────────────────
    function markDirty() {
        dirty = true;
        var bar = document.getElementById('tn-save-bar');
        if (bar) bar.style.display = 'flex';
        var flash = document.getElementById('tn-saved-flash');
        if (flash) flash.style.display = 'none';
    }

    function showSavedFlash() {
        var bar = document.getElementById('tn-save-bar');
        if (bar) bar.style.display = 'none';
        var flash = document.getElementById('tn-saved-flash');
        if (flash) {
            flash.style.display = 'block';
            setTimeout(function(){ flash.style.display = 'none'; }, 2500);
        }
    }

    var saveBtn = document.getElementById('tn-save-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            var pkg = pkgState[activePkg];
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            var url = saveUrlTpl.replace('__PKG__', activePkg);
            fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ days: pkg.itinerary })
            })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.status) {
                    dirty = false;
                    if (data.total_cost !== undefined) {
                        pkgState[activePkg].total_cost       = data.total_cost;
                        pkgState[activePkg].activity_cost    = data.activity_cost;
                        pkgState[activePkg].price_per_person = data.price_per_person;
                    }
                    recalcAndUpdate(activePkg);
                    showSavedFlash();
                } else {
                    alert(data.message || 'Save failed. Please try again.');
                }
            })
            .catch(function(){ alert('Network error. Please try again.'); })
            .finally(function(){
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="ion ion-ios-save"></i> Save Itinerary';
            });
        });
    }

    // ── "Add Activity" button (per day) ───────────────────────────────────────
    var addTargetPkg = 1, addTargetDay = 1;
    var searchResults = [], searchFilter = 'all';
    var addOverlay = document.getElementById('tn-add-modal-overlay');

    document.querySelectorAll('.tn-add-act-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            addTargetPkg = parseInt(this.dataset.pkg);
            addTargetDay = parseInt(this.dataset.day);
            document.getElementById('tn-add-modal-title').innerHTML =
                '<i class="ion ion-ios-add-circle" style="color:#2563eb;margin-right:6px"></i>Add Activity — Day ' + addTargetDay;
            document.getElementById('tn-add-search').value = '';
            document.getElementById('tn-add-results').innerHTML =
                '<p style="color:#aaa;font-size:12px;text-align:center;padding:30px 0">Start typing to search activities for this destination.</p>';
            document.getElementById('tn-add-pax').value = guests;
            searchResults = [];
            setFilter('all');
            addOverlay.style.display = 'flex';
            document.getElementById('tn-add-search').focus();
        });
    });

    document.getElementById('tn-add-close').addEventListener('click', function(){ addOverlay.style.display = 'none'; });
    addOverlay.addEventListener('click', function(e){ if (e.target === addOverlay) addOverlay.style.display = 'none'; });

    // Search
    var searchTimer;
    document.getElementById('tn-add-search').addEventListener('input', function() {
        clearTimeout(searchTimer);
        var q = this.value;
        searchTimer = setTimeout(function(){ doSearch(q); }, 320);
    });

    function doSearch(q) {
        var resultsEl = document.getElementById('tn-add-results');
        resultsEl.innerHTML = '<p style="color:#aaa;font-size:12px;text-align:center;padding:30px 0">Searching…</p>';
        fetch(searchUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            searchResults = data.results || [];
            renderSearchResults();
        })
        .catch(function(){
            resultsEl.innerHTML = '<p style="color:#e11d48;font-size:12px;text-align:center;padding:30px 0">Search failed. Check your connection.</p>';
        });
    }

    // Filter chips
    function setFilter(f) {
        searchFilter = f;
        document.querySelectorAll('.tn-add-filter').forEach(function(btn){
            btn.classList.toggle('active', btn.dataset.filter === f);
        });
        renderSearchResults();
    }
    document.querySelectorAll('.tn-add-filter').forEach(function(btn){
        btn.addEventListener('click', function(){ setFilter(this.dataset.filter); });
    });

    function renderSearchResults() {
        var filtered = searchResults.filter(function(r){
            if (searchFilter === 'tour')       return r.type !== 'Restaurant';
            if (searchFilter === 'restaurant') return r.type === 'Restaurant';
            return true;
        });

        var resultsEl = document.getElementById('tn-add-results');
        if (!filtered.length) {
            resultsEl.innerHTML = '<p style="color:#aaa;font-size:12px;text-align:center;padding:30px 0">No results found. Try a different search term.</p>';
            return;
        }

        var html = '<div style="padding:4px 0">';
        filtered.forEach(function(r, idx) {
            var costStr = r.included
                ? '<span style="color:#16a34a;font-weight:600;font-size:11px">Included</span>'
                : (r.cost > 0 ? 'USD ' + fmt(r.cost) + ' p.p.' : 'Free');
            var slotMap = {0:'',1:'Morning',2:'Afternoon',3:'Evening'};
            var slotHint = slotMap[r.time_slot] || '';
            var typeBadge = r.type === 'Restaurant'
                ? '<span style="background:#fffbeb;color:#d97706;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;margin-right:4px">Restaurant</span>'
                : '<span style="background:#eff6ff;color:#2563eb;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;margin-right:4px">' + esc(r.type) + '</span>';

            html += '<div class="tn-result-item">'
                + '<div style="flex:1;min-width:0">'
                    + '<div style="font-size:13px;font-weight:600;color:#0a0a0a;margin-bottom:3px">' + esc(r.name) + '</div>'
                    + '<div style="font-size:11px;color:#aaa">' + typeBadge + (slotHint ? slotHint + ' · ' : '') + costStr + '</div>'
                    + (r.description ? '<div style="font-size:11px;color:#bbb;margin-top:3px;line-height:1.4">' + esc(String(r.description).substring(0,100)) + (r.description.length > 100 ? '…' : '') + '</div>' : '')
                + '</div>'
                + '<button class="tp-ab-btn tp-ab-btn--outline" style="padding:5px 12px;font-size:11px;flex-shrink:0;margin-left:10px" data-ridx="' + idx + '">+ Add</button>'
                + '</div>';
        });
        html += '</div>';
        resultsEl.innerHTML = html;

        resultsEl.querySelectorAll('[data-ridx]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var r     = filtered[parseInt(this.dataset.ridx)];
                var time  = document.getElementById('tn-add-time').value;
                var pax   = Math.max(1, parseInt(document.getElementById('tn-add-pax').value)||guests);

                var newAct = {
                    time: time, name: r.name, description: r.description||'',
                    duration: r.duration||1, cost: r.cost||0,
                    included: r.included||false, image: r.image||null,
                    type: r.type||'Activity', pax: pax
                };

                var pkg = pkgState[addTargetPkg];
                var day = (pkg.itinerary||[]).find(function(d){ return d.day == addTargetDay; });
                if (day) {
                    day.activities = day.activities || [];
                    day.activities.push(newAct);
                    day.activities.sort(function(a,b){ return (a.time||'').localeCompare(b.time||''); });
                }

                renderActivitiesForDay(addTargetPkg, addTargetDay);
                recalcAndUpdate(addTargetPkg);
                markDirty();
                addOverlay.style.display = 'none';
            });
        });
    }

    // ── Guest booking modal ───────────────────────────────────────────────────
    var overlay   = document.getElementById('tn-modal-overlay');
    var openBtn   = document.getElementById('tn-open-modal');
    var closeBtn  = document.getElementById('tn-modal-close');
    var cancelBtn = document.getElementById('tn-modal-cancel');
    var modalPkg  = document.getElementById('tn-modal-pkg');

    function openBookingModal() {
        if (dirty) {
            if (!confirm('You have unsaved changes. Save before booking to lock in your edits — or continue anyway?')) return;
        }
        if (modalPkg && bookPkg) modalPkg.value = bookPkg.value;
        overlay.style.display = 'flex';
        var fi = document.getElementById('tn-first');
        if (fi && !fi.value) fi.focus();
    }

    if (openBtn)   openBtn.addEventListener('click', openBookingModal);
    if (closeBtn)  closeBtn.addEventListener('click', function(){ overlay.style.display = 'none'; });
    if (cancelBtn) cancelBtn.addEventListener('click', function(){ overlay.style.display = 'none'; });
    if (overlay)   overlay.addEventListener('click', function(e){ if(e.target===overlay) overlay.style.display='none'; });

})();
</script>
@endpush
