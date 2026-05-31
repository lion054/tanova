<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Agent Register')); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <div class="form-controls">
                            <div class="form-group">
                                <label> <input type="checkbox" <?php if(setting_item('vendor_auto_approved') ?? '' == 1): ?> checked <?php endif; ?> name="vendor_auto_approved" value="1"> <?php echo e(__("Agent Auto Approved?")); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo e(__('Agent Role')); ?></label>
                        <div class="form-controls">
                            <select name="vendor_role" class="form-control">

                                <?php $__currentLoopData = \Modules\User\Models\Role::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($role->id); ?>" <?php echo e((setting_item('vendor_role') ?? '') == $role->id ? 'selected': ''); ?>><?php echo e(ucfirst($role->name)); ?></option>
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
<hr>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Agent Profile')); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <div class="form-controls">
                            <div class="form-group">
                                <label> <input type="checkbox" <?php if(setting_item('vendor_show_email') ?? '' == 1): ?> checked <?php endif; ?> name="vendor_show_email" value="1"> <?php echo e(__("Show agent email in profile?")); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-controls">
                            <div class="form-group">
                                <label> <input type="checkbox" <?php if(setting_item('vendor_show_phone') ?? '' == 1): ?> checked <?php endif; ?> name="vendor_show_phone" value="1"> <?php echo e(__("Show agent phone in profile?")); ?></label>
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
<hr>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Content Email Agent Registered')); ?></h3>
        <div class="form-group-desc"><?php echo e(__('Content email send to Agent or Administrator when user registered.')); ?>

            <?php $__currentLoopData = \Modules\User\Listeners\SendVendorRegisterdEmail::CODE; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div><code><?php echo e($value); ?></code></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label> <input type="checkbox" <?php if(setting_item('enable_mail_vendor_registered') ?? '' == 1): ?> checked <?php endif; ?> name="enable_mail_vendor_registered" value="1"> <?php echo e(__("Enable send email to customer when customer registered ?")); ?></label>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label> <input type="checkbox" <?php if(setting_item('enable_mail_vendor_registered') ?? '' == 1): ?> checked <?php endif; ?> disabled name="enable_mail_vendor_registered" value="1"> <?php echo e(__("Enable send email to customer when customer registered ?")); ?></label>
                    </div>
                    <?php if(setting_item('enable_mail_vendor_registered') != 1): ?>
                        <p><?php echo e(__('You must enable on main lang.')); ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="form-group" data-condition="enable_mail_vendor_registered:is(1)">
                    <label><?php echo e(__("Email to agent subject")); ?></label>
                    <div class="form-controls">
                        <textarea name="vendor_subject_email_registered" class="form-control" cols="30" rows="2"><?php echo e(setting_item_with_lang('vendor_subject_email_registered',request()->query('lang'))?? '','New Vendor Registration'); ?></textarea>
                    </div>
                </div>
                <div class="form-group" data-condition="enable_mail_vendor_registered:is(1)">
                    <label><?php echo e(__("Email to agent content")); ?></label>
                    <div class="form-controls">
                        <textarea name="vendor_content_email_registered" class="d-none has-ckeditor" cols="30" rows="10"><?php echo e(setting_item_with_lang('vendor_content_email_registered',request()->query('lang')) ?? ''); ?></textarea>
                    </div>
                </div>


                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label> <input type="checkbox" <?php if(setting_item('admin_enable_mail_vendor_registered') ?? '' == 1): ?> checked <?php endif; ?> name="admin_enable_mail_vendor_registered" value="1"> <?php echo e(__("Enable send email to Administrator when customer registered ?")); ?></label>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label> <input type="checkbox" <?php if(setting_item('admin_enable_mail_vendor_registered') ?? '' == 1): ?> checked <?php endif; ?> disabled name="admin_enable_mail_vendor_registered" value="1"> <?php echo e(__("Enable send email to Administrator when customer registered ?")); ?></label>
                    </div>
                    <?php if(setting_item('admin_enable_mail_vendor_registered') != 1): ?>
                        <p><?php echo e(__('You must enable on main lang.')); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="form-group" data-condition="admin_enable_mail_vendor_registered:is(1)">
                    <label><?php echo e(__("Email to Administrator subject")); ?></label>
                    <div class="form-controls">
                        <textarea name="admin_subject_email_vendor_registered" class="form-control" cols="30" rows="2"><?php echo e(setting_item_with_lang('admin_subject_email_vendor_registered',request()->query('lang'))?? 'New Vendor Registration'); ?></textarea>
                    </div>
                </div>
                <div class="form-group" data-condition="admin_enable_mail_vendor_registered:is(1)">
                    <label><?php echo e(__("Email to Administrator content")); ?></label>
                    <div class="form-controls">
                        <textarea name="admin_content_email_vendor_registered" class="d-none has-ckeditor" cols="30" rows="10"><?php echo e(setting_item_with_lang('admin_content_email_vendor_registered',request()->query('lang'))?? ''); ?></textarea>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php if(is_default_lang()): ?>
    <hr>
    <div class="row">
        <div class="col-sm-4">
            <h3 class="form-group-title"><?php echo e(__("Review Options")); ?></h3>
            <p class="form-group-desc"><?php echo e(__('Config review for agent')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-body">
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Enable review system for Agent?")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="agent_enable_review" value="1" <?php if(!empty(setting_item('agent_enable_review'))): ?> checked <?php endif; ?> /> <?php echo e(__("Yes, please enable it")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("Turn on the mode for reviewing agent")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="agent_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review must be approval by admin")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="agent_review_approved" value="1"  <?php if(!empty(setting_item('agent_review_approved'))): ?> checked <?php endif; ?> /> <?php echo e(__("Yes please")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("ON: Review must be approved by admin - OFF: Review is automatically approved")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="agent_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review number per page")); ?></label>
                        <div class="form-controls">
                            <input type="number" class="form-control" name="agent_review_number_per_page" value="<?php echo e(setting_item('agent_review_number_per_page') ?? 5); ?>" />
                            <small class="form-text text-muted"><?php echo e(__("Break comments into pages")); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Agency/Views/admin/settings/agent.blade.php ENDPATH**/ ?>