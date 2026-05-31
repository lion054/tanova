

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="panel">
            <div class="panel-body">
                
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="150px"><?php echo e(__('Name')); ?></th>
                                <th width="150px"><?php echo e(__('Name of property')); ?></th>
                                <th width="150px"><?php echo e(__('Name of vendor')); ?></th>
                                <th width="130px"> <?php echo e(__('Phone')); ?></th>
                                <th width="100px"> <?php echo e(__('Email')); ?></th>
                                <th width="500px"> <?php echo e(__('Message')); ?></th>
                                
                            </tr>
                            </thead>
                            <tbody>
                            <?php if($rows->isNotEmpty()): ?>
                                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($row->name); ?></td>
                                        <td><?php echo e($row->nameProperty); ?></td>
                                        <td><?php echo e($row->nameVendor); ?></td>
                                        <td><?php echo e($row->phone); ?></td>
                                        <td><?php echo e($row->email); ?></td>
                                        <td><?php echo e($row->message); ?></td>
                                        
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7"><?php echo e(__("No data")); ?></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                
                <?php echo e($rows->appends(request()->query())->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Property/Views/admin/contact.blade.php ENDPATH**/ ?>