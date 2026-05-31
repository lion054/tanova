
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('Role')); ?></h1>
            <div class="title-actions">
                <a href="<?php echo e(route('user.admin.role.verifyFields')); ?>" class="btn btn-warning"><i class="fa fa-check-circle-o"></i> <?php echo e(__('Verify Configs')); ?></a>
                <a href="<?php echo e(route('user.admin.role.permission_matrix')); ?>" class="btn btn-info"><?php echo e(__('Permission Matrix')); ?></a>
                <a href="<?php echo e(route('user.admin.role.create')); ?>" class="btn btn-primary"><?php echo e(__('Add new role')); ?></a>
            </div>
        </div>
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                <?php if(!empty($rows)): ?>
                    <form method="post" action="<?php echo e(route('user.admin.role.bulkEdit')); ?>" class="filter-form filter-form-left d-flex justify-content-start">
                        <?php echo e(csrf_field()); ?>

                        <select name="action" class="form-control">
                            <option value=""><?php echo e(__(" Bulk Actions ")); ?></option>
                            <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                        </select>
                        <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="col-left">
            </div>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="panel">
            <div class="panel-title"><?php echo e(__('All Roles')); ?></div>
            <div class="panel-body">
                <form action="" class="bc-form-item">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th width="60px"><input type="checkbox" class="check-all"></th>
                            <th><?php echo e(__('ID')); ?></th>
                            <th><?php echo e(__('Name')); ?></th>
                            <th><?php echo e(__('Code')); ?></th>
                            <th><?php echo e(__('Date')); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?php echo e($row->id); ?>" class="check-item"></td>
                                <td>#<?php echo e($row->id); ?></td>
                                <td class="title">
                                    <a href="<?php echo e(route('user.admin.role.detail',['id' => $row->id])); ?>"><?php echo e(ucfirst($row->name)); ?></a>
                                </td>
                                <td><?php echo e($row->code); ?></td>
                                <td><?php echo e(display_date($row->updated_at)); ?></td>
                                <td>
                                    <a href="<?php echo e(route('user.admin.role.detail',['id' => $row->id])); ?>" class="btn btn-default btn-sm"><i class="fa fa-edit"></i> <?php echo e(__("Edit")); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </form>
                <?php echo e($rows->withQueryString()->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/User/Views/admin/role/index.blade.php ENDPATH**/ ?>