

<?php $__env->startSection('content'); ?>
    <form action="<?php echo e(route('course.admin.store',['id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])); ?>" method="post">
        <?php echo csrf_field(); ?>
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar"><?php echo e($row->id ? __('Edit: ').$row->title : __('Add new course')); ?></h1>
                    <?php if($row->slug): ?>
                        <p class="item-url-demo"><?php echo e(__("Permalink")); ?>: <?php echo e(url('course' )); ?>/<a href="#" class="open-edit-input" data-name="slug"><?php echo e($row->slug); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="">
                    <?php if($row->slug): ?>
                        <a class="btn btn-primary btn-sm" href="<?php echo e($row->getDetailUrl(request()->query('lang'))); ?>" target="_blank"><i class="fa fa-eye"></i> <?php echo e(__("View Course")); ?></a>
                        <div class="btn-group">
                            <button class="btn btn-info dropdown-toggle btn-sm" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo e(__("Course Actions")); ?>

                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="<?php echo e(route('course.admin.lecture.index',['id'=>$row->id])); ?>"><?php echo e(__("Lessons Management")); ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if($row->id): ?>
                <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
            <div class="lang-content-box">
                <div class="row">
                    <div class="col-md-9">
                        <?php echo $__env->make('Course::admin.course.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Course::admin.course.pricing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
                                <div class="panel-title"><strong><?php echo e(__("Availability")); ?></strong></div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label><?php echo e(__('Course Featured')); ?></label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="is_featured" <?php if($row->is_featured): ?> checked <?php endif; ?> value="1"> <?php echo e(__("Enable featured")); ?>

                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if(is_default_lang()): ?>
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__("Teacher Setting")); ?></strong></div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <?php
                                    $user = !empty($row->author_id) ? App\User::find($row->author_id) : false;
                                    \App\Helpers\AdminForm::select2('author_id', [
                                        'configs' => [
                                            'ajax'        => [
                                                'url' => url('/admin/module/user/getForSelect2'),
                                                'dataType' => 'json'
                                            ],
                                            'allowClear'  => true,
                                            'placeholder' => __('-- Select User --')
                                        ]
                                    ], !empty($user->id) ? [
                                        $user->id,
                                        $user->display_name . ' (#' . $user->id . ')'
                                    ] : false)
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if(is_default_lang()): ?>
                            <?php echo $__env->make('Course::admin.course.attributes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <div class="panel">
                                <div class="panel-title"><strong><?php echo e(__('Feature Image')); ?></strong></div>
                                <div class="panel-body">
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

<?php $__env->startSection('script.body'); ?>
    <script>
        jQuery(function ($) {
        })
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/detail.blade.php ENDPATH**/ ?>