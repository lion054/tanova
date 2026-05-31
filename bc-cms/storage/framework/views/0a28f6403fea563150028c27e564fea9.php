<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($translation->name); ?>" placeholder="<?php echo e(__("Level name")); ?>" name="name" class="form-control">
</div>
<?php if(is_default_lang()): ?>
    <div class="form-group">
        <label><?php echo e(__("Status")); ?></label>
        <select name="status" class="form-control">
            <option value="publish"><?php echo e(__("Publish")); ?></option>
            <option value="draft" <?php if($row->status=='draft'): ?> selected <?php endif; ?>><?php echo e(__("Draft")); ?></option>
        </select>
    </div>

<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/level/form.blade.php ENDPATH**/ ?>