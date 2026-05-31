
<?php $__env->startSection('content'); ?>
    <div class="b-container">
        <div class="b-panel">
            <h1><?php echo e(__("Hello Administrator")); ?></h1>

            <p><?php echo e(__('An user has been submit their verification data.')); ?></p>
            <p><?php echo e(__('Name: :name',['name'=>$user->business_name ? $user->business_name : $user->first_name])); ?></p>

            <p><?php echo e(__('You can approved the request here:')); ?> <a href="<?php echo e(route('user.admin.verification.detail',['id'=>$user->id])); ?>"><?php echo e(__('View request')); ?></a></p>

            <br>
            <p><?php echo e(__('Regards')); ?>,<br><?php echo e(setting_item('site_title')); ?></p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/User/Views/emails/user-submit-verify-data.blade.php ENDPATH**/ ?>