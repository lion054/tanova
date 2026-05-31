<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" wire:model="name" required placeholder="<?php echo e(__("Visa type name")); ?>" name="name" class="form-control">
</div>
<?php if(is_default_lang($lang)): ?>
    
    <div class="form-group">
        <label><?php echo e(__("Status")); ?></label>
        <select wire:model="status" name="status" class="form-control">
            <option value="publish"><?php echo e(__("Publish")); ?></option>
            <option value="draft"><?php echo e(__("Draft")); ?></option>
        </select>
    </div>

<?php endif; ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/admin/type/form.blade.php ENDPATH**/ ?>