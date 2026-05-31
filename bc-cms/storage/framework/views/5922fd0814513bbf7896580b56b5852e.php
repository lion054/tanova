
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('Page')); ?></h1>
            <div class="title-actions">
                <a href="<?php echo e(route('user.admin.permission.create')); ?>" class="btn btn-primary"><?php echo e(__('Add new permission')); ?></a>
            </div>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="panel">
            <div class="panel-title"><?php echo e(__('All Permission')); ?></div>
            <div class="panel-body">
                <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th width="60px"><input type="checkbox" class="check-all"></th>
                        <th><?php echo e(__('Name')); ?></th>
                        <th><?php echo e(__('Date')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?php echo e($row->id); ?>"></td>
                            <td class="title">
                                <a href="<?php echo e(route('user.admin.permission.detail',['id'=>$row->id])); ?>"><?php echo e($row->name); ?></a>
                            </td>
                            <td><?php echo e(display_date($row->updated_at)); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                </div>
                <?php echo e($rows->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/User/Views/admin/permission/index.blade.php ENDPATH**/ ?>