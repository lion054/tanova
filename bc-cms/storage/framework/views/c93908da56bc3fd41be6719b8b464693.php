<section class="layout-pt-lg layout-pb-md">
    <div data-anim-wrap class="container">
        <div data-anim-child="slide-up delay-1" class="row y-gap-30">
            <div class="col-xl-4 col-lg-5">
                <h2 class="text-30 fw-600"><?php echo e($title ?? ''); ?></h2>
                <?php if($sub_title): ?>
                    <p class="mt-5"><?php echo e($sub_title); ?></p>
                <?php endif; ?>

                <?php if($description): ?>
                    <p class="text-dark-1 mt-40 sm:mt-20"><?php echo e($description); ?></p>
                <?php endif; ?>

                <?php if(!empty($link_title)): ?>
                <div class="d-inline-block mt-40 sm:mt-20">

                    <a href="<?php echo e($link_more ?? ''); ?>" class="button -md -blue-1 bg-yellow-1 text-dark-1">
                        <?php echo e($link_title); ?> <div class="icon-arrow-top-right ml-15"></div>
                    </a>

                </div>
                <?php endif; ?>
            </div>

            <div class="col-xl-6 offset-xl-1 col-lg-7">
                <div class="row y-gap-60">

                    <?php if(!empty($list_item)): ?>
                        <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $image_url = get_file_url($item['icon_image'], 'full') ?>

                            <div data-anim-child="slide-up delay-<?php echo e($k + 3); ?>" class="col-sm-6">
                                <?php if(!empty($image_url)): ?>
                                    <img class="size-60" src="<?php echo e($image_url); ?>" alt="<?php echo e($item['title'] ?? ''); ?>">
                                <?php endif; ?>
                                <h5 class="text-18 fw-500 mt-10"><?php echo e($item['title'] ?? ''); ?></h5>
                                <p class="mt-10"><?php echo e($item['sub_title'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/list-featured-item/style6.blade.php ENDPATH**/ ?>