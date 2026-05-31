

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center bc-login-form-page bc-login-page">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><?php echo e(__('Verify Your Email Address')); ?></div>
                <div class="card-body">
                    <?php if(session('resent')): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo e(__('A fresh verification link has been sent to your email address.')); ?>

                        </div>
                    <?php endif; ?>
                    <?php echo e(__('Before proceeding, please check your email for a verification link.')); ?>

                    <?php echo e(__('If you did not receive the email')); ?>, <a href="<?php echo e(route('verification.resend')); ?>"><?php echo e(__('click here to request another')); ?></a>.
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Layout/auth/verify.blade.php ENDPATH**/ ?>