
<?php $__env->startSection('content'); ?>
    <h2 class="title-bar">
        <?php echo e(__("Two Factor Authentication")); ?>

    </h2>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="row">
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-title"><strong><?php echo e(__("Setup Two Factor Authentication")); ?></strong></div>
                <div class="panel-body">
                    <?php if(auth()->user()->two_factor_secret): ?>
                        <?php echo $__env->make('User::frontend.2fa.parts.info', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php else: ?>
                        <?php echo $__env->make('User::frontend.2fa.parts.setup', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/2fa/index.blade.php ENDPATH**/ ?>