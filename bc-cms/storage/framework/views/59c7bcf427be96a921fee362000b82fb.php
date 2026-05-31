<?php switch($style_list):
    case ('carousel_v2'): ?>
    <?php case ('carousel'): ?> <?php echo $__env->make('Hotel::frontend.blocks.list-hotel.carousel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('normal2'): ?> <?php echo $__env->make('Hotel::frontend.blocks.list-hotel.normal2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php default: ?> <?php echo $__env->make('Hotel::frontend.blocks.list-hotel.normal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endswitch; ?>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/blocks/list-hotel/index.blade.php ENDPATH**/ ?>