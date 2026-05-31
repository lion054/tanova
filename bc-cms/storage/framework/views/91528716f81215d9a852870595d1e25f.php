<div class="b-flex b-flex-col b-h-full" x-data="BC_Form_Edit">
    <div class="b-bg-white b-py-4 b-border-solid b-border-0 b-border-b b-border-gray-200">
        <div class="container-fluid">
            <div class="b-text-xl b-text-gray-800"><i class="fa fa-edit"></i> <?php echo e(__("Edit Form")); ?></div>
        </div>
    </div>
    <div class="container-fluid b-flex-1">
        <div class="row">
            <div class="col-md-9 b-overflow-y-auto b-py-5">
                <div class="row">
                    <div class="col-md-1"></div>
                    <div class="col-md-10">
                        <div x-sort="handleSort" x-sort:group="fields" 
                        x-sort:config="{ handle:'.handle' }"
                        class="b-min-h-200 b-bg-white b-p-4 b-rounded" 
                        x-bind:class="fields.length == 0 ? 'b-border-dashed b-border-2 b-border-blue-500 b-rounded-md b-p-4' : ''">
                            <?php echo $__env->make('Form::admin.parts.field-preview', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 b-bg-white b-overflow-y-auto b-border-0 b-border-solid b-border-l b-border-l-gray-200 b-py-3">
                <?php echo $__env->make('Form::admin.parts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
</div>
    <?php
        $__assetKey = '2895362564-1';

        ob_start();
    ?>
    <script>
        const BC_ALL_FIELD_TYPES = <?php echo json_encode($fieldTypes, 15, 512) ?>;
    </script>
    <!-- Alpine Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3.x.x/dist/cdn.min.js"></script>
    <script src="<?php echo e(asset('/module/form/admin/js/edit.js')); ?>"></script>
    <?php
        $__output = ob_get_clean();

        // If the asset has already been loaded anywhere during this request, skip it...
        if (in_array($__assetKey, \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$alreadyRunAssetKeys)) {
            // Skip it...
        } else {
            \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$alreadyRunAssetKeys[] = $__assetKey;

            // Check if we're in a Livewire component or not and store the asset accordingly...
            if (isset($this)) {
                \Livewire\store($this)->push('assets', $__output, $__assetKey);
            } else {
                \Livewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::$nonLivewireAssets[$__assetKey] = $__output;
            }
        }
    ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Form/Views/admin/edit.blade.php ENDPATH**/ ?>