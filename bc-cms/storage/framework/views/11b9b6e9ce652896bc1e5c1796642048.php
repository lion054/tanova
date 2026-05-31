
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('Edit Field: :name',['name'=>$row['name'] ?? ''])); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-3">
            </div>
            <div class="col-md-6">
                <form method="post" action="<?php echo e(route('user.admin.role.verifyFieldsStore')); ?>" class="needs-validation" novalidate>
                    <?php echo csrf_field(); ?>
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Edit verification field')); ?></strong></div>
                    <div class="panel-body">
                        <?php echo $__env->make('User::admin.role.verifyFieldsForm', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-success"><?php echo e(__('Save Changes')); ?></button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/User/Views/admin/role/verifyFieldsEdit.blade.php ENDPATH**/ ?>