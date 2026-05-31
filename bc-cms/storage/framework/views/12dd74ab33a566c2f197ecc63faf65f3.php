<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e(__("Vendor Plans")); ?></h1>
        <div class="title-actions">
            <a href="<?php echo e(route('vendor.admin.plan.create')); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo e(__("Add Plan")); ?></a>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="panel">
        <div class="panel-body">
            <form action="" method="post" class="bc-form-item">
                <?php echo csrf_field(); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th width="60px"><input type="checkbox" class="check-all"></th>
                            <th><?php echo e(__('Name')); ?></th>
                            <th width="160px"><?php echo e(__('Monthly Price')); ?></th>
                            <th width="160px"><?php echo e(__('Annual Price')); ?></th>
                            <th width="130px"><?php echo e(__('Commission')); ?></th>
                            <th width="100px"><?php echo e(__('Status')); ?></th>
                            <th width="120px"><?php echo e(__('Actions')); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if($rows->total() > 0): ?>
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="status-<?php echo e($row->status); ?>">
                                <td><input type="checkbox" name="ids[]" class="check-item" value="<?php echo e($row->id); ?>"></td>
                                <td>
                                    <a href="<?php echo e(route('vendor.admin.plan.edit',['id'=>$row->id])); ?>">
                                        <strong><?php echo e($row->name); ?></strong>
                                    </a>
                                </td>
                                <td><?php echo e(format_money($row->price)); ?></td>
                                <td><?php echo e($row->price_annual ? format_money($row->price_annual) : '—'); ?></td>
                                <td><?php echo e($row->base_commission); ?>%</td>
                                <td>
                                    <span class="badge badge-<?php echo e($row->status == 'publish' ? 'success' : 'secondary'); ?>">
                                        <?php echo e($row->status == 'publish' ? __('Published') : __('Draft')); ?>

                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('vendor.admin.plan.edit',['id'=>$row->id])); ?>" class="btn btn-sm btn-info">
                                        <i class="fa fa-edit"></i> <?php echo e(__('Edit')); ?>

                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <tr><td colspan="7"><?php echo e(__("No plans found. Create your first plan.")); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex">
                        <select name="action" class="form-control mr-2" style="width:auto">
                            <option value=""><?php echo e(__('-- Bulk Action --')); ?></option>
                            <option value="publish"><?php echo e(__('Publish')); ?></option>
                            <option value="draft"><?php echo e(__('Draft')); ?></option>
                            <option value="delete"><?php echo e(__('Delete')); ?></option>
                        </select>
                        <button type="submit" class="btn btn-secondary"><?php echo e(__('Apply')); ?></button>
                    </div>
                    <?php echo e($rows->links()); ?>

                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/plan/index.blade.php ENDPATH**/ ?>