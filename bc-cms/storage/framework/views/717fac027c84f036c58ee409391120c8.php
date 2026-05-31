<div class="panel">
    <div class="panel-title">
        <strong><?php echo e(__('Summary')); ?></strong>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label><?php echo e(__('Booking ID')); ?></label>
                    <input type="text" class="form-control" value="<?php echo e($booking->id); ?>" disabled>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label><?php echo e(__('Booking Status')); ?></label>
                    <select wire:model="status" class="form-control">
                        <option value=""><?php echo e(__('-- Select Status --')); ?></option>
                        <?php if(!empty($statues)): ?>
                            <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($status); ?>"><?php echo e(booking_status_to_text($status)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary"><?php echo e(__('Save Changes')); ?></button>
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Booking/Views/admin/booking/parts/sidebar.blade.php ENDPATH**/ ?>