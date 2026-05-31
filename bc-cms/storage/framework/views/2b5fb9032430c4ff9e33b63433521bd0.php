<div x-show="selectedFieldId == null" class="b-flex b-flex-col b-gap-5" >
    <?php $__currentLoopData = $fieldGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fieldGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="">
            <div class="b-text-xl b-font-medium b-mb-2"><?php echo e($fieldGroup['name']); ?></div>
            <div class="b-grid b-grid-cols-2 b-gap-2 " x-sort x-sort:config="{ group:{ name: 'fields', put: false, pull : 'clone' }, sort:false, handle:'.handle' }">
                <?php $__currentLoopData = $fieldGroup['types']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fieldType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div 
                    x-sort:item="{isNew: true, type: '<?php echo e($fieldType['type']); ?>', name: '<?php echo e($fieldType['name']); ?>'}" 
                    class="b-group b-flex b-items-center b-justify-between handle b-px-2 b-py-2 b-border b-border-solid b-border-gray-300 b-flex b-items-center b-gap-2 b-cursor-pointer b-rounded-md b-shadow-sm" >
                        <div class="b-flex b-items-center b-gap-2 b-pl-2">
                            <i class="<?php echo e($fieldType['icon']); ?>"></i>
                            <div class="b-text-center"><?php echo e($fieldType['name']); ?></div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         class="size-6 b-invisible group-hover:b-visible b-w-6 b-h-6"
                         x-on:click="addField(<?php echo e(json_encode($fieldType)); ?>)"
                         title="<?php echo e(__("Add Field")); ?>"
                         >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                          </svg>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Form/Views/admin/parts/field-types.blade.php ENDPATH**/ ?>