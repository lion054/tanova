

<?php $__env->startSection('content'); ?>
    <div class="layout-pt-lg layout-pb-lg bg-blue-2 header-margin">
        <div class="container">
            <div class="row justify-content-center bc-login-form-page bc-login-page">
                <div class="col-xl-6 col-lg-7 col-md-9">
                    <div class="px-50 py-50 sm:px-20 sm:py-20 bg-white shadow-4 rounded-4">
                        <h4 class="form-title text-22 fw-500 mb-3"><?php echo e(__('Reset Password')); ?></h4>
                        <div class="row">

                            <form method="POST" action="<?php echo e(route('password.update')); ?>" class="y-gap-20">
                                <?php echo $__env->make('Layout::admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="token" value="<?php echo e(request()->route('token')); ?>">

                                <div class="col-12">
                                    <div class="form-input">
                                        <input id="email" type="email" class="form-control" name="email" value="<?php echo e(old('email',request()->email)); ?>" required autofocus>
                                        <label class="lh-1 text-14 text-light-1"><?php echo e(__('E-Mail Address')); ?></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-input">
                                        <input id="password" type="password" class="form-control" name="password" required>
                                        <label class="lh-1 text-14 text-light-1"><?php echo e(__('Password')); ?></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-input">
                                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                                        <label class="lh-1 text-14 text-light-1"><?php echo e(__('Confirm Password')); ?></label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="button py-20 -dark-1 bg-blue-1 text-white w-100 form-submit">
                                        <?php echo e(__('Reset Password')); ?>

                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/auth/passwords/reset.blade.php ENDPATH**/ ?>