
<?php $__env->startSection('content'); ?>
    <h2 class="title-bar">
        <?php echo e(__("Change Password")); ?>

    </h2>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <form action="<?php echo e(route("user.change_password.update")); ?>" method="post">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Current Password")); ?></label>
                    <input type="password" required name="current-password" placeholder="<?php echo e(__("Current Password")); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label><?php echo e(__("New Password")); ?></label>
                    <input type="password" required name="new-password" minlength="8" placeholder="<?php echo e(__("New Password")); ?>" class="form-control">
                    <p><i><?php echo e(__("* Require at least one uppercase, one lowercase letter, one number and one symbol.")); ?></i></p>
               </div>
                <div class="form-group">
                    <label><?php echo e(__("New Password Again")); ?></label>
                    <input type="password" required name="new-password_confirmation" minlength="8" placeholder="<?php echo e(__("New Password Again")); ?>" class="form-control">
                </div>
            </div>
            <div class="col-md-12">
                <hr>
                <input type="submit" class="btn btn-primary" value="<?php echo e(__("Change Password")); ?>">
                <a href="<?php echo e(route("user.profile.index")); ?>" class="btn btn-default"><?php echo e(__("Cancel")); ?></a>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/changePassword.blade.php ENDPATH**/ ?>