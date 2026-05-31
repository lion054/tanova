

<?php $__env->startSection('content'); ?>
    <div class="layout-pt-lg layout-pb-lg bg-blue-2 header-margin">
        <div class="container">
            <div class="row justify-content-center bc-login-form-page bc-login-page">
                <div class="col-md-8">
                    <div class="card">
                        <div class="bg-green-1 text-white rounded"><?php echo e(__('Verify Your Email Address')); ?></div>
                        <div class="card-body">
                            <?php if(session('resent')): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo e(__('A fresh verification link has been sent to your email address.')); ?>

                                </div>
                            <?php endif; ?>
                            <p>
                                <?php echo e(__('Before proceeding, please check your email for a verification link.')); ?>

                                <?php echo e(__('If you did not receive the email')); ?>,
                            </p>
                            <form action="<?php echo e(route('verification.send')); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                <button class="button px-10 py-10 -dark-1 bg-blue-1 text-white d-inline-flex form-submit mr-10" type="submit"><?php echo e(__('click here to request another')); ?>.</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/auth/verify.blade.php ENDPATH**/ ?>