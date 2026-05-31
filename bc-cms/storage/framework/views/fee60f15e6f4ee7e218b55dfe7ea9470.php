<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($row->name??''); ?>" placeholder="<?php echo e(__("Name")); ?>" name="name" class="form-control">
</div>
<div class="form-group">
    <label><?php echo e(__("Code")); ?></label>
    <input type="text" value="<?php echo e($row->code??''); ?>" placeholder="<?php echo e(__("Code")); ?>" name="code" class="form-control" required>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Flight/Views/admin/seatType/form.blade.php ENDPATH**/ ?>