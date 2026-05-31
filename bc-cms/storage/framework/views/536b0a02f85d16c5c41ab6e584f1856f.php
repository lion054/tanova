

<?php $__env->startSection('content'); ?>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                Booking Core
                <span class="badge badge-warning">PRO</span>
            </div>
            <div class="card-body p-0">
                <?php echo $__env->make('Pro::admin.upgrade-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/app/Pro/Views/admin/upgrade.blade.php ENDPATH**/ ?>