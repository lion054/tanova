<?php
    $translation = $row->translate();
?>
<div class="carCard -type-1 d-block rounded-4 item-loop-gird-2 <?php echo e($wrap_class ?? ''); ?>">
    <div class="carCard__image">
        <div class="has-skeleton">
        <div class="cardImage ratio border-light ratio-6:5">
            <a <?php if(!empty($blank)): ?> target="_blank" <?php endif; ?> href="<?php echo e($row->getDetailUrl()); ?>">
                <div class="cardImage__content">
                    <?php if($row->image_url): ?>
                        <?php if(!empty($disable_lazyload)): ?>
                            <img  src="<?php echo e($row->image_url); ?>" class="rounded-4 col-12 js-lazy" alt="<?php echo e($translation->title ?? 'image'); ?>">
                        <?php else: ?>
                            <?php echo get_image_tag($row->image_id,'medium',['class'=>'rounded-4 col-12 js-lazy','alt'=>$translation->title]); ?>

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
                <?php if($row->is_featured == "1"): ?>
                    <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-yellow-1 text-dark-1">
                        <?php echo e(__("Featured")); ?>

                    </div>
                <?php endif; ?>
                <?php if($row->discount_percent): ?>
                    <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-blue-1 text-white mt-5">
                        <?php echo e(__("Sale off :number",['number'=>$row->discount_percent])); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
    <div class="carCard__content mt-10">
        <div class="d-flex items-center lh-14 mb-5">
            <?php if(!empty($row->location->name)): ?>
                <?php $location =  $row->location->translate() ?>
                <div class="text-14 text-light-1 has-skeleton"><?php echo e($location->name); ?></div>
            <?php endif; ?>
        </div>
        <h4 class="text-dark-1 text-18 lh-16 fw-500 has-skeleton">
            <a class="text-dark-1-i" <?php if(!empty($blank)): ?> target="_blank" <?php endif; ?> href="<?php echo e($row->getDetailUrl()); ?>"> <span><?php echo e($translation->title); ?></span></a>
        </h4>
        <p class="text-light-1 lh-14 text-14 mt-5"></p>
        <div class="row x-gap-20 y-gap-10 items-center pt-5">
            <div class="col-auto">
                <div class="d-flex items-center text-14 text-dark-1 has-skeleton">
                    <i class="icon-user-2 mr-10"></i>
                    <div class="lh-14"><?php echo e($row->passenger); ?></div>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex items-center text-14 text-dark-1 has-skeleton">
                    <i class="icon-luggage mr-10"></i>
                    <div class="lh-14"><?php echo e($row->baggage); ?></div>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex items-center text-14 text-dark-1 has-skeleton">
                    <i class="icon-transmission mr-10"></i>
                    <div class="lh-14"><?php echo e($row->gear); ?></div>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex items-center text-14 text-dark-1 has-skeleton">
                    <i class="icon-speedometer mr-10"></i>
                    <div class="lh-14"><?php echo e(__("Door")); ?>: <?php echo e($row->door); ?></div>
                </div>
            </div>
        </div>
        <?php if(setting_item('car_enable_review')): ?>
            <?php $reviewData = $row->getScoreReview(); $score_total = $reviewData['score_total']; ?>
            <div class="d-flex items-center mt-20 has-skeleton">
                <div class="flex-center bg-yellow-1 rounded-4 size-30 text-12 fw-600 text-dark-1"><?php echo e($reviewData['score_total']); ?></div>
                <div class="text-14 text-dark-1 fw-500 ml-10"><?php echo e($reviewData['review_text']); ?></div>
                <div class="text-14 text-light-1 ml-10">
                    <?php if($reviewData['total_review'] > 1): ?>
                        <?php echo e(__(":number Reviews",["number"=>$reviewData['total_review'] ])); ?>

                    <?php else: ?>
                        <?php echo e(__(":number Review",["number"=>$reviewData['total_review'] ])); ?>

                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="mt-5">
            <div class="text-light-1 has-skeleton">
                <?php echo e(__('from')); ?>

                <span class="text-14  text-red-1 line-through d-inline-flex"><?php echo e($row->display_sale_price); ?></span>
                <span class="fw-500 text-dark-1 d-inline-flex"><?php echo e($row->display_price); ?></span>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Car/Views/frontend/layouts/search/loop-grid.blade.php ENDPATH**/ ?>