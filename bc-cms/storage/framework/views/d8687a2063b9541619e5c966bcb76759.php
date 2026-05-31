
<?php $__env->startSection('content'); ?>
    <iframe
            width="100%"
            style="height: calc(100vh - 30px)"
            src="<?php echo e(route(config('chatify.path'),['id'=>request('user_id')])); ?>"
            frameborder="0"
    ></iframe>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/chat/index.blade.php ENDPATH**/ ?>