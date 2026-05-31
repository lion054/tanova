<div class="bc-call-to-action <?php echo e($style); ?>">
    <?php switch($style):
        case ("style_2"): ?>
            <?php echo $__env->make("Tour::frontend.blocks.call-to-action.style-2", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php break; ?>
       
        <?php case ("style_4"): ?>
            <?php echo $__env->make("Tour::frontend.blocks.call-to-action.style-4", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php break; ?>
        <?php case ("style_5"): ?>
            <?php echo $__env->make("Tour::frontend.blocks.call-to-action.style-5", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php break; ?>
        <?php default: ?>
            <?php echo $__env->make("Tour::frontend.blocks.call-to-action.style-normal", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endswitch; ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/call-to-action/index.blade.php ENDPATH**/ ?>