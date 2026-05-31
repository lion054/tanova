<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($row->name??''); ?>" placeholder="<?php echo e(__("Name")); ?>" name="name" required class="form-control">
</div>
<div class="form-group">
    <label><?php echo e(__("Code")); ?></label>
    <input type="text" value="<?php echo e($row->code??''); ?>" placeholder="<?php echo e(__("IATA Code")); ?>" name="code" required class="form-control">
</div>
<div class="form-group">
    <label><?php echo e(__("Address")); ?></label>
    <input type="text" value="<?php echo e($row->address??''); ?>" placeholder="<?php echo e(__("Address")); ?>" name="address" class="form-control">
</div>
<div class="form-group">
    <label><?php echo e(__("Location")); ?></label>
    <select name="location_id" class="form-control">
        <option value=""><?php echo e(__("-- Please Select --")); ?></option>
        <?php
        $traverse = function ($array, $prefix = '') use (&$traverse, $row) {
            foreach ($array as $value) {
                if ($value->id == $row->id) {
                    continue;
                }
                $selected = '';
                if ($row->location_id == $value->id)
                    $selected = 'selected';
                printf("<option value='%s' %s>%s</option>", $value->id, $selected, $prefix . ' ' . $value->name);
                $traverse($value->children, $prefix . '-');
            }
        };
        $traverse($locations);
        ?>
    </select>
</div>
<div class="form-group">
    <label><?php echo e(__("Status")); ?></label>
    <select class="form-control" name="status">
        <option value="publish"><?php echo e(__('Publish')); ?></option>
        <option value="draft" <?php if(old('draft',$row->status) == 'draft'): ?> selected <?php endif; ?>><?php echo e(__("Draft")); ?></option>
    </select>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Flight/Views/admin/airport/form.blade.php ENDPATH**/ ?>