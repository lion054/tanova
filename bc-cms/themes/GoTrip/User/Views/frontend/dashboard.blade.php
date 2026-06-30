@extends('layouts.user')
@section('content')
@php
    $dashIcon = function ($k) {
        $m = ['pending'=>'icofont-clock-time','earn'=>'icofont-money','book'=>'icofont-ticket','service'=>'icofont-listing-box','revenue'=>'icofont-money'];
        foreach ($m as $kw => $ic) { if (str_contains(strtolower((string) $k), $kw)) return $ic; }
        return 'icofont-chart-bar-graph';
    };
@endphp
<div class="tnv-page">

    <div class="tnv-ph">
        <div>
            <div class="tnv-ph__crumb">{{ __('Overview') }}</div>
            <h1 class="tnv-ph__title">{{ __('Dashboard') }}</h1>
        </div>
    </div>

    @include('admin.message')

    @if(!empty($cards_report))
    <div class="tnv-grid tnv-grid--stats" style="margin-bottom:18px">
        @foreach($cards_report as $key => $item)
            <div class="tnv-stat">
                <div class="tnv-stat__ic"><i class="{{ $dashIcon($key) }}"></i></div>
                <div class="tnv-stat__main">
                    <div class="tnv-stat__l">{{ $item['title'] }}</div>
                    <div class="tnv-stat__v">{{ $item['amount'] }}</div>
                    @if(!empty($item['desc']))<div class="tnv-muted" style="font-size:11.5px;margin-top:3px">{{ $item['desc'] }}</div>@endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="tnv-grid tnv-grid--2">

        {{-- Earning statistics --}}
        <div class="tnv-c">
            <div class="tnv-c__h">
                <h3>{{ __('Earning Statistics') }}</h3>
                <div class="tnv-b tnv-b--neutral" id="reportrange" style="cursor:pointer">
                    <i class="fa fa-calendar"></i> <span></span> <i class="fa fa-caret-down"></i>
                </div>
            </div>
            <div class="tnv-c__b">
                <canvas class="bc-user-render-chart" height="150"></canvas>
                <script>var earning_chart_data = {!! json_encode($earning_chart_data) !!};</script>
            </div>
        </div>

        {{-- Recent bookings --}}
        <div class="tnv-c">
            <div class="tnv-c__h">
                <h3>{{ __('Recent Bookings') }}</h3>
                <a href="{{ route('vendor.bookingReport') }}" class="tnv-link-gold">{{ __('View All') }}</a>
            </div>
            <div class="tnv-c__b tnv-c__b--flush">
                @if($recent_bookings && count($recent_bookings))
                    <table class="tnv-tbl">
                        <thead><tr><th>#</th><th>{{ __('Item') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead>
                        <tbody>
                        @foreach($recent_bookings as $val)
                            @php
                                $st = $val->status;
                                $pill = in_array($st,['paid','completed','confirmed']) ? 'tnv-b--pos'
                                      : (in_array($st,['unpaid','processing','pending','partial_payment']) ? 'tnv-b--gold'
                                      : (in_array($st,['fail']) ? 'tnv-b--neg' : 'tnv-b--neutral'));
                            @endphp
                            <tr>
                                <td class="tnv-muted">#{{ $val->id }}</td>
                                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $val->service->title ?? '—' }}</td>
                                <td class="num">{{ format_money($val->total) }}</td>
                                <td><span class="tnv-b {{ $pill }}">{{ booking_status_to_text($st) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="tnv-empty"><div class="tnv-empty__ic"><i class="icofont-ticket"></i></div><div class="tnv-empty__t">{{ __('No bookings yet') }}</div></div>
                @endif
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
        /* Remap chart dataset colours to brand (gold + greyscale) */
        if (earning_chart_data && earning_chart_data.datasets) {
            var cols = ['#E0A23B','#16161A','#A1A1AA','#D0D0D0','#E8E8E8'];
            earning_chart_data.datasets.forEach(function(ds, i) {
                ds.backgroundColor = cols[i % cols.length];
                ds.borderColor     = cols[i % cols.length];
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
