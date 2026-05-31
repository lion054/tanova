<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__("Support Options")); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__("Ticket Options")); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class=""><?php echo e(__("Ticket Assign To")); ?></label>
                    <div>
                        <?php $__currentLoopData = \Modules\User\Models\Role::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label>
                                <input
                                        type="checkbox"
                                        name="support_ticket_assign_role[]"
                                        value="<?php echo e($role->id); ?>" <?php echo e(in_array($role->id,setting_item_array('support_ticket_assign_role')) ? 'checked' : ''); ?>><?php echo e(ucfirst($role->name)); ?>

                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class=""><?php echo e(__("Supporter View Type")); ?></label>
                    <select class="form-control" name="support_ticket_view_type">
                        <option value=""><?php echo e(__("Per user [Default]")); ?></option>
                        <option
                                <?php if(setting_item('support_ticket_view_type') == 'all'): ?> selected <?php endif; ?> value="all"
                        ><?php echo e(__("Supporter see all")); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/admin/settings/support.blade.php ENDPATH**/ ?>