<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__("Page Search")); ?></h3>
        <p class="form-group-desc"><?php echo e(__('Config page search of your website')); ?></p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__("General Options")); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="" ><?php echo e(__("Title Page")); ?></label>
                    <div class="form-controls">
                        <input type="text" name="visa_page_search_title" value="<?php echo e(setting_item_with_lang('visa_page_search_title',request()->query('lang'))); ?>" class="form-control">
                    </div>
                </div>
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Banner Page")); ?></label>
                        <div class="form-controls form-group-image">
                            <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('visa_page_search_banner',$settings['visa_page_search_banner'] ?? ""); ?>

                        </div>
                    </div>
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Layout Search")); ?></label>
                        <div class="form-controls">
                            <select name="visa_layout_search" class="form-control" >
                                <?php $__currentLoopData = config('visa.layouts',['normal'=>__("Normal Layout"),'map'=>__("Map Layout")]); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>)
                                    <option value="<?php echo e($id); ?>" <?php echo e(setting_item('visa_layout_search','normal') == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                    <?php do_action(\Modules\Visa\Hook::VISA_SETTING_AFTER_LAYOUT_SEARCH) ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="" ><?php echo e(__("Limit item per Page")); ?></label>
                                <div class="form-controls">
                                    <input type="number" min="1" name="visa_page_limit_item" placeholder="<?php echo e(__("Default: 9")); ?>" value="<?php echo e(setting_item_with_lang('visa_page_limit_item',request()->query('lang'), 9)); ?>" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php echo $__env->make('Visa::admin.settings.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__("SEO Options")); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#seo_1"><?php echo e(__("General Options")); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#seo_2"><?php echo e(__("Share Facebook")); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#seo_3"><?php echo e(__("Share X")); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content" >
                        <div class="tab-pane active" id="seo_1">
                            <div class="form-group" >
                                <label class="control-label"><?php echo e(__("Seo Title")); ?></label>
                                <input type="text" name="visa_page_list_seo_title" class="form-control" placeholder="<?php echo e(__("Enter title...")); ?>" value="<?php echo e(setting_item_with_lang('visa_page_list_seo_title',request()->query('lang'))); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__("Seo Description")); ?></label>
                                <input type="text" name="visa_page_list_seo_desc" class="form-control" placeholder="<?php echo e(__("Enter description...")); ?>" value="<?php echo e(setting_item_with_lang('visa_page_list_seo_desc',request()->query('lang'))); ?>">
                            </div>
                            <?php if(is_default_lang()): ?>
                                <div class="form-group form-group-image">
                                    <label class="control-label"><?php echo e(__("Featured Image")); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('visa_page_list_seo_image', $settings['visa_page_list_seo_image'] ?? "" ); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                            $seo_share = json_decode(setting_item_with_lang('visa_page_list_seo_share',request()->query('lang'),'[]'),true);
                        ?>
                        <div class="tab-pane" id="seo_2">
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__("Facebook Title")); ?></label>
                                <input type="text" name="visa_page_list_seo_share[facebook][title]" class="form-control" placeholder="<?php echo e(__("Enter title...")); ?>" value="<?php echo e($seo_share['facebook']['title'] ?? ""); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__("Facebook Description")); ?></label>
                                <input type="text" name="visa_page_list_seo_share[facebook][desc]" class="form-control" placeholder="<?php echo e(__("Enter description...")); ?>" value="<?php echo e($seo_share['facebook']['desc'] ?? ""); ?>">
                            </div>
                            <?php if(is_default_lang()): ?>
                                <div class="form-group form-group-image">
                                    <label class="control-label"><?php echo e(__("Facebook Image")); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('visa_page_list_seo_share[facebook][image]',$seo_share['facebook']['image'] ?? "" ); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane" id="seo_3">
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__("X Title")); ?></label>
                                <input type="text" name="visa_page_list_seo_share[twitter][title]" class="form-control" placeholder="<?php echo e(__("Enter title...")); ?>" value="<?php echo e($seo_share['twitter']['title'] ?? ""); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__("X Description")); ?></label>
                                <input type="text" name="visa_page_list_seo_share[twitter][desc]" class="form-control" placeholder="<?php echo e(__("Enter description...")); ?>" value="<?php echo e($seo_share['twitter']['desc'] ?? ""); ?>">
                            </div>
                            <?php if(is_default_lang()): ?>
                                <div class="form-group form-group-image">
                                    <label class="control-label"><?php echo e(__("X Image")); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('visa_page_list_seo_share[twitter][image]', $seo_share['twitter']['image'] ?? "" ); ?>

                                </div>
                            <?php endif; ?>
                        </div>
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
            <p class="form-group-desc"><?php echo e(__('Config review for visa')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel"> 
                <div class="panel-body">
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Enable review system for Visa?")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="visa_enable_review" value="1" <?php if(!empty($settings['visa_enable_review'])): ?> checked <?php endif; ?> /> <?php echo e(__("Yes, please enable it")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("Turn on the mode for reviewing visa")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="visa_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Customer must book a visa before writing a review?")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="visa_enable_review_after_booking" value="1"  <?php if(!empty($settings['visa_enable_review_after_booking'])): ?> checked <?php endif; ?> /> <?php echo e(__("Yes please")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("ON: Only post a review after booking - Off: Post review without booking")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="visa_enable_review:is(1),visa_enable_review_after_booking:is(1)">
                        <label><?php echo e(__("Allow review after making Completed Booking?")); ?></label>
                        <div class="form-controls">
                            <?php
                                $status = config('booking.statuses');
                                $settings_status = !empty($settings['visa_allow_review_after_making_completed_booking']) ? json_decode($settings['visa_allow_review_after_making_completed_booking']) : [];
                            ?>
                            <div class="row">
                                <?php $__currentLoopData = $status; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="col-md-4">
                                        <label><input type="checkbox" name="visa_allow_review_after_making_completed_booking[]" <?php if(in_array($item,$settings_status)): ?> checked <?php endif; ?> value="<?php echo e($item); ?>"  /> <?php echo e(booking_status_to_text($item)); ?> </label>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <small class="form-text text-muted"><?php echo e(__("Pick to the Booking Status, that allows reviews after booking")); ?></small>
                            <small class="form-text text-muted"><?php echo e(__("Leave blank if you allow writing the review with all booking status")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="visa_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review must be approval by admin")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="visa_review_approved" value="1"  <?php if(!empty($settings['visa_review_approved'])): ?> checked <?php endif; ?> /> <?php echo e(__("Yes please")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("ON: Review must be approved by admin - OFF: Review is automatically approved")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="visa_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review number per page")); ?></label>
                        <div class="form-controls">
                            <input type="number" class="form-control" name="visa_review_number_per_page" value="<?php echo e($settings['visa_review_number_per_page'] ?? 5); ?>" />
                            <small class="form-text text-muted"><?php echo e(__("Break comments into pages")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="visa_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review criteria")); ?></label>
                        <div class="form-controls">
                            <div class="form-group-item">
                                <div class="g-items-header">
                                    <div class="row">
                                        <div class="col-md-5"><?php echo e(__("Title")); ?></div>
                                        <div class="col-md-1"></div>
                                    </div>
                                </div>
                                <div class="g-items">
                                    <?php
                                    if(!empty($settings['visa_review_stats'])){
                                    $social_share = json_decode($settings['visa_review_stats']);
                                    ?>
                                    <?php $__currentLoopData = $social_share; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="item" data-number="<?php echo e($key); ?>">
                                            <div class="row">
                                                <div class="col-md-11">
                                                    <input type="text" name="visa_review_stats[<?php echo e($key); ?>][title]" class="form-control" value="<?php echo e($item->title); ?>" placeholder="<?php echo e(__('Eg: Service')); ?>">
                                                </div>
                                                <div class="col-md-1">
                                                    <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php } ?>
                                </div>
                                <div class="text-right">
                                    <span class="btn btn-info btn-sm btn-add-item"><i class="icon ion-ios-add-circle-outline"></i> <?php echo e(__('Add item')); ?></span>
                                </div>
                                <div class="g-more hide">
                                    <div class="item" data-number="__number__">
                                        <div class="row">
                                            <div class="col-md-11">
                                                <input type="text" __name__="visa_review_stats[__number__][title]" class="form-control" value="" placeholder="<?php echo e(__('Eg: Service')); ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if(is_default_lang()): ?>
<hr>

<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__("Booking Deposit")); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__("Booking Deposit Options")); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="form-controls">
                    <label><input type="checkbox" name="visa_deposit_enable" value="1" <?php if(setting_item('visa_deposit_enable')): ?> checked <?php endif; ?> > <?php echo e(__('Yes, please enable it')); ?></label>
                    </div>
                </div>
                <div class="form-group" data-condition="visa_deposit_enable:is(1)">
                    <label ><?php echo e(__('Deposit Amount')); ?></label>
                    <div class="form-controls">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group mb-3">
                                    <input type="number" name="visa_deposit_amount" class="form-control" step="0.1" value="<?php echo e(old('visa_deposit_amount',setting_item('visa_deposit_amount'))); ?>" >
                                    <select name="visa_deposit_type"  class="form-control">
                                        <option value="fixed"><?php echo e(__("Fixed")); ?></option>
                                        <option <?php if(old('visa_deposit_type',setting_item('visa_deposit_type')) == 'percent'): ?> selected <?php endif; ?> value="percent"><?php echo e(__("Percent")); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group" data-condition="visa_deposit_enable:is(1)">
                    <label class="" ><?php echo e(__("Deposit Fomular")); ?></label>
                    <div class="form-controls">
                        <div class="row">
                            <div class="col-md-6">
                                <select name="visa_deposit_fomular" class="form-control" >
                                    <option value="default" <?php echo e(($settings['visa_deposit_fomular'] ?? '') == 'default' ? 'selected' : ''); ?>><?php echo e(__('Default')); ?></option>
                                    <option value="deposit_and_fee" <?php echo e(($settings['visa_deposit_fomular'] ?? '') == 'deposit_and_fee' ? 'selected' : ''); ?>><?php echo e(__("Deposit amount + Buyer free")); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__("Disable visa module?")); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__("Disable visa module")); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="form-controls">
                    <label><input type="checkbox" name="visa_disable" value="1" <?php if(setting_item('visa_disable')): ?> checked <?php endif; ?> > <?php echo e(__('Yes, please disable it')); ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/admin/settings/visa.blade.php ENDPATH**/ ?>