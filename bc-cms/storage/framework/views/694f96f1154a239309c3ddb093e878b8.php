

<?php $__env->startSection('content'); ?>
    <h2 class="title-bar">
        <?php echo e(__("Verification data")); ?>

    </h2>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="booking-history-manager">
        <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php switch($field['type']):
                case ("email"): ?>
                <?php echo $__env->make('User::frontend.verification.fields.email', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php break; ?>
                <?php case ("phone"): ?>
                <?php echo $__env->make('User::frontend.verification.fields.phone', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php break; ?>
                <?php case ("file"): ?>
                <?php echo $__env->make('User::frontend.verification.fields.file', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php break; ?>
                <?php case ("multi_files"): ?>
                <?php echo $__env->make('User::frontend.verification.fields.multi_files', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php break; ?>
                <?php case ('text'): ?>
                <?php default: ?>
                <?php echo $__env->make('User::frontend.verification.fields.text', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php break; ?>
            <?php endswitch; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <hr>
        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-4">
                <a href="<?php echo e(route('user.verification.update')); ?>" class="btn btn-warning"> <i class="fa fa-edit"></i> <?php echo e(__("Update verification data")); ?> </a>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/verification/index.blade.php ENDPATH**/ ?>