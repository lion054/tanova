<?php
$service = $row->service;
?>
<?php if(!empty($service)): ?>
    <?php
        $service = $row->service;
        $translation = $service->translate();
        $layout_style = $layout_style ?? '';
    ?>
    <div class="border-bottom-light pb-20">
        <div class="row x-gap-20 y-gap-30">
            <div class="col-md-auto">
                <div class="cardImage ratio ratio-1:1 w-200 md:w-1/1 rounded-4">
                    <div class="cardImage__content">
                        <img  src="<?php echo e($service->image_url); ?>" class="rounded-4 js-lazy" alt="<?php echo e($translation->title); ?>">
                    </div>
                    <div class="service-wishlist <?php echo e($service->isWishList()); ?>" data-id="<?php echo e($service->id); ?>" data-type="<?php echo e($service->type); ?>">
                        <div class="cardImage__wishlist">
                            <button class="button -blue-1 bg-white size-30 rounded-full shadow-2">
                                <i class="icon-heart text-12"></i>
                            </button>
                        </div>
                    </div>
                    <?php if($service->is_featured == "1"): ?>
                        <div class="cardImage__leftBadge">
                            <div class="py-5 px-15 rounded-right-4 text-12 lh-16 fw-500 uppercase bg-dark-1 text-white">
                                <?php echo e(__("Featured")); ?>

                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md">
                <a href="<?php echo e($service->getDetailUrl()); ?>" class="text-18 lh-14 fw-500 text-dark-1"><?php echo e($translation->title); ?></a>
                <?php if($service->getReviewEnable()): ?>
                    <div class="rate  pt-10">
                            <?php $reviewData = $service->getScoreReview(); $score_total = $reviewData['score_total'];?>
                        <?php echo $__env->make('Layout::common.rating',['score_total'=>$score_total], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endif; ?>
                <div class="pt-10 text-dark-1">
                    <i class="icofont-license"></i>
                    <?php echo e(__("Service Type")); ?>: <span class="badge badge-info"><?php echo e($service->getModelName() ?? ''); ?></span>
                </div>
                <div class="pt-5 text-dark-1">
                    <?php if(!empty($service->location->name)): ?>
                        <i class="icofont-paper-plane"></i>
                        <?php echo e(__("Location")); ?>: <?php echo e($service->location->name ?? ''); ?>

                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-auto text-right md:text-left">
                <div class="d-flex flex-column justify-between h-full">
                    <?php if($service->getReviewEnable()): ?>
                        <div class="row x-gap-10 y-gap-10 justify-end items-center md:justify-start">
                            <div class="col-auto">
                                <div class="text-14 lh-14 fw-500">
                                    <?php echo e($reviewData['review_text'] ?? ""); ?>

                                </div>
                                <div class="text-14 lh-14 text-light-1">
                                    <?php if($reviewData['total_review'] > 1): ?>
                                        <?php echo e(__(":number Reviews",["number"=>$reviewData['total_review'] ])); ?>

                                    <?php else: ?>
                                        <?php echo e(__(":number Review",["number"=>$reviewData['total_review'] ])); ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="flex-center text-white fw-600 text-14 size-40 rounded-4 bg-blue-1"><?php echo e($score_total); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="pt-24">
                        <div class="fw-500"><?php echo e(__("Starting from")); ?></div>
                        <span class="fw-500 text-blue-1 text-20">
                            <span class="sale-price"><?php echo e($service->display_sale_price); ?></span>
                            <?php echo e($service->display_price); ?>

                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/wishList/loop-list.blade.php ENDPATH**/ ?>