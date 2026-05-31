<?php $__env->startPush('css'); ?>
<link href="<?php echo e(asset('dist/frontend/module/social/css/social.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
<style type="text/css">
    .bc_topbar,
    .bc_footer {
        display: none
    }
</style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div class="social-page">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="nav-group">
                    <ul class="nav-items nav flex-column nav-pills">
                        <li class=""><a class="nav-link media active" href="<?php echo e(route('social.index')); ?>">
                                <i class="bc-icon fa fa-paper-plane-o"></i>
                                <span class="media-body"><?php echo e(__('News Feed')); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="nav-group">
                    <div class="nav-title"><?php echo e(__('Forums')); ?></div>
                    <ul class="nav-items nav flex-column nav-pills">
                        <?php $__currentLoopData = $forums; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $forum): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class=""><a class="nav-link media" href="<?php echo e($forum->getDetailUrl()); ?>">
                                    <?php echo $forum->icon_html; ?>

                                    <span class="media-body">
                                        <?php echo e($forum->name); ?>

                                    </span>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-8">
                        <?php echo $__env->make('Social::frontend.posts.create', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('Social::frontend.posts.loop', ['post' => $row], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="col-md-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['container_class' => 'container-fluid', 'header_right_menu' => true, 'header_social' => 1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Social/Views/frontend/index.blade.php ENDPATH**/ ?>