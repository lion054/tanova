
<?php $__env->startSection('content'); ?>
    <form action="<?php echo e(route('visa.admin.store', $row->id)); ?>" method="post">
        <?php echo csrf_field(); ?>
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar"><?php echo e($row->id ? __('Edit: ').$row->title : __('Add new visa')); ?></h1>
                </div>

                <?php if($row->slug): ?>
                <div>
                    <a href="<?php echo e(route('visa.detail',['slug'=>$row->slug])); ?>" class="btn btn-default"><?php echo e(__('View on website')); ?></a>
                </div>
                <?php endif; ?>
            </div>
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if($row->id): ?>
                <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
            <div class="lang-content-box">
                <div class="row">
                    <div class="col-md-9">
                        <?php echo $__env->make('Visa::admin.visa.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo $__env->make('Core::admin/seo-meta/seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-3">
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Publish')); ?></strong></div>
                            <div class="panel-body">
                                <?php if(is_default_lang()): ?>
                                    <div>
                                        <label><input <?php if($row->status == 'publish'): ?> checked <?php endif; ?> type="radio" name="status" value="publish"> <?php echo e(__("Publish")); ?>

                                        </label></div>
                                    <div>
                                        <label><input <?php if($row->status == 'draft'): ?> checked <?php endif; ?> type="radio" name="status" value="draft"> <?php echo e(__("Draft")); ?>

                                        </label></div>
                                    <div>
                                        <label><input <?php if($row->status == 'pending'): ?> checked <?php endif; ?> type="radio" name="status" value="pending"> <?php echo e(__("Pending")); ?>

                                        </label></div>
                                <?php endif; ?>
                                <div class="text-right">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?php echo e(__('Save Changes')); ?></button>
                                </div>
                            </div>
                        </div>
                        <?php if(is_default_lang()): ?>
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__("Author Setting")); ?></strong></div>
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
                                    ] : false, false, [
                                        'wire:model' => 'author_id',
                                        'wire:ignore' => true,
                                    ])
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="panel">
                            <div class="panel-title"><strong><?php echo e(__('Featured Image')); ?></strong></div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id', $row->image_id); ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('Layout::admin.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/admin/visa/edit.blade.php ENDPATH**/ ?>