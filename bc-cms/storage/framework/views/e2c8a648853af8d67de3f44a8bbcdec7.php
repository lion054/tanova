<form action="<?php echo e(route('visa.search')); ?>" class="form bc_form d-flex mb-1 py-2" method="get">
    <div class="g-field-search">
        <div class="row d-block nav-select d-flex align-items-end">
            <?php$visa_search_fields = setting_item_array('visa_search_fields');
                                $visa_search_fields = array_values(
                                    \Illuminate\Support\Arr::sort($visa_search_fields, function ($value) {
                                        return $value['position'] ?? 0;
                                    }),
                                );
                        ?> ?>
            <?php if(!empty($visa_search_fields)): ?>
                <?php $__currentLoopData = $visa_search_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $field['title'] = $field['title_'.app()->getLocale()] ?? $field['title'] ?? "" ?>
                    <div class="col-md-<?php echo e($field['size'] ?? '6'); ?> border-right">
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
    <div class="g-button-submit align-self-lg-end">
        <button type="submit" class="btn btn-primary btn-md border-radius-3 mb-xl-0 mb-lg-1 transition-3d-hover">
            <i class="flaticon-magnifying-glass font-size-20 mr-2"></i><?php echo e(__('Search')); ?>

        </button>
    </div>
</form>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/layouts/search/form-search.blade.php ENDPATH**/ ?>