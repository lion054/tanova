<?php $translation = $row->translate(); ?>

<div class="border-top-light pt-30  <?php echo e($wrap_class ?? ''); ?>">
    <div class="row x-gap-20 y-gap-20">
        <div class="col-md-auto">
            <div class="has-skeleton">
                <div class="cardImage ratio ratio-1:1 w-250 md:w-1/1 rounded-4 ">
                <div class="cardImage__content">
                    <?php if($row->image_url): ?>
                        <?php if(!empty($disable_lazyload)): ?>
                            <img src="<?php echo e($row->image_url); ?>" class="img-responsive rounded-4 col-12" alt="<?php echo e($translation->title); ?>">
                        <?php else: ?>
                            <?php echo get_image_tag($row->image_id,'medium',['class'=>'img-responsive rounded-4 col-12','alt'=>$translation->title, 'lazy' => false]); ?>

                        <?php endif; ?>
                    <?php endif; ?>
                </div>

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

        <div class="col-md ">
            <?php if(!empty($row->location->name)): ?>
                <?php $location = $row->location->translate() ?>
                <div class="text-14 text-light-1 has-skeleton"><?php echo e($location->name ?? ''); ?></div></a>
            <?php endif; ?>

            <h3 class="text-18 lh-16 fw-500 mt-5 has-skeleton">
                <a class="color-dark" href="<?php echo e($row->getDetailUrl()); ?>"><span><?php echo e($translation->title); ?></span></a>
            </h3>

            <div class="has-skeleton mt-30">
                <div class="row y-gap-15">
                    <?php if($row->max_guest): ?>
                        <div class="col-auto">
                            <div class="text-14"><?php echo e(__("Max Guests")); ?></div>
                            <div class="text-14 text-light-1"><?php echo e($row->max_guest); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if($row->cabin): ?>
                        <div class="col-auto">
                            <div class="text-14"><?php echo e(__("Cabin")); ?></div>
                            <div class="text-14 text-light-1"><?php echo e($row->cabin); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if($row->length): ?>
                        <div class="col-auto">
                            <div class="text-14"><?php echo e(__("Length Boat")); ?></div>
                            <div class="text-14 text-light-1"><?php echo e($row->length); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if($row->speed): ?>
                        <div class="col-auto">
                            <div class="text-14"><?php echo e(__("Speed")); ?></div>
                            <div class="text-14 text-light-1"><?php echo e($row->speed); ?></div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-md-auto text-right md:text-left">
            <div class="has-skeleton">
                <?php if(setting_item('boat_enable_review')): ?>
                    <div class="d-flex justify-content-md-end">
                            <?php $reviewData = $row->getScoreReview(); $score_total = $reviewData['score_total'];?>
                        <?php echo $__env->make('Layout::common.rating',['score_total'=>$score_total], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="text-14 lh-14 text-light-1 mt-10">
                        <?php if($reviewData['total_review'] > 1): ?>
                            <?php echo e(__(":number Reviews",["number"=>$reviewData['total_review'] ])); ?>

                        <?php else: ?>
                            <?php echo e(__(":number Review",["number"=>$reviewData['total_review'] ])); ?>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="text-14 text-light-1 mt-20"><?php echo e(__("From")); ?></div>
                <div class="text-22 lh-12 fw-600 mt-5"><?php echo e(format_money($row->min_price)); ?></div>
                <div class="text-14 text-light-1 mt-5"><?php echo e(__("per adult")); ?></div>
            </div>
            <div class="has-skeleton mt-24">
                <a href="<?php echo e($row->getDetailUrl()); ?>" class="button h-50 px-24 -dark-1 bg-blue-1 text-white">
                    <?php echo e(__("View Detail")); ?> <div class="icon-arrow-top-right ml-15"></div>
                </a>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/layouts/search/loop-list.blade.php ENDPATH**/ ?>