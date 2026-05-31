<?php if(!empty($attr)): ?>
    <input type="hidden" name="attr_id" value="<?php echo e($attr->id); ?>">
<?php endif; ?>
<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($translation->name); ?>" placeholder="<?php echo e(__("Term name")); ?>" name="name" class="form-control">
</div>
<?php if(is_default_lang()): ?>
<div class="form-group d-none">
    <label ><?php echo e(__('Image')); ?></label>
    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id',$row->image_id); ?>

</div>

<?php endif; ?>

    
    
        
    
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/terms/form.blade.php ENDPATH**/ ?>