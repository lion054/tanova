<section class="layout-pt-md layout-pb-md bg-light-2">
    <div class="container">
        <div class="row y-gap-30">

            <?php if(!empty($list_item)): ?>
                <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $image_url = get_file_url($item['icon_image'], 'full') ?>

                        <div class="col-lg-4 col-md-6">
                            <div class="d-flex pr-30">
                                <?php if(!empty($image_url)): ?>
                                    <img class="size-50" src="<?php echo e($image_url); ?>" alt="<?php echo e($item['title'] ?? ''); ?>">
                                <?php endif; ?>

                                <div class="ml-15">
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
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/list-featured-item/style5.blade.php ENDPATH**/ ?>