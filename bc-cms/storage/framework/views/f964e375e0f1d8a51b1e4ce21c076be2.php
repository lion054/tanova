<?php switch($style):
    case ('carousel'): ?> <?php echo $__env->make("Template::frontend.blocks.form-search-all-service.style-normal", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('carousel_v2'): ?> <?php echo $__env->make("Template::frontend.blocks.form-search-all-service.carousel_v2", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('carousel_v3'): ?> <?php echo $__env->make("Template::frontend.blocks.form-search-all-service.carousel_v3", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php case ('normal2'): ?> <?php echo $__env->make("Template::frontend.blocks.form-search-all-service.style-normal-2", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php break; ?>
    <?php default: ?> <?php echo $__env->make("Template::frontend.blocks.form-search-all-service.style-normal", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endswitch; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Template/Views/frontend/blocks/form-search-all-service/index.blade.php ENDPATH**/ ?>