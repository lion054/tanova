

<?php ($location_search_style = setting_item('event_location_search_style')); ?>
<?php
$location_name = "";
$list_json = [];
$traverse = function ($locations, $prefix = '') use (&$traverse, &$list_json, &$location_name) {
    foreach ($locations as $location) {
        $translate = $location->translate();
        if (request()->query('location_id') == $location->id) {
            $location_name = $translate->name;
        }
        $list_json[] = [
            'id' => $location->id,
            'title' => $prefix . ' ' . $translate->name,
        ];
        $traverse($location->children, $prefix . '-');
    }
};
$traverse($list_location);
?>
<div class="searchMenu-loc pr-30 pl-20 lg:py-20 lg:px-0 js-form-dd js-liverSearch">
    <div lass="d-flex" data-x-dd-click="searchMenu-loc">
        <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e(__('Destinations')); ?></h4>

        <div class="text-15 text-light-1 ls-2 lh-16">
            <input type="hidden" name="location_id" class="js-search-get-id" value="<?php echo e($location_id ?? ''); ?>">
            <input autocomplete="off" type="search" placeholder="<?php echo e(__('Where are you going?')); ?>" class="js-search js-dd-focus" value="<?php echo e($location_name ?? ''); ?>" />
        </div>
    </div>
    <div class="searchMenu-loc__field shadow-2 js-popup-window" data-x-dd="searchMenu-loc" data-x-dd-toggle="-is-active">
        <div class="bg-white px-30 py-30 sm:px-0 sm:py-15 rounded-4">
            <div class="y-gap-5 js-results">
                <?php $__currentLoopData = $list_json; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="-link d-block col-12 text-left rounded-4 px-20 py-15 js-search-option" data-id="<?php echo e(@$location['id']); ?>">
                        <div class="d-flex align-items-center">
                            <div class="icon-location-2 text-light-1 text-20 pt-4"></div>
                            <div class="ml-10">
                                <div class="text-15 lh-12 fw-500 js-search-option-target"><?php echo e(@$location['title']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Event/Views/frontend/layouts/search-map/fields/location.blade.php ENDPATH**/ ?>