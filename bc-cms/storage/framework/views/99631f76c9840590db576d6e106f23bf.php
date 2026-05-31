<?php switch($style ?? ''):
    case ('style2'): ?> <?php echo $__env->make("Template::frontend.blocks.offer-block.style2", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php default: ?> <?php echo $__env->make("Template::frontend.blocks.offer-block.style1", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endswitch; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Template/Views/frontend/blocks/offer-block/index.blade.php ENDPATH**/ ?>