<div class="form-group col-<?php echo e($field['col'] ?? 12); ?>" wire:key="<?php echo e($field['id']); ?>">
    <?php $inputClass = isset($errors) && $errors->has($field['id']) ? 'is-invalid' : '' ?>
    <?php if($field['type'] == 'text'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <input 
        <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> 
        class="form-control <?php echo e($inputClass); ?>" 
        id="data_<?php echo e($field['id']); ?>" 
        type="text" 
        wire:model="data.<?php echo e($field['id']); ?>" 
        placeholder="<?php echo e($field['placeholder'] ?? ''); ?>"
        >
    <?php elseif($field['type'] == 'email'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <input 
        <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> 
        class="form-control <?php echo e($inputClass); ?>" 
        id="data_<?php echo e($field['id']); ?>" 
        type="email" 
        wire:model="data.<?php echo e($field['id']); ?>" 
        placeholder="<?php echo e($field['placeholder'] ?? ''); ?>"
        >

    <?php elseif($field['type'] == 'number'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <input 
        <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> 
        class="form-control <?php echo e($inputClass); ?>" 
        id="data_<?php echo e($field['id']); ?>" 
        type="number" 
        wire:model="data.<?php echo e($field['id']); ?>" 
        placeholder="<?php echo e($field['placeholder'] ?? ''); ?>"
        >

    <?php elseif($field['type'] == 'textarea'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <textarea <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control <?php echo e($inputClass); ?>" id="data_<?php echo e($field['id']); ?>" wire:model="data.<?php echo e($field['id']); ?>" placeholder="<?php echo e($field['placeholder'] ?? ''); ?>"></textarea>
    <?php elseif($field['type'] == 'select'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <select <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control <?php echo e($inputClass); ?>" id="data_<?php echo e($field['id']); ?>" wire:model="data.<?php echo e($field['id']); ?>">
            <?php
            if(!empty($field['data_source'])){
                $field['options'] = $this->getDataSource($field);
            }
            ?>
            <?php if(!empty($field['options'])): ?>
                <option value=""><?php echo e(__('--Select--')); ?></option>
                <?php $__currentLoopData = $field['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($option['value']); ?>"><?php echo e($option['label']); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </select>
    <?php elseif($field['type'] == 'radio'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="<?php $__errorArgs = [$field['id']];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__currentLoopData = $field['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> type="radio" wire:model="data.<?php echo e($field['id']); ?>" id="<?php echo e($field['id']); ?>_<?php echo e($option['value']); ?>" name="<?php echo e($field['id']); ?>" value="<?php echo e($option['value']); ?>">
                <label for="<?php echo e($field['id']); ?>_<?php echo e($option['value']); ?>"><?php echo e($option['label']); ?></label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php elseif($field['type'] == 'checkbox'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="<?php $__errorArgs = [$field['id']];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__currentLoopData = $field['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="checkbox" wire:model="data.<?php echo e($field['id']); ?>" value="<?php echo e($option['value']); ?>">
                <label for="<?php echo e($option['value']); ?>"><?php echo e($option['label']); ?></label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
    <?php if($field['type'] == 'date'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <input <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control <?php echo e($inputClass); ?>" id="data_<?php echo e($field['id']); ?>" type="date" wire:model="data.<?php echo e($field['id']); ?>" placeholder="<?php echo e($field['placeholder'] ?? ''); ?>">
    <?php endif; ?>

    <?php if($field['type'] == 'file_picker'): ?>
        <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Form::frontend.field.file_picker', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <?php $__errorArgs = [$field['id']];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="invalid-feedback d-block"> <?php echo e($message); ?> </div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Form/Views/frontend/simple-field.blade.php ENDPATH**/ ?>