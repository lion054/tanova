<div class="bc-list-item">
    <div class="row y-gap-10 justify-between items-center pb-20 topbar-search">
        <div class="col-auto">
            <h2 class="text-18">
                <span class="fw-500">
                    <?php if($rows->total() > 1): ?>
                        <?php echo e(__(":count spaces found",['count'=>$rows->total()])); ?>

                    <?php else: ?>
                        <?php echo e(__(":count space found",['count'=>$rows->total()])); ?>

                    <?php endif; ?>
                </span>
            </h2>
        </div>
        <div class="col-auto">
            <div class="control d-flex align-items-center">
                <?php echo $__env->make('Space::frontend.layouts.search-map.orderby', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>

    <div class="row y-gap-20 list-service-item">
        <?php if($rows->total() > 0): ?>
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="item col-12">
                    <div class="border-top-light pt-20">
                        <?php echo $__env->make('Space::frontend.layouts.search.loop-item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="col-lg-12">
                <?php echo e(__("Space not found")); ?>

            </div>
        <?php endif; ?>
    </div>

    <div class="goTrip-bc-pagination">
        <?php echo e($rows->appends(request()->query())->links()); ?>

        <?php if($rows->total() > 0): ?>
            <div class="text-center mt-30 md:mt-10">
                <div class="text-14 text-light-1">
                    <?php echo e(__("Showing :from - :to of :total Spaces",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?>

                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Space/Views/frontend/layouts/search-map/list-item.blade.php ENDPATH**/ ?>