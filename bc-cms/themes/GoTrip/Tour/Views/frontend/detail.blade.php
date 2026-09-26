@extends('layouts.public')
@php
    $page = \App\Support\PublicServicePage::make('tour', $row, $translation, $tour_related ?? []);
    $edit_url = \Illuminate\Support\Facades\Auth::check() && (int) \Illuminate\Support\Facades\Auth::id() === (int) $row->author_id && \Illuminate\Support\Facades\Route::has('tour.vendor.edit') ? route('tour.vendor.edit', ['id' => $row->id]) : null;
@endphp

@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css') }}" />
@endpush
@section('content')
    @include('Layout::public.service')
@endsection

@push('js')
    {!! App\Helpers\MapEngine::scripts() !!}
    <script>
        jQuery(function($) {
            @if ($row->map_lat && $row->map_lng)
                new BCMapEngine('map_content', {
                    disableScripts: true,
                    fitBounds: true,
                    center: [{{ $row->map_lat }}, {{ $row->map_lng }}],
                    zoom: {{ $row->map_zoom ?? '8' }},
                    ready: function(engineMap) {
                        engineMap.addMarker([{{ $row->map_lat }}, {{ $row->map_lng }}], {
                            icon_options: {
                                iconUrl: "{{ get_file_url(setting_item('tour_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png') }}"
                            }
                        });
                    }
                });
            @endif
        })
    </script>
    <script>
        var bc_booking_data = {!! json_encode($booking_data) !!}
        var bc_booking_i18n = {
            no_date_select: '{{ __('Please select Start and End date') }}',
            no_guest_select: '{{ __('Please select at least one guest') }}',
            load_dates_url: '{{ route('tour.vendor.availability.loadDates') }}',
            name_required: '{{ __('Name is Required') }}',
            email_required: '{{ __('Email is Required') }}',
        };
    </script>
    <script type="text/javascript" src="{{ asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js') }}"></script>

    <script type="text/javascript" src="{{ asset('module/tour/js/single-tour.js?_ver=' . config('app.asset_version')) }}">
    </script>
@endpush
