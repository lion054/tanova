<div class="bc-list-item <?php if(!$rows->count()): ?> not-found <?php endif; ?>">

    <?php if($rows->count()): ?>
        <div class="row y-gap-30 pt-20">
            <?php if($rows->total() > 0): ?>
                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($layout == 'grid'): ?>
                        <div class="col-lg-4 col-sm-6">
                            <?php echo $__env->make('Boat::frontend.layouts.search.loop-grid-2', ['wrap_class' => 'item-loop-wrap'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <?php echo $__env->make('Boat::frontend.layouts.search.loop-list', ['wrap_class' => 'item-loop-wrap inner-loop-wrap'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <div class="col-lg-12">
                    <?php echo e(__('Boat not found')); ?>

                </div>
            <?php endif; ?>
        </div>

        <div class="bc-pagination">
            <?php echo e($rows->appends(request()->except(['_ajax']))->links()); ?>

            <?php if($rows->total() > 0): ?>
                <div class="text-center mt-30 md:mt-10">
                    <div class="text-14 text-light-1">
                        <?php echo e(__('Showing :from - :to of :total boats', ['from' => $rows->firstItem(), 'to' => $rows->lastItem(), 'total' => $rows->total()])); ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="not-found-box">
            <h3 class="n-title"><?php echo e(__("We couldn't find any boats.")); ?></h3>
            <p class="p-desc"><?php echo e(__('Try changing your filter criteria')); ?></p>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/ajax/search-result-map.blade.php ENDPATH**/ ?>