
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('themes/gotrip/dist/frontend/module/space/css/space.css?_ver=' . config('app.asset_version'))); ?>"
        rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc_detail">
        <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="bc_content">
            <?php echo $__env->make('Space::frontend.layouts.details.space-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('Layout::map.detail.map', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="container">
                <?php echo $__env->make('Layout::common.detail.review', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <?php echo $__env->make('Space::frontend.layouts.details.space-related', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="bc-more-book-mobile">
            <div class="container">
                <div class="left">
                    <div class="g-price">
                        <div class="prefix">
                            <span class="fr_text"><?php echo e(__('from')); ?></span>
                        </div>
                        <div class="price">
                            <span class="onsale"><?php echo e($row->display_sale_price); ?></span>
                            <span class="text-price"><?php echo e($row->display_price); ?></span>
                        </div>
                    </div>
                    <?php if(setting_item('space_enable_review')): ?>
                        <?php
                        $reviewData = $row->getScoreReview();
                        $score_total = $reviewData['score_total'];
                        ?>
                        <div class="service-review tour-review-<?php echo e($score_total); ?>">
                            <div class="list-star">
                                <ul class="booking-item-rating-stars">
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                    <li><i class="fa fa-star-o"></i></li>
                                </ul>
                                <div class="booking-item-rating-stars-active"
                                    style="width: <?php echo e($score_total * 2 * 10 ?? 0); ?>%">
                                    <ul class="booking-item-rating-stars">
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                        <li><i class="fa fa-star"></i></li>
                                    </ul>
                                </div>
                            </div>
                            <span class="review">
                                <?php if($reviewData['total_review'] > 1): ?>
                                    <?php echo e(__(':number Reviews', ['number' => $reviewData['total_review']])); ?>

                                <?php else: ?>
                                    <?php echo e(__(':number Review', ['number' => $reviewData['total_review']])); ?>

                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="right">
                    <?php if($row->getBookingEnquiryType() === 'book'): ?>
                        <a
                            class="rounded-4 bg-blue-1 text-white cursor-pointer btn-primary gotrip-detail-book-mobile"><?php echo e(__('Book Now')); ?></a>
                    <?php else: ?>
                        <a class="rounded-4 bg-blue-1 text-white cursor-pointer btn-primary" data-bs-toggle="modal"
                            data-bs-target="#enquiry_form_modal"><?php echo e(__('Contact Now')); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <?php echo App\Helpers\MapEngine::scripts(); ?>

    <script>
        jQuery(function($) {
            <?php if($row->map_lat && $row->map_lng): ?>
                new BCMapEngine('map_content', {
                    disableScripts: true,
                    fitBounds: true,
                    center: [<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>],
                    zoom: <?php echo e($row->map_zoom ?? '8'); ?>,
                    ready: function(engineMap) {
                        engineMap.addMarker([<?php echo e($row->map_lat); ?>, <?php echo e($row->map_lng); ?>], {
                            icon_options: {
                                iconUrl: "<?php echo e(get_file_url(setting_item('space_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png')); ?>"
                            }
                        });
                    }
                });
            <?php endif; ?>
        })
    </script>
    <script>
        var bc_booking_data = <?php echo json_encode($booking_data); ?>

        var bc_booking_i18n = {
            no_date_select: '<?php echo e(__('Please select Start and End date')); ?>',
            no_guest_select: '<?php echo e(__('Please select at least one guest')); ?>',
            load_dates_url: '<?php echo e(route('space.vendor.availability.loadDates')); ?>',
            name_required: '<?php echo e(__('Name is Required')); ?>',
            email_required: '<?php echo e(__('Email is Required')); ?>',
        };
    </script>
    <script type="text/javascript" src="<?php echo e(asset('module/space/js/single-space.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
    <script type="text/javascript"
        src="<?php echo e(asset('themes/gotrip/module/space/js/single-space.js?_ver=' . config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Space/Views/frontend/detail.blade.php ENDPATH**/ ?>