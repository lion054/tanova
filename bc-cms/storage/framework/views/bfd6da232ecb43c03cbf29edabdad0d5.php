<?php $__env->startSection('icon', '🔒'); ?>
<?php $__env->startSection('code', '403'); ?>
<?php $__env->startSection('title', __('Access Restricted')); ?>
<?php $__env->startSection('message', !empty($exception->getMessage()) ? $exception->getMessage() : __('You don\'t have permission to access this area. If you believe this is a mistake, please contact support or sign in with the correct account.')); ?>

<?php $__env->startSection('actions'); ?>
    <a href="/" class="btn-primary">
        ← <?php echo e(__('Back to Home')); ?>

    </a>
    <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary">
        <?php echo e(__('Go to Dashboard')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/errors/403.blade.php ENDPATH**/ ?>