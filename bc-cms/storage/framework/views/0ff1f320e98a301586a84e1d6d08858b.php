

<?php $__env->startSection('content'); ?>
    <form
        action="<?php echo e(route('support.admin.topic.store',['id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])); ?>"
        method="post"
        class="dungdt-form"
    >
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar"><?php echo e($row->id ? __('Edit topic: :name',['name'=>$row->title]): __('Add new topic')); ?></h1>
                    <?php if($row->slug): ?>
                        <p class="item-url-demo"><?php echo e(__("Permalink")); ?>: <?php echo e(url( (request()->query('lang') ? request()->query('lang').'/' : '').config('support.topic_route_prefix'))); ?>/
                            <a href="#" class="open-edit-input" data-name="slug"><?php echo e($row->slug); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="">
                    <?php if($row->slug): ?>
                        <a class="btn btn-primary btn-sm" href="<?php echo e($row->getDetailUrl(request()->query('lang'))); ?>" target="_blank"><?php echo e(__("View Post")); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="lang-content-box">
                <div class="row">
                    <div class="col-md-9">
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Content')); ?></strong></div>
                            <div class="panel-body">
                                <?php echo csrf_field(); ?>
                                <?php echo $__env->make('Support::admin.topic.form',['row'=>$row], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </div>
                        <?php echo $__env->make('Core::admin/seo-meta/seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-3">
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Publish')); ?></strong></div>
                            <div class="panel-body">
                                <?php if(is_default_lang()): ?>
                                <div>
                                    <label><input <?php if($row->status=='publish'): ?> checked <?php endif; ?> type="radio" name="status" value="publish"> <?php echo e(__("Publish")); ?>

                                    </label></div>
                                <div>
                                    <label><input <?php if($row->status=='draft'): ?> checked <?php endif; ?> type="radio" name="status" value="draft"> <?php echo e(__("Draft")); ?>

                                    </label></div>
                                <?php endif; ?>
                                <div class="text-right">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?php echo e(__('Save Changes')); ?></button>
                                </div>
                            </div>
                        </div>

                        <?php if(is_default_lang()): ?>
                            <div class="panel">
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label><?php echo e(__('Display Order')); ?> </label>
                                        <input type="number" step="1" value="<?php echo e(old('display_order',$row->display_order)); ?>"  name="display_order" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label><?php echo e(__('Category')); ?> </label>
                                        <select name="cat_id" class="form-control">
                                            <option value=""><?php echo e(__('-- Please Select --')); ?> </option>
                                            <?php
                                            $traverse = function ($categories, $prefix = '') use (&$traverse, $row) {
                                                foreach ($categories as $category) {
                                                    $selected = '';
                                                    if ($row->cat_id == $category->id)
                                                        $selected = 'selected';
                                                    printf("<option value='%s' %s>%s</option>", $category->id, $selected, $prefix . ' '.strtoupper($category->product_line).' - ' . $category->name);
                                                    $traverse($category->children, $prefix . '-');
                                                }
                                            };
                                            $traverse($categories);
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label"> <?php echo e(__('Tag')); ?></label>
                                        <div class="">
                                            <input type="text" data-role="tagsinput" value="<?php echo e($row->tag); ?>" placeholder="<?php echo e(__('Enter tag')); ?>" name="tag" class="form-control tag-input">
                                            <br>
                                            <div class="show_tags">
                                                <?php if(!empty($tags)): ?>
                                                    <?php $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="tag_item"><?php echo e($tag->name); ?><span data-role="remove"></span>
                                                    <input type="hidden" name="tag_ids[]" value="<?php echo e($tag->id); ?>">
                                                </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if(is_default_lang()): ?>
                            <div class="panel">
                                <div class="panel-body">
                                    <h3 class="panel-body-title"> <?php echo e(__('Feature Image')); ?></h3>
                                    <div class="form-group">
                                        <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id',$row->image_id); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/admin/topic/detail.blade.php ENDPATH**/ ?>