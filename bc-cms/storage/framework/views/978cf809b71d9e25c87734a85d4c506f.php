<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('/themes/mytravel/dist/frontend/module/tour/css/tour.css?_ver=' . config('app.asset_version'))); ?>"
        rel="stylesheet">
   
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/fotorama/fotorama.css')); ?>" />
<?php $__env->stopPush(); ?>
<div class="bc_detail_tour bc_detail">
        <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="bc_content">
            <?php $review_score = $row->review_data ?>
           <?php echo $__env->make('Visa::frontend.layouts.details.visa-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <section>
                <div class="container">
                        <?php echo $__env->make('Visa::frontend.layouts.details.visa-review', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </section>
           
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
                    <?php if(setting_item('visa_enable_review')): ?>
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
                        <a
                            class="rounded-4 bg-blue-1 text-white cursor-pointer btn-primary gotrip-detail-book-mobile"><?php echo e(__('Book Now')); ?></a>
                </div>
            </div>
        </div>
    </div>
<?php $__env->startPush('js'); ?>
            <script type="text/javascript" src="<?php echo e(asset('libs/fotorama/fotorama.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/sticky/jquery.sticky.js')); ?>"></script>
    <script>
        $(document).ready(function() {
            $(document).on("click", ".gotrip-detail-book-mobile", function() {
                $('.bc_single_book_wrap').modal('show');
            });
        });
    </script>
<?php $__env->stopPush(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/detail.blade.php ENDPATH**/ ?>