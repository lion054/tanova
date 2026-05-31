<?php if($row->getGallery()): ?>
    <?php echo $__env->make('Layout::common.detail.gallery5',['galleries' => $row->getGallery()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
<div class="row x-gap-80 y-gap-40 pt-40">
    <div class="col-12 gotrip-overview">
        <h3 class="text-22 fw-500"><?php echo e(__("Overview")); ?></h3>
        <div class="text-dark-1 text-15 mt-20 content-text">
            <?php echo clean($translation->content); ?>

        </div>
        <span class="d-none btn-showmore pointer text-14 text-blue-1 fw-500 underline mt-10">
                <?php echo e(__("Show More")); ?>

            </span>
    </div>
    <?php echo $__env->make('Boat::frontend.layouts.details.specs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('Boat::frontend.layouts.details.attributes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/layouts/details/detail.blade.php ENDPATH**/ ?>