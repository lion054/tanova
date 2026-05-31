<?php if(is_default_lang()): ?>
    <div class="row">
        <div class="col-sm-4">
            <h3 class="form-group-title"><?php echo e(__("Tracking System")); ?></h3>
            <p class="form-group-desc"><?php echo e(__('Config tracking system option')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-title"><strong><?php echo e(__("General Options")); ?></strong></div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Enable Tracking")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="tracking_enable" value="1" <?php if(setting_item('tracking_enable')): ?> checked <?php endif; ?>> <?php echo e(__("Yes,please enable it")); ?> </label>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Do not track these IP address")); ?></label>
                        <div class="form-controls">
                            <textarea name="tracking_ignore_ip"  cols="30" rows="10" class="form-control"><?php echo e(setting_item('tracking_ignore_ip')); ?></textarea>
                        </div>
                        <p><i><?php echo e(__('Example: 14.232.76.172, 162.158.167.62')); ?></i></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Tracking/Views/admin/settings/tracking.blade.php ENDPATH**/ ?>