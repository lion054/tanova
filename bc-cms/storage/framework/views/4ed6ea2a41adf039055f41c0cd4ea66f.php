
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('dist/frontend/module/support/css/support.css?_v='.config('app.asset_version'))); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Support::frontend.layouts.topic.search-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="topic-by-category py-4">
        <div class="container">
            <div class="topic-category-list">
                <div class="row">
                    <?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $tran = $cat->translate() ?>
                        <div class="col-md-4 mb-5">
                            <div class="category-item h-100 p-4">
                                <div class="d-flex justify-content-between">
                                    <h3 class="cat-name text-20 font-weight-bold mb-3">
                                        <a href="<?php echo e($cat->getDetailUrl()); ?>">
                                            <i class="fa fa-folder mr-2"></i> <?php echo e($tran->name); ?></a>
                                    </h3>
                                </div>
                                <div class="topic-list pl-3">
                                    <?php $__currentLoopData = $cat->latestTopics(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php $topic_trans = $topic->translate() ?>
                                        <div class="topic-item mb-2 text-16">
                                            <a href="<?php echo e($topic->getDetailUrl()); ?>">
                                                <i class="fa fa-file-text-o mr-2"></i> <?php echo e($topic_trans->title); ?></a>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <a class="view-all" href="<?php echo e($cat->getDetailUrl()); ?>"><?php echo e(__("View all")); ?>

                                        <i class="fa fa-long-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/index.blade.php ENDPATH**/ ?>