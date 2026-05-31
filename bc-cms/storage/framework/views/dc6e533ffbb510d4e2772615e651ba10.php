<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Team Members')); ?></h3>
        <p class="form-group-desc"><?php echo e(__('Change your config vendor team members')); ?></p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <div class="form-controls">
                            <div class="form-group">
                                <label> <input type="checkbox" <?php if(setting_item('vendor_team_enable')): ?> checked <?php endif; ?> name="vendor_team_enable" value="1"> <?php echo e(__("Team Member enable?")); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-controls">
                            <div class="form-group">
                                <label> <input type="checkbox" <?php if(setting_item('vendor_team_auto_approved')): ?> checked <?php endif; ?> name="vendor_team_auto_approved" value="1"> <?php echo e(__("Auto-approve team member request?")); ?></label>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p><?php echo e(__('You can edit on main lang.')); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/settings/team.blade.php ENDPATH**/ ?>