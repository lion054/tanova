<div>
    <section class="layout-pt-md bc-about-text">
        <div class="container">
            <div class="row y-gap-30 justify-between items-center">
                <div class="col-lg-5">
                    <h2 class="text-30 fw-600"><?php echo e($title ?? ""); ?></h2>
                    <p class="mt-5"><?php echo e($desc ?? ""); ?></p>
                    <p class="text-dark-1 mt-60 lg:mt-40 md:mt-20">
                    <?php echo $content ?? ""; ?>

                    </p>
                </div>
                <?php if($img = get_file_url($bg_image,'full')): ?>
                    <div class="col-lg-6">
                        <img src="<?php echo e($img); ?>" alt="<?php echo e($title ?? ""); ?>" class="rounded-4">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php if(!empty($list_item)): ?>
        <section class="pt-60">
            <div class="container">
                <div class="border-bottom-light pb-40">
                    <div class="row y-gap-30 justify-center text-center">
                        <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-xl-3 col-6">
                                <div class="text-40 lg:text-30 lh-13 fw-600"><?php echo e($item['title']); ?></div>
                                <div class="text-14 lh-14 text-light-1 mt-5"><?php echo e($item['desc']); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Template/Views/frontend/blocks/about-text/index.blade.php ENDPATH**/ ?>