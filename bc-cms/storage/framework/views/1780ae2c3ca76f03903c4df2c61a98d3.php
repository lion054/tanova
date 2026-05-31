<div class="form-group">
    <label><?php echo e(__('Name')); ?></label>
    <input type="text" required value="<?php echo e($row->name); ?>" placeholder=" <?php echo e(__('Tag name')); ?>" name="name" class="form-control">
</div>
<?php if(is_default_lang()): ?>
<div class="form-group">
    <label><?php echo e(__('Slug')); ?></label>
    <input type="text" value="<?php echo e($row->slug); ?>" placeholder=" <?php echo e(__('Tag Slug')); ?>" name="slug" class="form-control">
</div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/admin/topic/tag/form.blade.php ENDPATH**/ ?>