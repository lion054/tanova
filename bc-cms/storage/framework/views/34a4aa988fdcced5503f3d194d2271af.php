
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-60 lg:pb-40 md:pb-32">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"> <?php echo e(!empty($recovery) ?__('Recovery Spaces') : __("Manage Spaces")); ?> </h1>
            <div class="text-15 text-light-1"><?php echo e(__('AI-native space and venue management. Bookings, yield, and guest automation in one place.')); ?></div>
        </div>
        <div class="col-auto">
            <?php if(Auth::user()->hasPermission('space_create')&& empty($recovery)): ?>
                <a href="<?php echo e(route("space.vendor.create")); ?>" class="button h-50 px-24 -dark-1 bg-blue-1 text-white">
                    <?php echo e(__("Add Space")); ?> <div class="icon-arrow-top-right ml-15"></div>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if($rows->total() > 0): ?>
        <div class="bc-list-item py-30 px-30 rounded-4 bg-white shadow-3">
            <div class="list-item mt-0">
                <div class="row">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-md-12">
                            <?php echo $__env->make('Space::frontend.manageSpace.loop-list', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="bc-pagination mt-0 mb-0">
                <span class="count-string"><?php echo e(__("Showing :from - :to of :total Spaces",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
                <div class="mt-2">
                    <?php echo e($rows->appends(request()->query())->links()); ?>

                </div>
            </div>
        </div>
    <?php else: ?>
        <?php echo e(__("No Space")); ?>

    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Space/Views/frontend/manageSpace/index.blade.php ENDPATH**/ ?>