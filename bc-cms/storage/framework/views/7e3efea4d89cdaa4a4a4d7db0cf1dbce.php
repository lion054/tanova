<div class="bc-list-item">
    <div class="row y-gap-10 justify-between items-center">
        <div class="col-auto">
            <div class="text-18">
                <span class="fw-500 result-count">
                    <?php if($rows->total() > 1): ?>
                        <?php echo e(__(":count flights found",['count'=>$rows->total()])); ?>

                    <?php else: ?>
                        <?php echo e(__(":count flight found",['count'=>$rows->total()])); ?>

                    <?php endif; ?>

                </span>
            </div>
        </div>

        <div class="col-auto bc-form-order">
            <?php echo $__env->make('Layout::global.search.orderby',['routeName'=>'flight.search','hidden_map_button'=>1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <div class="ajax-search-result" id="flightFormBook">
        <?php echo $__env->make('Flight::frontend.ajax.search-result', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/layouts/search/list-item.blade.php ENDPATH**/ ?>