
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('dist/frontend/module/support/css/support.css?_v='.config('app.asset_version'))); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Support::frontend.layouts.topic.search-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="topic-lists-wrap topic-detail">
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <div class="page-header">
                        <h1 class="text-24">
                            <i class="fa fa-file-text-o"></i>
                            <?php echo e($page_title); ?></h1>
                        <div class="ml-3 mt-2 topic-meta">
                            <?php if($row->cat): ?>
                                <?php $cat_trans = $row->cat->translate() ?>
                                <span class="mr-3">
                                    <i class="fa fa-folder-o mr-1"></i>
                                    <a href="<?php echo e($row->cat->getDetailUrl()); ?>"><?php echo e($cat_trans->name ?? ''); ?></a>
                                </span>
                            <?php endif; ?>
                            <span>
                                <i class="fa fa-clock-o mr-1"></i> <?php echo e(display_datetime($row->updated_at ? : $row->created_at)); ?>

                            </span>
                        </div>
                    </div>
                    <div class="topic-content py-4">
                        <?php echo clean($translation->content); ?>

                    </div>
                    <?php if(count($row->tags)): ?>
                        <div><strong><?php echo e(__("Tags: ")); ?></strong>
                            <?php $__currentLoopData = $row->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e($tag->getDetailUrl()); ?>"><?php echo e($tag->name); ?></a> <?php if($index < count($row->tags) - 1): ?>
                                    ,
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <?php $rows = $row->related()->limit(5)->with(['translation','cat'])->get(); ?>
                    <h4><?php echo e(__("Related topics")); ?></h4>
                    <div class="topic-lists py-4 list-group">
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('Support::frontend.layouts.topic.loop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php echo $__env->make('Support::frontend.layouts.topic.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/topic/detail.blade.php ENDPATH**/ ?>