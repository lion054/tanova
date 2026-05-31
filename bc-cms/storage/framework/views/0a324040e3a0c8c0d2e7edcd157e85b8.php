<section class="layout-pt-md layout-pb-md bc-gotrip-list-hotel layout_<?php echo e($style_list); ?>">
    <div data-anim="slide-up delay-1" class="container">
        <div class="row y-gap-10 <?php if($style_list == 'carousel_v2'): ?> justify-center <?php else: ?> justify-between <?php endif; ?> items-end">
            <div class="col-auto">
                <div class="sectionTitle -md">
                    <h2 class="sectionTitle__title"><?php echo e($title); ?></h2>
                    <p class=" sectionTitle__text mt-5 sm:mt-0"><?php echo e($desc); ?></p>
                </div>
            </div>
        </div>

        <div class="relative <?php if($style_list == 'carousel'): ?> overflow-hidden <?php endif; ?> pt-40 sm:pt-20 js-section-slider" data-gap="30" data-scrollbar data-slider-cols="xl-4 lg-3 md-2 sm-2 base-1" data-nav-prev="js-hotels-prev" data-pagination="js-hotels-pag" data-nav-next="js-hotels-next">
            <div class="swiper-wrapper">
                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="swiper-slide">
                        <?php echo $__env->make('Hotel::frontend.layouts.search.loop-grid', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php if($style_list == 'carousel_v2'): ?>
                <button class="section-slider-nav -prev flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-hotels-prev">
                    <i class="icon icon-chevron-left text-12"></i>
                </button>
                <button class="section-slider-nav -next flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-hotels-next">
                    <i class="icon icon-chevron-right text-12"></i>
                </button>
            <?php else: ?>
                <div class="d-flex x-gap-15 items-center justify-center sm:justify-start pt-40 sm:pt-20">
                    <div class="col-auto">
                        <button class="d-flex items-center text-24 arrow-left-hover js-hotels-prev">
                            <i class="icon icon-arrow-left"></i>
                        </button>
                    </div>

                    <div class="col-auto">
                        <div class="pagination -dots text-border js-hotels-pag"></div>
                    </div>

                    <div class="col-auto">
                        <button class="d-flex items-center text-24 arrow-right-hover js-hotels-next">
                            <i class="icon icon-arrow-right"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/blocks/list-hotel/carousel.blade.php ENDPATH**/ ?>