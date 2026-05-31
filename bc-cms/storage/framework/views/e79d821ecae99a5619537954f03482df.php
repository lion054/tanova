

<?php $__env->startSection('content'); ?>
    <div class="container">
        <div class="row justify-content-center bc-login-form-page bc-login-page">
            <div class="col-md-5">
                <div class="">
                    <h4 class="form-title"><?php echo e(__('Register')); ?></h4>
                    <?php echo $__env->make('Layout::auth.register-form',['captcha_action'=>'register_normal'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Layout/auth/register.blade.php ENDPATH**/ ?>