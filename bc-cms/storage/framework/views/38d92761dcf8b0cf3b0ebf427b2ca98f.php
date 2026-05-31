<div class="select2-container <?php echo e($class); ?>" >
    <div wire:ignore>
        <select id="<?php echo e($id); ?>" name="<?php echo e($name); ?>" style="width: 100%">
            <?php if(!empty($placeholder)): ?>
                <option value=""><?php echo e($placeholder); ?></option>
            <?php endif; ?>
            <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if($selected == $value): ?> selected <?php endif; ?>>
                    <?php echo e($label); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <input type="hidden" wire:model="selected" id="<?php echo e($id); ?>-hidden">
</div>

    <?php
        $__scriptKey = '546362770-0';
        ob_start();
    ?>
<script>
    let select = $('#<?php echo e($id); ?>');

    select.select2({
        allowClear: true,
    });

    // Set initial value from Livewire
    select.val($wire.selected).trigger('change');

    // Sync Select2 -> Livewire
    select.on('change', function () {
        let value = $(this).val();
        $wire.selected = value;
        updateWrapperClass($wire.selected);
    });

    // Sync Livewire -> Select2 (optional: when model changes from outside)
    Livewire.on('refreshSelect2', () => {
        select.val($wire.selected).trigger('change');
    });

    
    // Function to update wrapper class based on selected value
    function updateWrapperClass(selected) {
        let wrapper = select.closest('.select2-container');
        if (selected.length > 0) {
            wrapper.addClass('is-selected');
        } else {
            wrapper.removeClass('is-selected');
        }
    }

    // Cleanup on component destruction
    document.addEventListener('livewire:navigating', () => {
        select.select2('destroy');
    });
</script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/livewire/select2.blade.php ENDPATH**/ ?>