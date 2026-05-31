<?php $style = $style ?? "style1" ?>
<?php switch($style):
    case ('style2'): ?> <?php echo $__env->make('Tour::frontend.blocks.list-featured-item.style2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('style3'): ?> <?php echo $__env->make('Tour::frontend.blocks.list-featured-item.style3', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('style4'): ?> <?php echo $__env->make('Tour::frontend.blocks.list-featured-item.style4', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('style5'): ?> <?php echo $__env->make('Tour::frontend.blocks.list-featured-item.style5', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('style6'): ?> <?php echo $__env->make('Tour::frontend.blocks.list-featured-item.style6', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php default: ?> <?php echo $__env->make('Tour::frontend.blocks.list-featured-item.style1', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endswitch; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/list-featured-item/index.blade.php ENDPATH**/ ?>