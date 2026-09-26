@extends('layouts.public')
@php
    $page = \App\Support\PublicServicePage::make('space', $row, $translation, $space_related ?? []);
    $edit_url = \Illuminate\Support\Facades\Auth::check() && (int) \Illuminate\Support\Facades\Auth::id() === (int) $row->author_id && \Illuminate\Support\Facades\Route::has('space.vendor.edit') ? route('space.vendor.edit', ['id' => $row->id]) : null;
@endphp

@push('css')
    <link href="{{ asset('themes/gotrip/dist/frontend/module/space/css/space.css?_ver=' . config('app.asset_version')) }}"
        rel="stylesheet">
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
                                iconUrl: "{{ get_file_url(setting_item('space_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png') }}"
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
            load_dates_url: '{{ route('space.vendor.availability.loadDates') }}',
            name_required: '{{ __('Name is Required') }}',
            email_required: '{{ __('Email is Required') }}',
        };
    </script>
    <script type="text/javascript" src="{{ asset('module/space/js/single-space.js?_ver=' . config('app.asset_version')) }}">
    </script>
    <script type="text/javascript"
        src="{{ asset('themes/gotrip/module/space/js/single-space.js?_ver=' . config('app.asset_version')) }}"></script>
@endpush
