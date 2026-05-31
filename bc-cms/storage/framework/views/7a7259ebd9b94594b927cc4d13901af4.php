<?php echo $__env->make('Hotel::admin.room.form-detail.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('Hotel::admin.room.form-detail.pricing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('Hotel::admin.room.form-detail.attributes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('Hotel::admin.room.form-detail.ical', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php if(is_default_lang()): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label ><strong><?php echo e(__('Status')); ?></strong> </label>
                <select name="status"  class="custom-select">
                    <option value="publish" ><?php echo e(__('Publish')); ?></option>
                    <option value="pending"  <?php if($row->status == 'pending'): ?> selected <?php endif; ?> ><?php echo e(__('Pending')); ?></option>
                    <option value="draft"  <?php if($row->status == 'draft'): ?> selected <?php endif; ?> ><?php echo e(__('Draft')); ?></option>
                </select>
            </div>
        </div>
    </div>
<?php endif; ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Hotel/Views/admin/room/form.blade.php ENDPATH**/ ?>