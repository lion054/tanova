<!-- Results count and sort -->
<div class="row y-gap-10 items-center justify-between">
    <div class="col-auto">
        <div class="text-18 fw-500 result-count">
            <?php if($rows->total() > 1): ?>
                <?php echo __(':count visas found', ['count' => $rows->total()]); ?>

            <?php else: ?>
                <?php echo __(':count visa found', ['count' => $rows->total()]); ?>

            <?php endif; ?>
        </div>
    </div>

    <div class="col-auto">
        <div class="row x-gap-20 y-gap-20">
            <div class="col-auto bc-form-order">
                <?php echo $__env->make('Layout::global.search.orderby', ['routeName' => 'visa.search', 'hidden_map_button' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
</div>

<!--End Filter mobile-->
<div class="ajax-search-result">
    <div class="pt-30 mt-30 border-top-light"></div>
    <div class="row y-gap-30">
        <?php if($rows->total() > 0): ?>
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-6 col-xl-4 mb-3 mb-md-4 pb-1">
                    <?php echo $__env->make('Visa::frontend.layouts.search.loop-grid', ['disable_lazyload' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="col-lg-12">
                <?php echo e(__('Visa not found')); ?>

            </div>
        <?php endif; ?>
    </div>

    <div class="bc-pagination">
        <?php echo e($rows->appends(request()->except(['_ajax']))->links('Layout::global.livewire.pagination')); ?>

        <?php if($rows->total() > 0): ?>
            <div class="text-center mt-30 md:mt-10">
                <div class="text-14 text-light-1">
                    <?php echo e(__('Showing :from - :to of :total tours', ['from' => $rows->firstItem(), 'to' => $rows->lastItem(), 'total' => $rows->total()])); ?>

                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/layouts/search/list-item.blade.php ENDPATH**/ ?>