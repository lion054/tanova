<div class="bc-list-tour <?php echo e($style_list); ?>">
    <?php if($style_list == 'normal'): ?>
        <?php echo $__env->make("Tour::frontend.blocks.list-tour.style-normal", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
    <?php if($style_list == "carousel"): ?>
        <?php echo $__env->make("Tour::frontend.blocks.list-tour.style-carousel", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/list-tour/index.blade.php ENDPATH**/ ?>