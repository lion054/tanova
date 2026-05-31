
<?php $__env->startSection('content'); ?>
    <h2 class="title-bar">
        <?php echo e(!empty($recovery) ?__('Recovery Tours') : __("Manage Tours")); ?>

        <?php if(Auth::user()->hasPermission('tour_create') && empty($recovery)): ?>
            <a href="<?php echo e(route("tour.vendor.create")); ?>" class="btn-change-password"><?php echo e(__("Add Tour")); ?></a>
        <?php endif; ?>
    </h2>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if($rows->total() > 0): ?>
        <div class="bc-list-item">
            <div class="bc-pagination">
                <span class="count-string"><?php echo e(__("Showing :from - :to of :total Tours",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
                <?php echo e($rows->appends(request()->query())->links()); ?>

            </div>
            <div class="list-item">
                <div class="row">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-md-12">
                            <?php echo $__env->make('Tour::frontend.manageTour.loop-list', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="bc-pagination">
                <span class="count-string"><?php echo e(__("Showing :from - :to of :total Tours",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
                <?php echo e($rows->appends(request()->query())->links()); ?>

            </div>
        </div>
    <?php else: ?>
        <?php echo e(__("No Tours")); ?>

    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Tour/Views/frontend/manageTour/index.blade.php ENDPATH**/ ?>