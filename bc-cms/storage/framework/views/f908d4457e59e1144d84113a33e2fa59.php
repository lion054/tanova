<?php if(is_default_lang()): ?>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Checkout Page')); ?></h3>
        <p class="form-group-desc"><?php echo e(__('Change your checkout page options')); ?></p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="" ><?php echo e(__("Enable Captcha")); ?></label>
                    <div class="form-controls">
                        <label><input type="checkbox" name="order_enable_recaptcha" value="1" <?php if(setting_item('order_enable_recaptcha')): ?> checked <?php endif; ?> /> <?php echo e(__("Yes, please")); ?> </label>
                    </div>
                </div>
                <div class="form-group">
                    <label ><?php echo e(__("Terms & Conditions page")); ?></label>
                    <div class="form-controls">
                        <?php
                            $template = \Modules\Page\Models\Page::find(setting_item('order_term_conditions') );
                            \App\Helpers\AdminForm::select2('order_term_conditions',[
                            'configs'=>[
                                    'ajax'=>[
                                        'url'=>url('/admin/module/page/getForSelect2'),
                                        'dataType'=>'json'
                                    ]
                                ]
                            ],
                            !empty($template->id) ? [$template->id,$template->title] :false
                            )
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<hr>
<?php endif; ?>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Invoice Page')); ?></h3>
        <p class="form-group-desc"><?php echo e(__('Change your invoice page options')); ?></p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label><?php echo e(__("Invoice Logo")); ?></label>
                        <div class="form-controls form-group-image">
                            <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('logo_invoice_id',$settings['logo_invoice_id'] ?? ''); ?>

                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class=""><?php echo e(__("Invoice Company Info")); ?></label>
                    <div class="form-controls">
                        <textarea name="invoice_company_info" class="d-none has-tinymce" cols="30" rows="10"><?php echo e(setting_item_with_lang('invoice_company_info',request()->query('lang'))); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<hr>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/admin/settings/order.blade.php ENDPATH**/ ?>