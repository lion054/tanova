<section class="<?php if($style == 'style2'): ?> layout-pt-xl layout-pb-md <?php else: ?> layout-pt-md layout-pb-lg bc-featured-item <?php endif; ?> <?php echo e($style); ?>">
    <div data-anim-wrap class="container">
        <?php if(!empty($title)): ?>
            <div data-anim-child="slide-up delay-1" class="row justify-center text-center" style="padding-bottom: 50px">
                <div class="col-auto">
                    <div class="sectionTitle -md">
                        <h2 class="sectionTitle__title"><?php echo e($title ?? ''); ?></h2>
                        <p class=" sectionTitle__text mt-5 sm:mt-0"><?php echo e($sub_title ?? ''); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($list_item)): ?>
            <div class="row <?php if($style == 'style2'): ?> y-gap-40 sm:y-gap-10 <?php else: ?>  y-gap-20 <?php endif; ?> justify-between">
                <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $image_url = get_file_url($item['icon_image'], 'full') ?>
                    <div data-anim-child="slide-up delay-<?php echo e($k+1); ?>"
                         class="<?php if($style == 'style2'): ?> col-lg-4 col-sm-6 <?php else: ?> col-lg-3 col-sm-6 <?php endif; ?>">
                        <div
                            class="featureIcon -type-1 <?php if($style == 'style2'): ?>  -hover-shadow px-50 py-50 lg:px-24 lg:py-1 <?php endif; ?>">
                            <div class="d-flex justify-center">
                                <img src="<?php echo e($image_url); ?>" alt="<?php echo e($item['title'] ?? ''); ?>">
                            </div>

                            <div class="text-center mt-30">
                                <h4 class="text-18 fw-500"><?php echo e($item['title'] ?? ''); ?></h4>
                                <p class="text-15 mt-10"><?php echo e($item['sub_title']); ?></p>
                            </div>
                        </div>

                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/list-featured-item/style1.blade.php ENDPATH**/ ?>