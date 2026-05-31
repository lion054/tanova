
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/fotorama/fotorama.css')); ?>" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc_detail_boat bc_detail">
        <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Boat::frontend.layouts.details.title-and-share', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <section class="pt-40">
            <div class="container js-pin-container">
                <div class="row y-gap-30">
                    <div class="col-lg-8">
                        <?php echo $__env->make('Boat::frontend.layouts.details.detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-lg-4">
                        <?php echo $__env->make('Boat::frontend.layouts.details.form-book', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </section>

        <div class="container mt-40 mb-40">
            <div class="border-top-light"></div>
        </div>

        <?php if(($row->map_lat && $row->map_lng) || $translation->faqs): ?>
            <?php if($row->map_lat && $row->map_lng): ?>
                <section class="mt-40">
                    <?php echo $__env->make('Layout::map.detail.map', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </section>
            <?php endif; ?>
            <div class="mt-40"></div>

            <?php if($translation->faqs): ?>
                <section>
                    <div class="container">
                        <?php echo $__env->make('Layout::common.detail.faq2', ['faqs' => $translation->faqs], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="container mt-40 mb-40">
                <div class="border-top-light"></div>
            </div>
        <?php endif; ?>

        <section class="pt-40">
            <div class="container">
                <?php echo $__env->make('Layout::common.detail.review', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </section>

        <div class="layout-pt-lg"></div>

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
                    <?php if(setting_item('boat_enable_review')): ?>
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
                                iconUrl: "<?php echo e(get_file_url(setting_item('boat_icon_marker_map'), 'full') ?? url('images/icons/png/pin.png')); ?>"
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
            no_date_select: '<?php echo e(__('Please select start date')); ?>',
            no_guest_select: '<?php echo e(__('Please select at least one number')); ?>',
            load_dates_url: '<?php echo e(route('boat.vendor.availability.loadDates')); ?>',
            availability_booking_url: '<?php echo e(route('boat.vendor.availability.availabilityBooking')); ?>',
            name_required: '<?php echo e(__('Name is Required')); ?>',
            email_required: '<?php echo e(__('Email is Required')); ?>',
        };
    </script>
    <script type="text/javascript" src="<?php echo e(asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/fotorama/fotorama.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/sticky/jquery.sticky.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('module/boat/js/single-boat.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/detail.blade.php ENDPATH**/ ?>