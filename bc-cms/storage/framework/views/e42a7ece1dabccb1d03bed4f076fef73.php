<?php
    $translation = $row->translate();
?>
<div class="card transition-3d-hover shadow-hover-2 item-loop h-100 <?php echo e($wrap_class ?? ''); ?> d-none">
    <div class="position-relative mb-2">
        <a <?php if(!empty($blank)): ?> target="_blank" <?php endif; ?> href="<?php echo e($row->getDetailUrl($include_param ?? true)); ?>"
            class="d-block gradient-overlay-half-bg-gradient-v5">
            <img class="min-height-230 bg-img-hero card-img-top" src="<?php echo e($row->image_url); ?>" alt="<?php echo clean($translation->title); ?>">
        </a>
        <div class="position-absolute top-0 left-0 pt-4 pl-3 featured">
            <?php if($row->is_featured == '1'): ?>
                <span class="badge badge-pill bg-white text-primary px-4 mr-3 py-2 font-size-14 font-weight-normal"><?php echo e(__('Featured')); ?></span>
            <?php endif; ?>
            <?php if($row->discount_percent): ?>
                <span class="badge badge-pill bg-white text-danger px-3  py-2 font-size-14 font-weight-normal"><?php echo e($row->discount_percent); ?>%</span>
            <?php endif; ?>
        </div>
        <div class="position-absolute top-0 right-0 pt-4 pr-3 btn-wishlist">
            <button type="button" class="p-0 btn btn-sm btn-icon text-white rounded-circle service-wishlist <?php echo e($row->isWishList()); ?>"
                data-id="<?php echo e($row->id); ?>" data-type="<?php echo e($row->type); ?>" data-toggle="tooltip" data-placement="top" title=""
                data-original-title="<?php echo e(__('Save for later')); ?>">
                <span class="flaticon-valentine-heart font-size-20"></span>
            </button>
        </div>
        <div class="position-absolute bottom-0 left-0 right-0 text-content">
            <div class="px-3 pb-2">
                <h2 class="h5 text-white mb-0 font-weight-bold">
                    <small class="mr-1 font-size-14"><?php echo e(__('From')); ?></small>
                    <small class="mr-1 font-size-13 text-decoration-line-through">
                        <?php echo e($row->original_price); ?>

                    </small>
                    <?php echo e($row->price); ?>

                </h2>
            </div>
        </div>
        <div class="location d-none position-absolute bottom-0 left-0 right-0">
            <div class="px-4 pb-3">
                <?php if(!empty($row->location->name)): ?>
                    <?php $location =  $row->location->translate(); ?>
                    <a href="<?php echo e($row->location->getDetailUrl() ?? ''); ?>" class="d-block">
                        <div class="d-flex align-items-center font-size-14 text-white">
                            <i class="icon flaticon-pin-1 mr-2 font-size-20"></i> <?php echo e($location->name ?? ''); ?>

                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body px-3 py-2">
        <?php if(!empty($row->country)): ?>
            <div class="d-block location">
                <div class="mb-1 d-flex align-items-center font-size-14 text-gray-1">
                    <i class="icon flaticon-pin-1 mr-2 font-size-15"></i> <?php echo e($row->country); ?>

                </div>
            </div>
        <?php endif; ?>
        <a <?php if(!empty($blank)): ?> target="_blank" <?php endif; ?> href="<?php echo e($row->getDetailUrl($include_param ?? true)); ?>"
            class="card-title font-size-17 font-weight-bold mb-0 text-dark">
            <?php echo clean($translation->title); ?>

        </a>
        <?php if(setting_item('visa_enable_review')): ?>
            <?php
            $reviewData = $row->getScoreReview();
            $score_total = $reviewData['score_total'];
            ?>
            <div class="my-2 service-review review-<?php echo e($score_total); ?>">
                <div class="d-inline-flex align-items-center font-size-17 text-lh-1 text-primary">
                    <div class="list-star green-lighter mr-2">
                        <ul class="booking-item-rating-stars">
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                            <li><i class="fa fa-star-o"></i></li>
                        </ul>
                        <div class="booking-item-rating-stars-active" style="width: <?php echo e($score_total * 2 * 10 ?? 0); ?>%">
                            <ul class="booking-item-rating-stars">
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                                <li><i class="fa fa-star"></i></li>
                            </ul>
                        </div>
                    </div>
                    <span class="text-secondary font-size-14 mt-1">
                        <?php if($reviewData['total_review'] > 1): ?>
                            <?php echo e(__(':number Reviews', ['number' => $reviewData['total_review']])); ?>

                        <?php else: ?>
                            <?php echo e(__(':number Review', ['number' => $reviewData['total_review']])); ?>

                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        <div class="g-price d-none">
            <div class="prefix">
                <span class="fr_text"><?php echo e(__('from')); ?></span>
            </div>
            <div class="price">
                <span class="onsale"><?php echo e($row->original_price); ?></span>
                <span class="text-price"><?php echo e($row->price); ?></span>
            </div>
        </div>
        <div class="mb-1 d-flex align-items-center font-size-14 text-gray-1 duration">
            <i class="icon flaticon-clock-circular-outline mr-2 font-size-14"></i>
            <?php echo e(__(':amount day(s)', ['amount' => $row->processing_days])); ?>

        </div>
    </div>
</div>


<?php
    $translation = $row->translate();
?>
<div class="tourCard -type-1 rounded-4  <?php echo e($wrap_class ?? ''); ?>">
    <div class="tourCard__image">
        <div class="cardImage ratio ratio-1:1">
            <a <?php if(!empty($blank)): ?> target="_blank" <?php endif; ?> href="<?php echo e($row->getDetailUrl()); ?>">
                <div class="cardImage__content">
                    <?php if($row->image_url): ?>
                        <?php if(!empty($disable_lazyload)): ?>
                            <img src="<?php echo e($row->image_url); ?>" class="img-responsive rounded-4 col-12 js-lazy" alt="">
                        <?php else: ?>
                            <?php echo get_image_tag($row->image_id, 'medium', ['class' => 'img-responsive rounded-4 col-12 js-lazy', 'alt' => $translation->title]); ?>

                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </a>
            <div class="service-wishlist <?php echo e($row->isWishList()); ?>" data-id="<?php echo e($row->id); ?>" data-type="<?php echo e($row->type); ?>">
                <div class="cardImage__wishlist">
                    <button class="button -blue-1 bg-white size-30 rounded-full shadow-2">
                        <i class="icon-heart text-12"></i>
                    </button>
                </div>
            </div>
            <div class="cardImage__leftBadge">
                <?php if($row->is_featured == '1'): ?>
                    <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-yellow-1 text-dark-1">
                        <?php echo e(__('Featured')); ?>

                    </div>
                <?php endif; ?>
                <?php if($row->discount_percent): ?>
                    <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-blue-1 text-white mt-5">
                        <?php echo e(__('Sale off :number', ['number' => $row->discount_percent])); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="tourCard__content mt-10">
        <div class="d-flex items-center lh-14 mb-5">
            <div class="text-14 text-light-1"><?php echo e(__(':amount day(s)', ['amount' => $row->processing_days])); ?></div>
        </div>
        <h4 class="tourCard__title text-dark-1 text-18 lh-16 fw-500">
            <a class="text-dark-1-i" <?php if(!empty($blank)): ?> target="_blank" <?php endif; ?> href="<?php echo e($row->getDetailUrl()); ?>">
                <span><?php echo e($translation->title); ?></span></a>
        </h4>
        <?php if(!empty($row->location->name)): ?>
            <?php $location = $row->location->translate() ?>
            <p class="text-light-1 lh-14 text-14 mt-5"><?php echo e($location->name); ?></p>
        <?php endif; ?>
        <div class="row justify-between items-center pt-15">
            <div class="col-auto">
                <?php if(setting_item('visa_enable_review')): ?>
                    <?php $reviewData = $row->getScoreReview();
                    $score_total = $reviewData['score_total']; ?>
                    <div class="d-flex items-center">
                        <?php echo $__env->make('Layout::common.rating', ['score_total' => $score_total], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div class="text-14 text-light-1 ml-10">
                            <?php if($reviewData['total_review'] > 1): ?>
                                <?php echo e(__(':number Reviews', ['number' => $reviewData['total_review']])); ?>

                            <?php else: ?>
                                <?php echo e(__(':number Review', ['number' => $reviewData['total_review']])); ?>

                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <div class="text-14 text-light-1">
                    <?php echo e(__('from')); ?>

                    <span class="text-14 text-red-1 line-through d-inline-flex"><?php echo e($row->original_price); ?></span>
                    <span class="fw-500 text-dark-1 d-inline-flex"><?php echo e($row->price); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/layouts/search/loop-grid.blade.php ENDPATH**/ ?>