<?php $__env->startSection('icon', '⏱️'); ?>
<?php $__env->startSection('code', '419'); ?>
<?php $__env->startSection('title', __('Session Expired')); ?>
<?php $__env->startSection('message', __('Your session has timed out for security purposes. Please go back to the previous page and try your action again, or return to the homepage to start fresh.')); ?>

<?php $__env->startSection('actions'); ?>
    <a href="javascript:history.back()" class="btn-primary">
        ↩ <?php echo e(__('Try Again')); ?>

    </a>
    <a href="/" class="btn-secondary">
        <?php echo e(__('Go to Home')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/errors/419.blade.php ENDPATH**/ ?>