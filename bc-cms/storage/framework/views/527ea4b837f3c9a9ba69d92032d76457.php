<section class="layout-pt-lg layout-pb-md">
    <div data-anim-wrap class="container">
        <div data-anim-child="slide-up delay-1" class="row justify-center text-center">
            <div class="col-auto">
                <div class="sectionTitle -md">
                    <h2 class="sectionTitle__title"><?php echo e($title ?? ''); ?></h2>
                    <p class=" sectionTitle__text mt-5 sm:mt-0"><?php echo e($sub_title ?? ''); ?></p>
                </div>
            </div>
        </div>

        <div class="row y-gap-40 justify-between pt-40 sm:pt-20">
            <?php if(!empty($list_item)): ?>
                <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $image_url = get_file_url($item['icon_image'], 'full') ?>

                        <div data-anim-child="slide-up delay-<?php echo e(($k + 2)); ?>" class="col-lg-4 col-sm-6">

                            <div class="featureIcon -type-1 -hover-shadow px-50 py-50 lg:px-24 lg:py-15">
                                <div class="d-flex justify-center">
                                    <img src="<?php echo e($image_url); ?>" alt="<?php echo e($item['title'] ?? ''); ?>">
                                </div>

                                <div class="text-center mt-30">
                                    <h4 class="text-18 fw-500"><?php echo e($item['title'] ?? ''); ?></h4>
                                    <p class="text-15 mt-10"><?php echo e($item['sub_title'] ?? ''); ?></p>
                                </div>
                            </div>

                        </div>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>

        </div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/list-featured-item/style4.blade.php ENDPATH**/ ?>