<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($row->name??''); ?>" placeholder="<?php echo e(__("Name")); ?>" name="name" class="form-control">
</div>
<div class="form-group">
    <label class="control-label"><?php echo e(__("Logo")); ?></label>
    <div class="form-group-image">
        <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id',$row->image_id??''); ?>

    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Flight/Views/admin/airline/form.blade.php ENDPATH**/ ?>