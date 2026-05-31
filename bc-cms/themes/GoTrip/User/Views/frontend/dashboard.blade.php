@extends('layouts.user')
@section('content')
<div class="bc-user-dashboard">

    {{-- Page header --}}
    <div class="portal-header">
        <div>
            <p class="portal-eyebrow">{{ __("Overview") }}</p>
            <h1 class="portal-h1">{{ __("Dashboard") }}</h1>
        </div>
    </div>

    @include('admin.message')

    {{-- Stat cards --}}
    @if(!empty($cards_report))
    <div class="row y-gap-20 mb-28">
        @foreach($cards_report as $key => $item)
        <div class="col-xl-3 col-md-6">
            <div class="portal-stat">
                <div class="portal-stat__label">{{ $item['title'] }}</div>
                <div class="portal-stat__value">{{ $item['amount'] }}</div>
                <div class="portal-stat__desc">{{ $item['desc'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Charts + recent bookings --}}
    <div class="row y-gap-20 pt-8">

        {{-- Earning statistics --}}
        <div class="col-xl-7 col-md-6">
            <div class="portal-card">
                <div class="portal-card__header">
                    <span class="portal-card__title">{{ __("Earning Statistics") }}</span>
                    <div class="portal-daterange" id="reportrange">
                        <i class="fa fa-calendar"></i>
                        <span></span>
                        <i class="fa fa-caret-down"></i>
                    </div>
                </div>
                <canvas class="bc-user-render-chart"></canvas>
                <script>var earning_chart_data = {!! json_encode($earning_chart_data) !!};</script>
            </div>
        </div>

        {{-- Recent bookings --}}
        <div class="col-xl-5 col-md-6">
            <div class="portal-card">
                <div class="portal-card__header">
                    <span class="portal-card__title">{{ __("Recent Bookings") }}</span>
                    <a href="{{ route('vendor.bookingReport') }}" class="portal-card__link">{{ __("View All") }}</a>
                </div>
                <div class="overflow-scroll scroll-bar-1">
                    <table class="table-2 col-12">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __("Item") }}</th>
                            <th>{{ __("Total") }}</th>
                            <th>{{ __("Status") }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($recent_bookings)
                            @foreach($recent_bookings as $val)
                            @php
                                switch ($val->status) {
                                    case "unpaid": case "processing": case "pending":
                                        $sc = 'bg-yellow-4 text-yellow-3'; break;
                                    case "partial_payment":
                                        $sc = 'bg-blue-1-05 text-blue-1'; break;
                                    case "paid": case "completed": case "confirmed":
                                        $sc = 'bg-green-1 text-green-2'; break;
                                    case "cancelled": case "cancel":
                                        $sc = 'bg-border text-black'; break;
                                    case "fail":
                                        $sc = 'bg-red-3 text-red-2'; break;
                                    default:
                                        $sc = 'bg-light-2 text-light-1'; break;
                                }
                            @endphp
                            <tr>
                                <td style="color:#a0a0a0;font-size:11px;">#{{ $val->id }}</td>
                                <td style="font-size:12px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $val->service->title ?? '' }}</td>
                                <td style="font-size:12px;font-weight:600;">{{ format_money($val->total) }}</td>
                                <td>
                                    <div class="rounded-100 py-4 text-center text-14 fw-500 {{ $sc }}"
                                         style="font-size:11px;padding:3px 8px;white-space:nowrap;">
                                        {{ booking_status_to_text($val->status) }}
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr><td colspan="4" class="text-center" style="color:#a0a0a0;font-size:12px;padding:24px 0;">{{ __("No bookings yet") }}</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('js')
<script type="text/javascript" src="{{ asset("libs/chart_js/Chart.min.js") }}"></script>
<script type="text/javascript">
jQuery(function ($) {
    $(".bc-user-render-chart").each(function () {
        var ctx = $(this)[0].getContext('2d');
        /* Remap chart dataset colours to greyscale */
        if (earning_chart_data && earning_chart_data.datasets) {
            var greys = ['#0a0a0a','#5a5a5a','#a0a0a0','#d0d0d0','#e8e8e8'];
            earning_chart_data.datasets.forEach(function(ds, i) {
                ds.backgroundColor = greys[i % greys.length];
                ds.borderColor     = greys[i % greys.length];
            });
        }
        window.myMixedChartForVendor = new Chart(ctx, {
            type: 'bar',
            data: earning_chart_data,
            options: {
                responsive: true,
                legend: { display: true, labels: { fontFamily: 'Inter', fontSize: 11, fontColor: '#5a5a5a' } },
                scales: {
                    xAxes: [{ stacked: true, gridLines: { color: '#f0f0f0' }, ticks: { fontFamily: 'Inter', fontSize: 11, fontColor: '#a0a0a0' } }],
                    yAxes: [{ stacked: true, gridLines: { color: '#f0f0f0' }, ticks: { beginAtZero: true, fontFamily: 'Inter', fontSize: 11, fontColor: '#a0a0a0' } }]
                },
                tooltips: {
                    backgroundColor: '#0a0a0a',
                    titleFontFamily: 'Inter', titleFontSize: 11,
                    bodyFontFamily: 'Inter', bodyFontSize: 12,
                    callbacks: {
                        label: function (item, data) {
                            var lbl = data.datasets[item.datasetIndex].label || '';
                            return (lbl ? lbl + ': ' : '') + item.yLabel + ' ({{ setting_item("currency_main") }})';
                        }
                    }
                }
            }
        });
    });

    $(".bc-user-chart form select").change(function () {
        $(this).closest("form").submit();
    });

    var start = moment().startOf('week'), end = moment();
    function cb(s, e) {
        $('#reportrange span').html(s.format('MMM D, YYYY') + ' – ' + e.format('MMM D, YYYY'));
    }
    $('#reportrange').daterangepicker({
        startDate: start, endDate: end,
        alwaysShowCalendars: true, opens: 'left', showDropdowns: true,
        ranges: {
            '{{ __("Today") }}':       [moment(), moment()],
            '{{ __("Yesterday") }}':   [moment().subtract(1,'days'), moment().subtract(1,'days')],
            '{{ __("Last 7 Days") }}': [moment().subtract(6,'days'), moment()],
            '{{ __("Last 30 Days") }}': [moment().subtract(29,'days'), moment()],
            '{{ __("This Month") }}':  [moment().startOf('month'), moment().endOf('month')],
            '{{ __("Last Month") }}':  [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
            '{{ __("This Year") }}':   [moment().startOf('year'), moment().endOf('year')]
        }
    }, cb).on('apply.daterangepicker', function (ev, picker) {
        $.ajax({
            url: '{{ url("user/reloadChart") }}',
            data: { chart:'earning', from: picker.startDate.format('YYYY-MM-DD'), to: picker.endDate.format('YYYY-MM-DD') },
            dataType: 'json', type: 'post',
            success: function (res) {
                if (res.status) {
                    window.myMixedChartForVendor.data = res.data;
                    window.myMixedChartForVendor.update();
                }
            }
        });
    });
    cb(start, end);
});
</script>
@endpush
