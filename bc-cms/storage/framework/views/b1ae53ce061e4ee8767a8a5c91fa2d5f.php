
<?php $__env->startSection('content'); ?>
    <div class="b-container">
        <div class="b-panel">
            <h1><?php echo e(__("Hello :name",['name'=>$user->business_name ? $user->business_name : $user->first_name])); ?></h1>

            <p><?php echo e(__('You are receiving this email because we updated your vendor verification data.')); ?></p>
            <ul>
                <?php if(!empty($user->verification_fields)): ?>
                    <?php $__currentLoopData = $user->verification_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <strong><?php echo e($field['name']); ?>:</strong>
                            <i><?php if(!empty($field['is_verified'])): ?> <?php echo e(__("Verified")); ?> <?php else: ?> <?php echo e(__("Not verified")); ?> <?php endif; ?></i>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </ul>
            <p><?php echo e(__('You can check your information here:')); ?> <a href="<?php echo e(route('user.verification.index')); ?>"><?php echo e(__('View verification data')); ?></a></p>

            <br>
            <p><?php echo e(__('Regards')); ?>,<br><?php echo e(setting_item('site_title')); ?></p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/User/Views/emails/admin-submit-verify-data.blade.php ENDPATH**/ ?>