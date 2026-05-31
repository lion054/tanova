<?php $__env->startSection('icon', '🗺️'); ?>
<?php $__env->startSection('code', '404'); ?>
<?php $__env->startSection('title', __('Page Not Found')); ?>
<?php $__env->startSection('message', __('This page has wandered off the trail. It may have been moved, renamed, or removed. Let\'s get you back on the right path.')); ?>

<?php $__env->startSection('actions'); ?>
    <a href="/" class="btn-primary">
        ← <?php echo e(__('Back to Home')); ?>

    </a>
    <a href="javascript:history.back()" class="btn-secondary">
        <?php echo e(__('Go Back')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/errors/404.blade.php ENDPATH**/ ?>