<!-- Results count and sort -->
<div class="row y-gap-10 items-center justify-between">
    <div class="col-auto">
        <div class="text-18 fw-500 result-count">
            <?php if($rows->total() > 1): ?>
                <?php echo __(":count events found",['count'=>$rows->total()]); ?>

            <?php else: ?>
                <?php echo __(":count event found",['count'=>$rows->total()]); ?>

            <?php endif; ?>
        </div>
    </div>

    <div class="col-auto">
        <div class="row x-gap-20 y-gap-20">
            <div class="col-auto bc-form-order">
                <?php echo $__env->make('Layout::global.search.orderby',['routeName'=>'event.search'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
</div>
<!-- End Results count and sort -->

<div class="ajax-search-result">
    <?php echo $__env->make('Event::frontend.ajax.search-result', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Event/Views/frontend/layouts/search/list-item.blade.php ENDPATH**/ ?>