<?php $__env->startSection('icon', '⚙️'); ?>
<?php $__env->startSection('code', '500'); ?>
<?php $__env->startSection('title', __('Server Error')); ?>
<?php $__env->startSection('message', __('Something went wrong on our end. Our team has been notified and is working to fix the issue. Please try again in a few moments.')); ?>

<?php $__env->startSection('actions'); ?>
    <a href="/" class="btn-primary">
        ← <?php echo e(__('Back to Home')); ?>

    </a>
    <a href="javascript:location.reload()" class="btn-secondary">
        ↻ <?php echo e(__('Retry')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/errors/500.blade.php ENDPATH**/ ?>