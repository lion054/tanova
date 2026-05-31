<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('General Settings')); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label><?php echo e(__('Teacher Role')); ?></label>
                        <div class="form-controls">
                            <select name="teacher_role_id" class="form-control">
                                <?php $__currentLoopData = \Modules\User\Models\Role::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->id); ?>"
                                        <?php echo e(setting_item('teacher_role_id', 2) == $role->id ? 'selected' : ''); ?>>
                                        <?php echo e(ucfirst($role->name)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                <?php else: ?>
                    <p><?php echo e(__('You can edit on main lang.')); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Teacher/Views/admin/settings/teacher.blade.php ENDPATH**/ ?>