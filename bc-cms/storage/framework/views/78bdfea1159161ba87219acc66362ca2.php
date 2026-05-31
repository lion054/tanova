
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="mb40">
            <div class="d-flex justify-content-between">
                <h1 class="title-bar"><?php echo e($group['name']); ?></h1>
            </div>
            <hr>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-2">
                <div class="card sticky-top" style="top: 70px; z-index: 100;">
                    <div class="card-header d-flex align-items-center">
                        <strong>
                            <i class="fa fa-cogs"></i> <?php echo e(__('Main Settings')); ?></strong>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a class="list-group-item list-group-item-action <?php if($current_group == $id): ?> active <?php endif; ?>"
                                href="<?php echo e(route('core.admin.settings.index', ['group' => $id])); ?>">
                                <?php if(!empty($setting['icon'])): ?>
                                    <i class="<?php echo e($setting['icon']); ?>"></i>
                                <?php endif; ?>
                                <?php echo e($setting['title']); ?>

                                <?php if(!empty($setting['is_pro'])): ?>
                                    <span class="badge badge-warning ml-1" style="width: auto">PRO</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-10">
                <form action="<?php echo e(route('core.admin.settings.store', ['group' => $current_group])); ?>" method="post"
                    autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <div class="sticky-top <?php if(!setting_item('site_enable_multi_lang')): ?> panel px-3 py-2 d-flex justify-content-end <?php endif; ?>"
                        style="top: <?php echo e(setting_item('site_enable_multi_lang') ? '56px' : '70px'); ?>; z-index: 100;">
                        <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div class=" <?php if(setting_item('site_enable_multi_lang')): ?> position-absolute <?php endif; ?>"
                            style="right: 10px; top: 9px;">

                            <button class="btn btn-success" type="submit"><i class="fa fa-save"></i>
                                <?php echo e(__('Save settings')); ?></button>
                        </div>
                    </div>

                    <div class="lang-content-box">
                        <?php if(empty($group['view'])): ?>
                            <?php echo $__env->make('Core::admin.settings.groups.' . $current_group, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php else: ?>
                            <?php echo $__env->make($group['view'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Core/Views/admin/settings/index.blade.php ENDPATH**/ ?>