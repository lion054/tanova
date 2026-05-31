<?php
    $inputGroupClass = 'form-input';
    switch ($field['type']) {
        case 'checkbox':
        case 'radio':
            $inputGroupClass = 'form-check';
            break;
        default:
            $inputGroupClass = 'form-input';
            break;
    }

?>

<div class=" mb-3  col-<?php echo e($field['col'] ?? 12); ?>" wire:key="<?php echo e($field['id']); ?>">
    <?php $inputClass = isset($errors) && $errors->has($field['id']) ? 'is-invalid' : ''; ?>
    <?php if($field['type'] == 'text'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
            <input <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control has-value <?php echo e($inputClass); ?>"
                id="data_<?php echo e($field['id']); ?>" type="text" wire:model="data.<?php echo e($field['id']); ?>">
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php elseif($field['type'] == 'email'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
            <input <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control has-value <?php echo e($inputClass); ?>"
                id="data_<?php echo e($field['id']); ?>" type="email" wire:model="data.<?php echo e($field['id']); ?>">
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php elseif($field['type'] == 'number'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
            <input <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control has-value <?php echo e($inputClass); ?>"
                id="data_<?php echo e($field['id']); ?>" type="number" wire:model="data.<?php echo e($field['id']); ?>">
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php elseif($field['type'] == 'textarea'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
            <textarea <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?> class="form-control has-value <?php echo e($inputClass); ?>"
                id="data_<?php echo e($field['id']); ?>" wire:model="data.<?php echo e($field['id']); ?>"></textarea>
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php elseif($field['type'] == 'select'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
            <select <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?>
                class="form-control has-value <?php echo e($inputClass); ?>" id="data_<?php echo e($field['id']); ?>"
                wire:model="data.<?php echo e($field['id']); ?>">
                <?php
                if (!empty($field['data_source'])) {
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
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php elseif($field['type'] == 'radio'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
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
                    <label class="d-flex gap-3">
                        <div>
                            <input type="radio" name="<?php echo e($field['id']); ?>" value="<?php echo e($option['value']); ?>"
                                wire:model="data.<?php echo e($field['id']); ?>">
                        </div>
                        <div class="text-14 lh-10 text-light-1 ml-10"><?php echo e($option['label']); ?></div>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php elseif($field['type'] == 'checkbox'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
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
        </div>
    <?php endif; ?>
    <?php if($field['type'] == 'date'): ?>
        <div class="<?php echo e($inputGroupClass); ?>">
            <input <?php if(strpos($field['rules'], 'required') !== false): ?> required <?php endif; ?>
                class="form-control has-value <?php echo e($inputClass); ?>" id="data_<?php echo e($field['id']); ?>" type="date"
                wire:model="data.<?php echo e($field['id']); ?>">
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php endif; ?>

    <?php if($field['type'] == 'file_picker'): ?>
        <div class="">
            <?php echo $__env->make('Form::frontend.field.label', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('Form::frontend.field.file_picker', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
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
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Form/Views/frontend/simple-field.blade.php ENDPATH**/ ?>