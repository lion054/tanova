<section class="pt-40">
    <div class="container">
        <div class="row justify-between items-end">
            <div class="col-auto">
                <h1 class="text-26 fw-600"><?php echo e($translation->title); ?></h1>

                <div class="d-flex x-gap-5 items-center pt-5">
                    <i class="icon-location-2 text-16 text-light-1"></i>
                    <div class="text-15 text-light-1"><?php echo e($translation->address); ?></div>
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
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/layouts/details/title-and-share.blade.php ENDPATH**/ ?>