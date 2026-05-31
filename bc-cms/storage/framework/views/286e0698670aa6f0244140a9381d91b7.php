<div class="carsSlider mt-40">
    <div class="carsSlider-slides js-cars-slides">
        <?php $__currentLoopData = $galleries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($key > 4) continue; ?>
            <div class="carsSlider-slides__item rounded-4 <?php if($key == 0): ?>-is-active <?php endif; ?> ">
                <img src="<?php echo e($item['thumb']); ?>" alt="<?php echo e(__("Gallery")); ?>">
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="carsSlider-slider">
        <div class="js-cars-slider">
            <div class="swiper-wrapper">
                <?php $__currentLoopData = $galleries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($key > 4) continue; ?>
                    <div class="swiper-slide">
                        <img src="<?php echo e($item['large']); ?>" data-alt="<?php echo e(__("Gallery")); ?>" class="rounded-4">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/common/detail/gallery3.blade.php ENDPATH**/ ?>