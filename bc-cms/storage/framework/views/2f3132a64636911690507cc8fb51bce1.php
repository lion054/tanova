<form x-init="() => {
    window.Events.init();
    window.Events.ddInit();
    window.Events.liveSearch();
}" wire:submit.prevent="submit" method="get" class="gotrip_form_search bc_form_search bc_form form form-search-sidebar">
    <div class="field-items"  wire:ignore>
        <div class="row w-100 m-0">
            <?php
                $visa_search_fields = setting_item_array('visa_search_fields');
                $visa_search_fields = array_values(
                    \Illuminate\Support\Arr::sort($visa_search_fields, function ($value) {
                        return $value['position'] ?? 0;
                    }),
                );
            ?>
            <?php if(!empty($visa_search_fields)): ?>
                <?php $__currentLoopData = $visa_search_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-lg-<?php echo e($field['size'] ?? '6'); ?> align-self-center px-30 lg:py-20 lg:px-0">
                        <?php $field['title'] = $field['title_'.app()->getLocale()] ?? $field['title'] ?? "" ?>
                        <?php switch($field['field']):
                            case ('visa_type'): ?>
                                <?php echo $__env->make('Visa::frontend.layouts.search.fields.visa_type', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php break; ?>

                            <?php case ('to_country'): ?>
                                <?php echo $__env->make('Visa::frontend.layouts.search.fields.to_country', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php break; ?>

                            <?php case ('guests'): ?>
                                <?php echo $__env->make('Visa::frontend.layouts.search.fields.guests', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php break; ?>
                        <?php endswitch; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="button-item">
        <button class="mainSearch__submit button -dark-1 py-15 h-60 col-12 rounded-100 bg-blue-1 text-white w-100"
            type="submit">
            <i class="icon-search text-20 mr-10"></i>
            <span class="text-search"><?php echo e(__('Search')); ?></span>
        </button>
    </div>
</form>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/components/search-form/index-vertical.blade.php ENDPATH**/ ?>