<?php $__env->startSection('content'); ?>
<div class="bc-user-dashboard">

    
    <div class="portal-header">
        <div>
            <p class="portal-eyebrow"><?php echo e(__("Overview")); ?></p>
            <h1 class="portal-h1"><?php echo e(__("Dashboard")); ?></h1>
        </div>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(!empty($cards_report)): ?>
    <div class="row y-gap-20 mb-28">
        <?php $__currentLoopData = $cards_report; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-xl-3 col-md-6">
            <div class="portal-stat">
                <div class="portal-stat__label"><?php echo e($item['title']); ?></div>
                <div class="portal-stat__value"><?php echo e($item['amount']); ?></div>
                <div class="portal-stat__desc"><?php echo e($item['desc']); ?></div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    
    <div class="row y-gap-20 pt-8">

        
        <div class="col-xl-7 col-md-6">
            <div class="portal-card">
                <div class="portal-card__header">
                    <span class="portal-card__title"><?php echo e(__("Earning Statistics")); ?></span>
                    <div class="portal-daterange" id="reportrange">
                        <i class="fa fa-calendar"></i>
                        <span></span>
                        <i class="fa fa-caret-down"></i>
                    </div>
                </div>
                <canvas class="bc-user-render-chart"></canvas>
                <script>var earning_chart_data = <?php echo json_encode($earning_chart_data); ?>;</script>
            </div>
        </div>

        
        <div class="col-xl-5 col-md-6">
            <div class="portal-card">
                <div class="portal-card__header">
                    <span class="portal-card__title"><?php echo e(__("Recent Bookings")); ?></span>
                    <a href="<?php echo e(route('vendor.bookingReport')); ?>" class="portal-card__link"><?php echo e(__("View All")); ?></a>
                </div>
                <div class="overflow-scroll scroll-bar-1">
                    <table class="table-2 col-12">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo e(__("Item")); ?></th>
                            <th><?php echo e(__("Total")); ?></th>
                            <th><?php echo e(__("Status")); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if($recent_bookings): ?>
                            <?php $__currentLoopData = $recent_bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
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
                            ?>
                            <tr>
                                <td style="color:#a0a0a0;font-size:11px;">#<?php echo e($val->id); ?></td>
                                <td style="font-size:12px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($val->service->title ?? ''); ?></td>
                                <td style="font-size:12px;font-weight:600;"><?php echo e(format_money($val->total)); ?></td>
                                <td>
                                    <div class="rounded-100 py-4 text-center text-14 fw-500 <?php echo e($sc); ?>"
                                         style="font-size:11px;padding:3px 8px;white-space:nowrap;">
                                        <?php echo e(booking_status_to_text($val->status)); ?>

                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center" style="color:#a0a0a0;font-size:12px;padding:24px 0;"><?php echo e(__("No bookings yet")); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script type="text/javascript" src="<?php echo e(asset("libs/chart_js/Chart.min.js")); ?>"></script>
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
                            return (lbl ? lbl + ': ' : '') + item.yLabel + ' (<?php echo e(setting_item("currency_main")); ?>)';
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
            '<?php echo e(__("Today")); ?>':       [moment(), moment()],
            '<?php echo e(__("Yesterday")); ?>':   [moment().subtract(1,'days'), moment().subtract(1,'days')],
            '<?php echo e(__("Last 7 Days")); ?>': [moment().subtract(6,'days'), moment()],
            '<?php echo e(__("Last 30 Days")); ?>': [moment().subtract(29,'days'), moment()],
            '<?php echo e(__("This Month")); ?>':  [moment().startOf('month'), moment().endOf('month')],
            '<?php echo e(__("Last Month")); ?>':  [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
            '<?php echo e(__("This Year")); ?>':   [moment().startOf('year'), moment().endOf('year')]
        }
    }, cb).on('apply.daterangepicker', function (ev, picker) {
        $.ajax({
            url: '<?php echo e(url("user/reloadChart")); ?>',
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/dashboard.blade.php ENDPATH**/ ?>