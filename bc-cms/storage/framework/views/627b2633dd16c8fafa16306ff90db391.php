<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__('Page Search')); ?></h3>
        <p class="form-group-desc"><?php echo e(__('Config page search of your website')); ?></p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__('General Options')); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class=""><?php echo e(__('Title Page')); ?></label>
                    <div class="form-controls">
                        <input type="text" name="course_page_search_title"
                            value="<?php echo e(setting_item_with_lang('course_page_search_title', request()->query('lang'))); ?>"
                            class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class=""><?php echo e(__('Sub Title Page')); ?></label>
                    <div class="form-controls">
                        <input type="text" name="course_page_search_sub_title"
                            value="<?php echo e(setting_item_with_lang('course_page_search_sub_title', request()->query('lang'))); ?>"
                            class="form-control">
                    </div>
                </div>
                <?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label class=""><?php echo e(__('Layout Search')); ?></label>
                        <div class="form-controls">
                            <select name="course_layout_search" class="form-control">
                                <option value="style-1"
                                    <?php echo e(($settings['course_layout_search'] ?? '') == 'style-1' ? 'selected' : ''); ?>>
                                    <?php echo e(__('Style 1')); ?></option>
                                <option value="style-2"
                                    <?php echo e(($settings['course_layout_search'] ?? '') == 'style-2' ? 'selected' : ''); ?>>
                                    <?php echo e(__('Style 2')); ?></option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <?php echo do_action(Modules\Course\Hook::COURSE_SETTING_AFTER_LAYOUT_SEARCH); ?>

            </div>
        </div>

        <?php echo $__env->make('Course::admin.settings.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__('SEO Options')); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#seo_1"><?php echo e(__('General Options')); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#seo_2"><?php echo e(__('Share Facebook')); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#seo_3"><?php echo e(__('Share X')); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="seo_1">
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__('Seo Title')); ?></label>
                                <input type="text" name="course_page_list_seo_title" class="form-control"
                                    placeholder="<?php echo e(__('Enter title...')); ?>"
                                    value="<?php echo e(setting_item_with_lang('course_page_list_seo_title', request()->query('lang'))); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__('Seo Description')); ?></label>
                                <input type="text" name="course_page_list_seo_desc" class="form-control"
                                    placeholder="<?php echo e(__('Enter description...')); ?>"
                                    value="<?php echo e(setting_item_with_lang('course_page_list_seo_desc', request()->query('lang'))); ?>">
                            </div>
                            <?php if(is_default_lang()): ?>
                                <div class="form-group form-group-image">
                                    <label class="control-label"><?php echo e(__('Featured Image')); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload(
                                        'course_page_list_seo_image',
                                        $settings['course_page_list_seo_image'] ?? '',
                                    ); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                            $seo_share = json_decode(
                                setting_item_with_lang('course_page_list_seo_desc', request()->query('lang'), '[]'),
                                true,
                            );
                        ?>
                        <div class="tab-pane" id="seo_2">
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__('Facebook Title')); ?></label>
                                <input type="text" name="course_page_list_seo_share[facebook][title]"
                                    class="form-control" placeholder="<?php echo e(__('Enter title...')); ?>"
                                    value="<?php echo e($seo_share['facebook']['title'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__('Facebook Description')); ?></label>
                                <input type="text" name="course_page_list_seo_share[facebook][desc]"
                                    class="form-control" placeholder="<?php echo e(__('Enter description...')); ?>"
                                    value="<?php echo e($seo_share['facebook']['desc'] ?? ''); ?>">
                            </div>
                            <?php if(is_default_lang()): ?>
                                <div class="form-group form-group-image">
                                    <label class="control-label"><?php echo e(__('Facebook Image')); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload(
                                        'course_page_list_seo_share[facebook][image]',
                                        $seo_share['facebook']['image'] ?? '',
                                    ); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane" id="seo_3">
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__('X Title')); ?></label>
                                <input type="text" name="course_page_list_seo_share[twitter][title]"
                                    class="form-control" placeholder="<?php echo e(__('Enter title...')); ?>"
                                    value="<?php echo e($seo_share['twitter']['title'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?php echo e(__('X Description')); ?></label>
                                <input type="text" name="course_page_list_seo_share[twitter][desc]"
                                    class="form-control" placeholder="<?php echo e(__('Enter description...')); ?>"
                                    value="<?php echo e($seo_share['twitter']['title'] ?? ''); ?>">
                            </div>
                            <?php if(is_default_lang()): ?>
                                <div class="form-group form-group-image">
                                    <label class="control-label"><?php echo e(__('X Image')); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload(
                                        'course_page_list_seo_share[twitter][image]',
                                        $seo_share['twitter']['image'] ?? '',
                                    ); ?>

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
            <h3 class="form-group-title"><?php echo e(__('Review Options')); ?></h3>
            <p class="form-group-desc"><?php echo e(__('Config review for course')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-body">
                    <div class="form-group">
                        <label class=""><?php echo e(__('Enable review system for Course?')); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="course_enable_review" value="1"
                                    <?php if(!empty($settings['course_enable_review'])): ?> checked <?php endif; ?> />
                                <?php echo e(__('Yes, please enable it')); ?> </label>
                            <br>
                            <small
                                class="form-text text-muted"><?php echo e(__('Turn on the mode for reviewing course')); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="course_enable_review:is(1)">
                        <label class=""><?php echo e(__('Customer must book a course before writing a review?')); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="course_enable_review_after_booking" value="1"
                                    <?php if(!empty($settings['course_enable_review_after_booking'])): ?> checked <?php endif; ?> /> <?php echo e(__('Yes please')); ?> </label>
                            <br>
                            <small
                                class="form-text text-muted"><?php echo e(__('ON: Only post a review after booking - Off: Post review without booking')); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="course_enable_review:is(1)">
                        <label class=""><?php echo e(__('Review must be approval by admin')); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="course_review_approved" value="1"
                                    <?php if(!empty($settings['course_review_approved'])): ?> checked <?php endif; ?> /> <?php echo e(__('Yes please')); ?> </label>
                            <br>
                            <small
                                class="form-text text-muted"><?php echo e(__('ON: Review must be approved by admin - OFF: Review is automatically approved')); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="course_enable_review:is(1)">
                        <label class=""><?php echo e(__('Review number per page')); ?></label>
                        <div class="form-controls">
                            <input type="number" class="form-control" name="course_review_number_per_page"
                                value="<?php echo e($settings['course_review_number_per_page'] ?? 5); ?>" />
                            <small class="form-text text-muted"><?php echo e(__('Break comments into pages')); ?></small>
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
            <h3 class="form-group-title"><?php echo e(__('Booking Buyer Fees Options')); ?></h3>
            <p class="form-group-desc"><?php echo e(__('Config buyer fees for course')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-body">
                    <div class="form-group-item">
                        <label class="control-label"><?php echo e(__('Buyer Fees')); ?></label>
                        <div class="g-items-header">
                            <div class="row">
                                <div class="col-md-5"><?php echo e(__('Name')); ?></div>
                                <div class="col-md-3"><?php echo e(__('Price')); ?></div>
                                <div class="col-md-3"><?php echo e(__('Type')); ?></div>
                                <div class="col-md-1"></div>
                            </div>
                        </div>
                        <div class="g-items">
                            <?php $languages = \Modules\Language\Models\Language::getActive(); ?>
                            <?php if(!empty($settings['course_booking_buyer_fees'])): ?>
                                <?php $course_booking_buyer_fees = json_decode($settings['course_booking_buyer_fees'], true); ?>
                                <?php $__currentLoopData = $course_booking_buyer_fees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $buyer_fee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="item" data-number="<?php echo e($key); ?>">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <?php if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale')): ?>
                                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php $key_lang = setting_item('site_locale') != $language->locale ? '_' . $language->locale : ''; ?>
                                                        <div class="g-lang">
                                                            <div class="title-lang"><?php echo e($language->name); ?></div>
                                                            <input type="text"
                                                                name="course_booking_buyer_fees[<?php echo e($key); ?>][name<?php echo e($key_lang); ?>]"
                                                                class="form-control"
                                                                value="<?php echo e($buyer_fee['name' . $key_lang] ?? ''); ?>"
                                                                placeholder="<?php echo e(__('Fee name')); ?>">
                                                            <input type="text"
                                                                name="course_booking_buyer_fees[<?php echo e($key); ?>][desc<?php echo e($key_lang); ?>]"
                                                                class="form-control"
                                                                value="<?php echo e($buyer_fee['desc' . $key_lang] ?? ''); ?>"
                                                                placeholder="<?php echo e(__('Fee desc')); ?>">
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <input type="text"
                                                        name="course_booking_buyer_fees[<?php echo e($key); ?>][name]"
                                                        class="form-control" value="<?php echo e($buyer_fee['name'] ?? ''); ?>"
                                                        placeholder="<?php echo e(__('Fee name')); ?>">
                                                    <input type="text"
                                                        name="course_booking_buyer_fees[<?php echo e($key); ?>][desc]"
                                                        class="form-control" value="<?php echo e($buyer_fee['desc'] ?? ''); ?>"
                                                        placeholder="<?php echo e(__('Fee desc')); ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" min="0"
                                                    name="course_booking_buyer_fees[<?php echo e($key); ?>][price]"
                                                    class="form-control" value="<?php echo e($buyer_fee['price']); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <select name="course_booking_buyer_fees[<?php echo e($key); ?>][type]"
                                                    class="form-control">
                                                    <option <?php if($buyer_fee['type'] == 'one_time'): ?> selected <?php endif; ?>
                                                        value="one_time"><?php echo e(__('One-time')); ?></option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="btn btn-danger btn-sm btn-remove-item"><i
                                                        class="fa fa-trash"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <span class="btn btn-info btn-sm btn-add-item"><i
                                    class="icon ion-ios-add-circle-outline"></i> <?php echo e(__('Add item')); ?></span>
                        </div>
                        <div class="g-more hide">
                            <div class="item" data-number="__number__">
                                <div class="row">
                                    <div class="col-md-5">
                                        <?php if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale')): ?>
                                            <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $key = setting_item('site_locale') != $language->locale ? '_' . $language->locale : ''; ?>
                                                <div class="g-lang">
                                                    <div class="title-lang"><?php echo e($language->name); ?></div>
                                                    <input type="text"
                                                        __name__="course_booking_buyer_fees[__number__][name<?php echo e($key); ?>]"
                                                        class="form-control" value=""
                                                        placeholder="<?php echo e(__('Fee name')); ?>">
                                                    <input type="text"
                                                        __name__="course_booking_buyer_fees[__number__][desc<?php echo e($key); ?>]"
                                                        class="form-control" value=""
                                                        placeholder="<?php echo e(__('Fee desc')); ?>">
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php else: ?>
                                            <input type="text"
                                                __name__="course_booking_buyer_fees[__number__][name]"
                                                class="form-control" value=""
                                                placeholder="<?php echo e(__('Fee name')); ?>">
                                            <input type="text"
                                                __name__="course_booking_buyer_fees[__number__][desc]"
                                                class="form-control" value=""
                                                placeholder="<?php echo e(__('Fee desc')); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" min="0"
                                            __name__="course_booking_buyer_fees[__number__][price]"
                                            class="form-control" value="">
                                    </div>
                                    <div class="col-md-3">
                                        <select __name__="course_booking_buyer_fees[__number__][type]"
                                            class="form-control">
                                            <option value="one_time"><?php echo e(__('One-time')); ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <span class="btn btn-danger btn-sm btn-remove-item"><i
                                                class="fa fa-trash"></i></span>
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
            <h3 class="form-group-title"><?php echo e(__('Teacher Options')); ?></h3>
            <p class="form-group-desc"><?php echo e(__('Teacher config for course')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-body">
                    <div class="form-group">
                        <label class=""><?php echo e(__('Job created by vendor must be approved by admin')); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="course_teacher_create_service_must_approved_by_admin"
                                    value="1" <?php if(!empty($settings['course_teacher_create_service_must_approved_by_admin'])): ?> checked <?php endif; ?> />
                                <?php echo e(__('Yes please')); ?> </label>
                            <br>
                            <small
                                class="form-text text-muted"><?php echo e(__('ON: When vendor posts a service, it needs to be approved by administrator')); ?></small>
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
            <h3 class="form-group-title"><?php echo e(__('Disable course module?')); ?></h3>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-title"><strong><?php echo e(__('Disable course module')); ?></strong></div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-controls">
                            <label><input type="checkbox" name="course_disable" value="1"
                                    <?php if(setting_item('course_disable')): ?> checked <?php endif; ?>>
                                <?php echo e(__('Yes, please disable it')); ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/settings/course.blade.php ENDPATH**/ ?>