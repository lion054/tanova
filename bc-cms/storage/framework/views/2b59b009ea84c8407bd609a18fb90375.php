
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-60 lg:pb-40 md:pb-32">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"><?php echo e(__("Manage Rooms")); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__('AI-native property management. Live inventory, smart pricing, automated operations.')); ?></div>
        </div>
        <div class="col-auto">
            <div class="d-flex">
                <a href="<?php echo e(route('hotel.vendor.edit',['id'=>$hotel->id])); ?>" class="btn btn-info mr-2"><i class="fa fa-hand-o-right"></i> <?php echo e(__("Back to hotel")); ?></a>
                <a href="<?php echo e(route('hotel.vendor.room.availability.index',['hotel_id'=>$hotel->id])); ?>" class="btn btn-warning mr-2"><i class="fa fa-calendar"></i> <?php echo e(__("Availability Rooms")); ?></a>
                <a href="<?php echo e(route("hotel.vendor.room.create",['hotel_id'=>$hotel->id])); ?>" class="btn btn-success mr-2"><i class="fa fa-plus" aria-hidden="true"></i> <?php echo e(__("Add Room")); ?></a>
            </div>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php if($rows->total() > 0): ?>
        <div class="bc-list-item py-30 px-30 rounded-4 bg-white shadow-3">
            <div class="list-item mt-0">
                <div class="row">
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-md-12">
                            <?php echo $__env->make('Hotel::frontend.vendorHotel.room.loop-list', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="bc-pagination mt-0 mb-0">
                <span class="count-string"><?php echo e(__("Showing :from - :to of :total Rooms",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
                <?php echo e($rows->appends(request()->query())->links()); ?>

            </div>
        </div>
    <?php else: ?>
        <?php echo e(__("No Room")); ?>

    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/vendorHotel/room/index.blade.php ENDPATH**/ ?>