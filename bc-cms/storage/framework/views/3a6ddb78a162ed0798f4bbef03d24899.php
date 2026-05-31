<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Visa Content")); ?></strong></div>
    <div class="panel-body">
        <div class="form-group magic-field" data-id="title" data-type="title">
            <label class="control-label"><?php echo e(__("Title")); ?></label>
            <input required type="text" value="<?php echo e(old('title',$translation->title)); ?>" placeholder="<?php echo e(__("Title")); ?>" name="title" class="form-control">
        </div>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Slug (Optional, auto generate)")); ?></label>
            <input type="text" value="<?php echo e(old('slug',$row->slug)); ?>" placeholder="<?php echo e(__("Slug")); ?>" name="slug" class="form-control">
        </div>
        <?php if(is_default_lang()): ?>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Country")); ?></label>
            <select wire:model="to_country" name="to_country" class="form-control">
                <?php $__currentLoopData = get_country_lists(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option <?php if(old('to_country',$row->to_country) == $key): ?> selected <?php endif; ?> value="<?php echo e($key); ?>" ><?php echo e($value); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Code")); ?></label>
            <input required type="text" value="<?php echo e(old('code',$row->code)); ?>" placeholder="<?php echo e(__("Code (Unique)")); ?>" name="code" class="form-control">
            <p class="text-muted"><?php echo e(__("Alphanumeric, dash, and underscore are allowed")); ?></p>
        </div>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Visa Type")); ?></label>
            <select wire:model="type_id" name="type_id" class="form-control">
                <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option <?php if(old('type_id',$row->type_id) == $value->id): ?> selected <?php endif; ?> value="<?php echo e($value->id); ?>" ><?php echo e($value->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Price")); ?></label>
            <input type="text" value="<?php echo e(old('price',$row->price)); ?>" placeholder="<?php echo e(__("Price")); ?>" name="price" class="form-control">
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo e(__("Original Price (Before Discount, optional)")); ?></label>
            <input type="text" value="<?php echo e(old('original_price',$row->original_price)); ?>" placeholder="<?php echo e(__("Original Price")); ?>" name="original_price" class="form-control" wire:ignore>
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo e(__("Processing Days")); ?></label>
            <input type="text" value="<?php echo e(old('processing_days',$row->processing_days)); ?>" placeholder="<?php echo e(__("Processing Days")); ?>" name="processing_days" class="form-control">
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo e(__("Max Stay Days")); ?></label>
            <input type="text" value="<?php echo e(old('max_stay_days',$row->max_stay_days)); ?>" placeholder="<?php echo e(__("Max Stay Days")); ?>" name="max_stay_days" class="form-control">
        </div>

        <div class="form-group">
            <label class="control-label"><?php echo e(__("Multiple Entry")); ?></label>
            <input type="text" value="<?php echo e(old('multiple_entry',$row->multiple_entry)); ?>" placeholder="<?php echo e(__("Multiple Entry")); ?>" name="multiple_entry" class="form-control">
        </div>
        <?php endif; ?>

        
        <div class="form-group magic-field" data-id="content" data-type="content">
            <label class="control-label"><?php echo e(__("Content")); ?></label>
            <div class="" wire:ignore>
                <textarea name="content" class="d-none has-ckeditor" id="content" cols="30" rows="10"><?php echo e(old('content',$translation->content)); ?></textarea>
            </div>
        </div>
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/admin/visa/content.blade.php ENDPATH**/ ?>