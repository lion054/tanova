<div class="btn-upload-private-wrap" x-data="FormFilePicker">
    <div class="private-file-lists mb-2 row">
        <?php if(!empty($data[$field['id']])): ?>
        <?php
            $old = JSON_decode($data[$field['id']], true) ?? [];
            $files = !empty($field['multiple']) ? $old : [$old];
        ?>
            <?php $__currentLoopData = $files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-3">
                    <img style="max-width: 100%; height:auto;" src="<?php echo e(route('simple-form.upload-preview',['path' => $file['path'] ?? '','v' => uniqid()])); ?>" alt="<?php echo e($file['name']); ?>">
                    <a target="_blank" href="<?php echo e(route('simple-form.upload-preview',['path' => $file['path'] ?? '','v' => uniqid()])); ?>" class="file-item"><?php echo e($file['name']); ?> <i class="fa fa-download"></i></a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
    <?php if(empty($only_show_data)): ?>
        <span class="btn btn-primary btn-sm position-relative" x-bind:disabled="loading"><i class="fa fa-upload"></i> <?php echo e(__('Select File')); ?>

            <input x-bind:disabled="loading" x-on:change="upload($event)" data-options="<?php echo e(json_encode($options)); ?>" data-id="<?php echo e($field['id']); ?>" class="btn-upload-private-file position-absolute" style="top:0;left:0;right:0;bottom:0;opacity:0;" accept="<?php echo e(implode(',', $field['mime_types'])); ?>" data-multiple="" type="file" >
        </span>
    <?php else: ?>
        <?php if(empty($field['data'])): ?>
            <div><strong><?php echo e(__('N/A')); ?></strong></div>
        <?php endif; ?>
    <?php endif; ?>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Form/Views/frontend/field/file_picker.blade.php ENDPATH**/ ?>