<section class="pt-40 g-header">
    <div class="container">
        <div class="row y-gap-30">
            <div class="col-12">
                <div class="row justify-between items-end">
                    <div class="col-auto">
                        <h1 class="text-26 fw-600"><?php echo e($translation->title); ?></h1>

                        <div class="row x-gap-20 y-gap-20 items-center pt-10">
                            <div class="col-auto">
                                <div class="row x-gap-10 items-center">
                                    <div class="col-auto">
                                        <div class="d-flex x-gap-5 items-center">
                                            <i class="icon-location-2 text-16 text-light-1"></i>
                                            <div class="text-15 text-light-1"><?php echo e($translation->address); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-auto">
                        <div class="row x-gap-10 y-gap-10">
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="button px-15 py-10 -blue-1 dropdown-toggle" type="button" id="dropdownMenuShare" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="icon-share mr-10"></i>
                                        <?php echo e(__('Share')); ?>

                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuShare">
                                        <a class="dropdown-item facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e($row->getDetailUrl()); ?>&amp;title=<?php echo e($translation->title); ?>" target="_blank" rel="noopener" original-title="<?php echo e(__("Facebook")); ?>">
                                            <i class="fa fa-facebook"></i> <?php echo e(__('Facebook')); ?>

                                        </a>
                                        <a class="dropdown-item twitter" href="https://twitter.com/share?url=<?php echo e($row->getDetailUrl()); ?>&amp;title=<?php echo e($translation->title); ?>" target="_blank" rel="noopener" original-title="<?php echo e(__("X")); ?>">
                                            <i class="fa fa-twitter"></i> <?php echo e(__('X')); ?>

                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-auto">
                                <div class="service-wishlist <?php echo e($row->isWishList()); ?>" data-id="<?php echo e($row->id); ?>" data-type="<?php echo e($row->type); ?>">
                                    <button class="button px-15 py-10 -blue-1 bg-light-2">
                                        <i class="icon-heart mr-10"></i>
                                        <?php echo e(__('Save')); ?>

                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if($row->getGallery()): ?>
    <?php echo $__env->make('Layout::common.detail.gallery2',['galleries' => $row->getGallery()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<section class="pt-40">
    <div class="container js-pin-container">
        <div class="row y-gap-30">
            <div class="col-lg-8">
                <div>
                    <h3 class="text-22 fw-500"><?php echo e(__('Property highlights')); ?></h3>

                    <div class="row y-gap-30 justify-between pt-20">
                        <?php if(!empty($row->bed)): ?>
                            <div class="col-md-auto col-6">
                                <div class="d-flex">
                                    <i class="icon-bed text-22 text-blue-1 mr-10"></i>
                                    <div class="text-15 lh-15">
                                        <?php echo e(__('Bedrooms')); ?><br> <?php echo e($row->bed); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if($row->bathroom): ?>
                            <div class="col-md-auto col-6">
                                <div class="d-flex">
                                    <i class="icon-bathtub text-22 text-blue-1 mr-10"></i>
                                    <div class="text-15 lh-15">
                                        <?php echo e(__('Bathroom')); ?><br> <?php echo e($row->bathroom); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if($row->square): ?>
                            <div class="col-md-auto col-6">
                                <div class="d-flex">
                                    <i class="fa fa-crop text-22 text-blue-1 mr-10"></i>
                                    <div class="text-15 lh-15">
                                        <?php echo e(__("Square")); ?><br> <?php echo size_unit_format($row->square); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($row->location->name)): ?>
                            <?php $location =  $row->location->translate() ?>
                            <div class="col-md-auto col-6">
                                <div class="d-flex">
                                    <i class="icon-location text-22 text-blue-1 mr-10"></i>
                                    <div class="text-15 lh-15">
                                        <?php echo e(__("Location")); ?><br> <?php echo e($location->name ?? ''); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border-top-light mt-40 mb-40"></div>

                <?php if($translation->content): ?>
                    <div id="overview" class="col-12 gotrip-overview">
                        <h3 class="text-22 fw-500"><?php echo e(__('Overview')); ?></h3>
                        <div class="text-dark-1 text-15 mt-20 content-text">
                            <?php echo clean($translation->content); ?>

                        </div>
                        <span class="d-none btn-showmore pointer text-14 text-blue-1 fw-500 underline mt-10">
                                <?php echo e(__("Show More")); ?>

                            </span>
                    </div>
                <?php endif; ?>
                <?php echo $__env->make('Space::frontend.layouts.details.space-attributes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if ($__env->exists("Layout::common.detail.surrounding",['line_top'=>true])) echo $__env->make("Layout::common.detail.surrounding",['line_top'=>true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="border-top-light mt-40 mb-40"></div>
                <?php if($translation->faqs): ?>
                    <?php echo $__env->make('Layout::common.detail.faq',['faqs'=>$translation->faqs], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <?php echo $__env->make('Space::frontend.layouts.details.space-form-book', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
        <div class="border-top-light mt-40 mb-40"></div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Space/Views/frontend/layouts/details/space-detail.blade.php ENDPATH**/ ?>