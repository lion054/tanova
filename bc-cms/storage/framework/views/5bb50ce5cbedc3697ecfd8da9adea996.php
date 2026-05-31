<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Agency Content")); ?></strong></div>
    <div class="panel-body">
        <div class="form-group">
            <label><?php echo e(__("Name")); ?></label>
            <input type="text" value="<?php echo e(old('name',$translation->name)); ?>" placeholder="<?php echo e(__("Agency name")); ?>" name="name" class="form-control">
        </div>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Content")); ?></label>
            <div class="">
                <textarea name="content" class="d-none has-ckeditor" cols="30" rows="10"><?php echo e(old('content',$translation->content)); ?></textarea>
            </div>
        </div>
        <?php if(is_default_lang()): ?>
            <div class="row form-group">
                <div class="col-md-6 col-sm-12">
                    <label class="control-label"><?php echo e(__("Office")); ?></label>
                    <input type="text" name="office" class="form-control" value="<?php echo e($row->office ? $row->office : old('office')); ?>" placeholder="<?php echo e(__("Office")); ?>">
                </div>
                <div class="col-md-6 col-sm-12">
                    <label class="control-label"><?php echo e(__("Mobile")); ?></label>
                    <input type="text" name="mobile" class="form-control" value="<?php echo e($row->mobile ? $row->mobile : old('mobile')); ?>" placeholder="<?php echo e(__("Mobile")); ?>">
                </div>
            </div>
            <div class="row form-group">
                <div class="col-md-6 col-sm-12">
                    <label class="control-label"><?php echo e(__("Email")); ?></label>
                    <input type="text" name="email" class="form-control" value="<?php echo e($row->email ? $row->email : old('email')); ?>" placeholder="<?php echo e(__("Email")); ?>">
                </div>
                <div class="col-md-6 col-sm-12">
                    <label class="control-label"><?php echo e(__("Fax")); ?></label>
                    <input type="text" name="fax" class="form-control" value="<?php echo e($row->fax ? $row->fax : old('fax')); ?>" placeholder="<?php echo e(__("Fax")); ?>">
                </div>
            </div>
        <?php endif; ?>

        <?php if(is_default_lang()): ?>
            <div class="form-group">
                <label class="control-label"><?php echo e(__("Banner Image")); ?></label>
                <div class="form-group-image">
                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('banner_image_id',$row->banner_image_id); ?>

                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php if(is_default_lang()): ?>
    <?php echo $__env->make('Agency::admin.agency.include.social', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Agency/Views/admin/agency/include/content.blade.php ENDPATH**/ ?>