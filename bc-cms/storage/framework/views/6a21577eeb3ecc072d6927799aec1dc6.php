<label for="data_<?php echo e($field['id']); ?>"><?php echo e($field['label']); ?>

    <?php if(strpos($field['rules'], 'required') !== false): ?>
        <span class="text-danger">*</span>
    <?php endif; ?>
</label><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Form/Views/frontend/field/label.blade.php ENDPATH**/ ?>