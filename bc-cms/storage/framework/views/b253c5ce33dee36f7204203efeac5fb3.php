<?php if(!empty($list_item)): ?>
<section class="section-bg pt-40 pb-40">
    <div class="section-bg__item -left-100 -right-100 border-bottom-light"></div>
    <div class="container">
        <div class="row y-gap-30 justify-center text-center">
            <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-xl-3 col-6">
                    <div class="text-40 lh-13 text-blue-1 fw-600"><?php echo e($item['number']); ?></div>
                    <div class="text-14 lh-14 text-light-1 mt-5"><?php echo e($item['title']); ?></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/call-to-action/style-5.blade.php ENDPATH**/ ?>