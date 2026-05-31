<?php
    $style = $style ?? 'default';
    $classes = ' form-search-all-service mainSearch bg-white px-10 py-10 lg:px-20 lg:pt-5 lg:pb-20 rounded-4 mt-30';
    $button_classes = ' -dark-1 py-15 col-12 bg-blue-1 text-white w-100 rounded-4';
    if ($style == 'normal') {
        $classes =
            ' px-10 py-10 lg:px-20 lg:pt-5 lg:pb-20 rounded-100 form-search-all-service mainSearch -w-900 bg-white';
        $button_classes = ' -dark-1 py-15 h-60 col-12 rounded-100 bg-blue-1 text-white w-100';
    }
    if ($style == 'normal2') {
        $classes = 'mainSearch bg-white pr-20 py-20 lg:px-20 lg:pt-5 lg:pb-20 rounded-4 shadow-1';
        $button_classes = ' -dark-1 py-15 h-60 col-12 rounded-100 bg-blue-1 text-white w-100';
    }
    if ($style == 'carousel_v2') {
        $classes = ' w-100';
        $button_classes = ' -dark-1 py-15 px-35 h-60 col-12 rounded-4 bg-yellow-1 text-dark-1';
    }
    if ($style == 'map') {
        $classes = ' w-100';
        $button_classes = ' -dark-1 size-60 col-12 rounded-4 bg-blue-1 text-white';
    }
    if ($style == 'hotel_carousel') {
        $classes =
            ' form-search-all-service mainSearch bg-white px-10 py-10 lg:px-20 lg:pt-5 lg:pb-20 rounded-4 mt-30 form-search-service-hotel';
    }
?>
<form wire:submit.prevent="submit" method="get" class="gotrip_form_search bc_form_search bc_form form <?php echo e($classes); ?>"
    method="get">
    <?php if(!empty(Request::query('_layout'))): ?>
        <input type="hidden" name="_layout" value="<?php echo e(Request::query('_layout')); ?>">
    <?php endif; ?>
    <?php 
        $visa_search_fields = setting_item_array('visa_search_fields');
        $hotel_search_fields = array_values(
            \Illuminate\Support\Arr::sort($visa_search_fields, function ($value) {
                return $value['position'] ?? 0;
            }),
        );
    ?>
    <div class="field-items">
        <div class="row w-100 m-0">
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
        <button class="mainSearch__submit button <?php echo e($button_classes); ?>" type="submit">
            <i class="icon-search text-20 mr-10"></i>
            <span class="text-search"><?php echo e(__('Search')); ?></span>
        </button>
    </div>
</form>

    <?php
        $__scriptKey = '2924714692-1';
        ob_start();
    ?>
    <script>
        $('.form-date-search', $wire.$el).each(function() {
            var parent = $(this),
                check_in_input = $('.check-in-input', parent),
                check_out_input = $('.check-out-input', parent);

            check_in_input.on('change', function() {
                $wire.set('start', $(this).val(), false);
            });
            check_out_input.on('change', function() {
                $wire.set('end', $(this).val(), false);
            });
        });
        $('[name="to_country"]', $wire.$el).on('change', function() {
            $wire.set('to_country', $(this).val(), false);
        });
        $('[name="visa_type"]', $wire.$el).on('change', function() {
            $wire.set('visa_type', $(this).val(), false);
        });
    </script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/components/search-form/index.blade.php ENDPATH**/ ?>