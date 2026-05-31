<form action="<?php echo e(url(app_get_locale(false, false, '/') . config('event.event_route_prefix'))); ?>"
    class="form bc_form d-flex justify-content-start" id="event_form_search" method="get" onsubmit="return false;">

    <?php $event_map_search_fields = setting_item_array('event_map_search_fields');

        $event_map_search_fields = array_values(
            \Illuminate\Support\Arr::sort($event_map_search_fields, function ($value) {
                return $value['position'] ?? 0;
            }),
        );

    ?>
    <div class="mainSearch bg-white pr-10 py-10 lg:px-20 lg:pt-5 lg:pb-20 bg-light-2 rounded-4">
        <div class="button-grid items-center">

            <?php if(!empty($event_map_search_fields)): ?>
                <?php $__currentLoopData = $event_map_search_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php switch($field['field']):
                        case ('location'): ?>
                            <?php echo $__env->make('Event::frontend.layouts.search-map.fields.location', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                        <?php case ('attr'): ?>
                            <?php echo $__env->make('Event::frontend.layouts.search-map.fields.attr', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                        <?php case ('date'): ?>
                            <?php echo $__env->make('Event::frontend.layouts.search-map.fields.date', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>
                    <?php endswitch; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>

            <div class="button-item">
                <button class="mainSearch__submit button -dark-1 size-60 col-12 rounded-4 bg-blue-1 text-white">
                    <i class="icon-search text-20"></i>
                </button>
            </div>
        </div>
    </div>
</form>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Event/Views/frontend/layouts/search-map/form-search-map.blade.php ENDPATH**/ ?>