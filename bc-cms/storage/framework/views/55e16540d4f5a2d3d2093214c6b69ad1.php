<div class="relative d-flex justify-center js-section-slider" data-gap="10" data-slider-cols="xl-2 lg-2 base-1" data-nav-prev="js-img-prev" data-nav-next="js-img-next" data-loop>
    <div class="swiper-wrapper">

        <?php $__currentLoopData = $galleries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="swiper-slide">
            <div class="ratio ratio-64:45">
                <img src="<?php echo e($item['large']); ?>" alt="image" class="rounded-4 img-ratio">
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="absolute h-full col-11">

        <button class="section-slider-nav -prev flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-img-prev">
            <i class="icon icon-chevron-left text-12"></i>
        </button>

        <button class="section-slider-nav -next flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-img-next">
            <i class="icon icon-chevron-right text-12"></i>
        </button>

    </div>

    <div class="absolute h-full col-12 px-20 py-20 d-flex justify-end items-end">
        <?php $__currentLoopData = $galleries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($item['large']); ?>" class="<?php if(!$k): ?> button -blue-1 px-24 py-15 bg-white text-dark-1 z-2  <?php endif; ?> js-gallery" data-gallery="gallery2">
                <?php if(!$k): ?> <?php echo e(__('See All :count Photos',['count'=>count($galleries)])); ?> <?php endif; ?>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/common/detail/gallery.blade.php ENDPATH**/ ?>