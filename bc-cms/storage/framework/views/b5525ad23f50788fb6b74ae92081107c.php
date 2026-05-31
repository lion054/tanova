<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-20 lg:pb-40 md:pb-20">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"><?php echo e($row->id ? __('Edit: ') . $row->title : __('Add new visa service')); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__('Configure visa service details, pricing, and availability.')); ?></div>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="mb-2">
        <?php if($row->id): ?>
            <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
    <div class="lang-content-box">
        <form action="<?php echo e(route('visa.vendor.store', ['id' => $row->id ?: '-1', 'lang' => request()->query('lang')])); ?>" method="post">
            <?php echo csrf_field(); ?>
            <div class="form-add-service">
                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                    <a data-bs-toggle="tab" data-bs-target="#nav-content" aria-selected="true" class="active"><?php echo e(__('1. Content')); ?></a>
                    <?php if(is_default_lang()): ?>
                        <a data-bs-toggle="tab" data-bs-target="#nav-pricing" aria-selected="false"><?php echo e(__('2. Pricing')); ?></a>
                        <a data-bs-toggle="tab" data-bs-target="#nav-seo" aria-selected="false"><?php echo e(__('3. SEO')); ?></a>
                    <?php endif; ?>
                </div>

                <div class="tab-content" id="nav-tabContent">
                    
                    <div class="tab-pane fade show active" id="nav-content">
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Visa Details')); ?></strong></div>
                            <div class="panel-body">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Title')); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required
                                        value="<?php echo e(old('title', $translation->title ?? '')); ?>"
                                        placeholder="<?php echo e(__('e.g. Zimbabwe Tourist Visa')); ?>">
                                </div>

                                <?php if(is_default_lang()): ?>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Destination Country')); ?></label>
                                    <select name="to_country" class="form-select">
                                        <?php $__currentLoopData = get_country_lists(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($code); ?>" <?php if(old('to_country', $row->to_country) == $code): echo 'selected'; endif; ?>><?php echo e($name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Visa Type')); ?></label>
                                    <select name="type_id" class="form-select">
                                        <option value="">— <?php echo e(__('Select type')); ?> —</option>
                                        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($type->id); ?>" <?php if(old('type_id', $row->type_id) == $type->id): echo 'selected'; endif; ?>><?php echo e($type->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Code')); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" required
                                        value="<?php echo e(old('code', $row->code ?? '')); ?>"
                                        placeholder="<?php echo e(__('Unique alphanumeric code, e.g. ZW-TOURIST')); ?>">
                                    <small class="text-muted"><?php echo e(__('Alphanumeric, dash, and underscore only')); ?></small>
                                </div>
                                <?php endif; ?>

                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Description')); ?></label>
                                    <textarea name="content" class="form-control has-tinymce" rows="8"><?php echo e(old('content', $translation->content ?? '')); ?></textarea>
                                </div>

                                <?php if(is_default_lang()): ?>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Featured Image')); ?></label>
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id', $row->image_id); ?>

                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Status')); ?></label>
                                    <select name="status" class="form-select">
                                        <option value="publish" <?php if(old('status', $row->status) == 'publish'): echo 'selected'; endif; ?>><?php echo e(__('Publish')); ?></option>
                                        <option value="draft"   <?php if(old('status', $row->status) == 'draft'): echo 'selected'; endif; ?>><?php echo e(__('Draft')); ?></option>
                                        <option value="pending" <?php if(old('status', $row->status) == 'pending'): echo 'selected'; endif; ?>><?php echo e(__('Pending Review')); ?></option>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if(is_default_lang()): ?>
                    
                    <div class="tab-pane fade" id="nav-pricing">
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Pricing')); ?></strong></div>
                            <div class="panel-body">
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Price')); ?></label>
                                    <input type="number" step="0.01" name="price" class="form-control"
                                        value="<?php echo e(old('price', $row->price ?? '')); ?>"
                                        placeholder="0.00">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Original Price (before discount, optional)')); ?></label>
                                    <input type="number" step="0.01" name="original_price" class="form-control"
                                        value="<?php echo e(old('original_price', $row->original_price ?? '')); ?>"
                                        placeholder="0.00">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Processing Days')); ?></label>
                                    <input type="number" name="processing_days" class="form-control"
                                        value="<?php echo e(old('processing_days', $row->processing_days ?? '')); ?>"
                                        placeholder="<?php echo e(__('e.g. 5')); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Max Stay Days')); ?></label>
                                    <input type="number" name="max_stay_days" class="form-control"
                                        value="<?php echo e(old('max_stay_days', $row->max_stay_days ?? '')); ?>"
                                        placeholder="<?php echo e(__('e.g. 30')); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label fw-600"><?php echo e(__('Multiple Entry')); ?></label>
                                    <input type="text" name="multiple_entry" class="form-control"
                                        value="<?php echo e(old('multiple_entry', $row->multiple_entry ?? '')); ?>"
                                        placeholder="<?php echo e(__('e.g. Single / Multiple')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="tab-pane fade" id="nav-seo">
                        <?php echo $__env->make('User::frontend.vendor-seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button class="button h-50 px-24 -dark-1 bg-blue-1 text-white" type="submit">
                    <i class="fa fa-save mr-2"></i> <?php echo e(__('Save Changes')); ?>

                </button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/tinymce/js/tinymce/tinymce.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('js/condition.js?_ver=' . config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/manageVisa/detail.blade.php ENDPATH**/ ?>