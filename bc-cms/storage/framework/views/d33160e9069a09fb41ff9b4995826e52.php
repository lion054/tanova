<div class="form-group">
    <label><?php echo e($field['label']); ?>:</label>
    <?php
    $fieldValue = $fieldsMapById[$field['id']]['value'] ?? null;
    $fieldValueText = $fieldsMapById[$field['id']]['value_text'] ?? null;
    ?>
    <?php if($field['type'] == 'file_picker'): ?>
        <div class="row">
            <?php if(!empty($fieldValue)): ?>
            <div class="col-md-3">
                <img style="max-width: 100%; height:auto;" src="<?php echo e(route('simple-form.upload-preview',['path' => $fieldValue['path'] ?? '','v' => uniqid()])); ?>" alt="<?php echo e($fieldValue['name'] ?? ''); ?>">
                <a target="_blank" href="<?php echo e(route('simple-form.upload-preview',['path' => $fieldValue['path'] ?? '','v' => uniqid()])); ?>"> <?php echo e($fieldValue['name'] ?? ''); ?> <i class="fa fa-download"></i> </a>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <strong><?php echo e($fieldValueText ?? ''); ?></strong>
    <?php endif; ?>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/frontend/components/applicant-form-view/field.blade.php ENDPATH**/ ?>