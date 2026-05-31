
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('dist/frontend/module/support/css/support.css?_v='.config('app.asset_version'))); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Support::frontend.layouts.topic.search-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="topic-lists-wrap">
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <div class="page-header">
                        <h1 class="text-24">
                            <?php if(!empty($cat)): ?>
                                <i class="fa fa-folder mr-1"></i>
                            <?php endif; ?>
                            <?php echo e($page_title); ?></h1>
                    </div>
                    <div class="topic-lists py-4 list-group">
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('Support::frontend.layouts.topic.loop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="bc-pagination">
                        <?php echo e($rows->appends(request()->query())->links()); ?>

                        <?php if($rows->total() > 0): ?>
                            <span class="count-string"><?php echo e(__("Showing :from - :to of :total topics",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if(!count($rows)): ?>
                        <div class="alert alert-warning"><?php echo e(__("No topic found")); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <?php echo $__env->make('Support::frontend.layouts.topic.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/topic/index.blade.php ENDPATH**/ ?>