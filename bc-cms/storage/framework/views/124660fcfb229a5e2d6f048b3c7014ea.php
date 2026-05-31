<section class="section-bg layout-pt-xl layout-pb-xl">
    <?php if(!empty($bg_image)): ?>
    <div data-anim="fade delay-1" class="section-bg__item -mx-20" data-parallax="0.7">
        <div data-parallax-target>
            <img src="<?php echo e(get_file_url($bg_image ?? "",'full')); ?>" alt="<?php echo e($title ?? 'image'); ?>">
        </div>
    </div>
    <?php endif; ?>

    <div class="container">
        <div data-anim="fade delay-3" class="row justify-center text-center">
            <div class="col-auto">
                <div class="text-white mb-10"><?php echo e($title ?? ''); ?></div>
                <h2 class="text-40 text-white"><?php echo e($sub_title ?? ''); ?></h2>

                <div class="d-inline-block mt-30">
                    <a href="<?php echo e($link_more ?? '#'); ?>" class="button -md -blue-1 bg-white text-dark-1"><?php echo e($link_title ?? ''); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if(!empty($list_item)): ?>
<section class="pt-50 pb-40 border-bottom-light">
    <div data-anim="slide-up delay-1" class="container">
        <div class="row justify-center text-center">
            <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-xl-3 col-sm-6">
                    <div class="text-40 lh-13 text-dark-1 fw-600"><?php echo e($item['number']); ?></div>
                    <div class="text-14 lh-14 text-light-1 mt-5"><?php echo e($item['title']); ?></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/call-to-action/style-normal.blade.php ENDPATH**/ ?>