
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-20 lg:pb-40 md:pb-20">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"> <?php echo e($row->id ? __('Edit: ') . $row->title : __('Add new flight')); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__('AI-native airline operations. Seat inventory, fares, ancillaries, and distribution.')); ?></div>
        </div>
        <div class="col-auto">
            <?php if($row->id): ?>
                <a class="btn btn-info" href="<?php echo e(route('flight.vendor.seat.index', ['flight_id' => $row->id])); ?>">
                    <i class="fa fa-hand-o-right"></i> <?php echo e(__('Flight ticket')); ?>

                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="mb-2">
        <?php if($row->id): ?>
            <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
    <div class="lang-content-box">
        <form
            action="<?php echo e(route('flight.vendor.store', ['id' => $row->id ? $row->id : '-1', 'lang' => request()->query('lang')])); ?>"
            method="post">
            <?php echo csrf_field(); ?>
            <div class="form-add-service">
                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                    <a data-bs-toggle="tab" data-bs-target="#nav-tour-content" aria-selected="true"
                        class="active"><?php echo e(__('1. Content')); ?></a>
                    <?php if(is_default_lang()): ?>
                        <a data-bs-toggle="tab" data-bs-target="#nav-attribute"
                            aria-selected="false"><?php echo e(__('4. Attributes')); ?></a>
                        <a data-bs-toggle="tab" data-bs-target="#nav-seo" aria-selected="false"><?php echo e(__('5. SEO')); ?></a>
                    <?php endif; ?>
                </div>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-tour-content">
                        <?php echo $__env->make('Flight::admin.flight.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <?php if(is_default_lang()): ?>
                        <div class="tab-pane fade" id="nav-attribute">
                            <?php echo $__env->make('Tour::admin.tour.attributes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="tab-pane fade" id="nav-seo">
                            <?php echo $__env->make('User::frontend.vendor-seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <button class="button h-50 px-24 -dark-1 bg-blue-1 text-white" type="submit"><i
                        class="fa fa-save mr-2"></i> <?php echo e(__('Save Changes')); ?></button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/tinymce/js/tinymce/tinymce.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('js/condition.js?_ver=' . config('app.asset_version'))); ?>"></script>
    <script type="text/javascript" src="<?php echo e(url('module/core/js/map-engine.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
    <?php echo App\Helpers\MapEngine::scripts(); ?>

    <script>
        $(document).ready(function() {
            $('.has-datetimepicker').daterangepicker({
                singleDatePicker: true,
                timePicker: true,
                showCalendar: false,
                autoUpdateInput: false, //disable default date
                sameDate: true,
                autoApply: true,
                disabledPast: true,
                enableLoading: true,
                showEventTooltip: true,
                classNotAvailable: ['disabled', 'off'],
                disableHightLight: true,
                timePicker24Hour: true,
                locale: {
                    format: 'YYYY/MM/DD HH:mm:ss'
                }
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY/MM/DD hh:mm:ss'));
            });
        })
        jQuery(function($) {
            "use strict"
            new BCMapEngine('map_content', {
                fitBounds: true,
                center: [<?php echo e($row->map_lat ?? setting_item('map_lat_default', 51.505)); ?>,
                    <?php echo e($row->map_lng ?? setting_item('map_lng_default', -0.09)); ?>

                ],
                zoom: <?php echo e($row->map_zoom ?? '8'); ?>,
                ready: function(engineMap) {
                    <?php if($row->map_lat && $row->map_lng): ?>
                        engineMap.addMarker([<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>], {
                            icon_options: {}
                        });
                    <?php endif; ?>
                    engineMap.on('click', function(dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    engineMap.on('zoom_changed', function(zoom) {
                        $("input[name=map_zoom]").attr("value", zoom);
                    });
                    if (myTravel.map_provider === "gmap") {
                        engineMap.searchBox($('#customPlaceAddress'), function(dataLatLng) {
                            engineMap.clearMarkers();
                            engineMap.addMarker(dataLatLng, {
                                icon_options: {}
                            });
                            $("input[name=map_lat]").attr("value", dataLatLng[0]);
                            $("input[name=map_lng]").attr("value", dataLatLng[1]);
                        });
                    }
                    engineMap.searchBox($('.bc_searchbox'), function(dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                }
            });
        })
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/manageFlight/detail.blade.php ENDPATH**/ ?>