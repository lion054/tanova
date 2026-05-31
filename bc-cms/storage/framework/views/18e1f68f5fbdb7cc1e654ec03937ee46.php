<?php $topic_trans = $topic->translate(); ?>
<a class="list-group-item list-group-item-action py-3" href="<?php echo e($topic->getDetailUrl()); ?>">
    <div class="topic-item d-flex justify-content-between align-items-center">
        <div>
            <h3 class="topic-name  text-16">
                <i class="fa fa-file-text-o mr-1"></i> <?php echo e($topic_trans->title); ?></h3>
            <div class="ml-3 mt-2 topic-meta">
                <?php if($topic->cat): ?>
                    <?php $cat_trans = $topic->cat->translate() ?>
                    <span class="mr-3">
                        <i class="fa fa-folder-o mr-1"></i> <?php echo e($cat_trans->name ?? ''); ?></span>
                <?php endif; ?>
                <span>
                    <i class="fa fa-clock-o mr-1"></i> <?php echo e(display_datetime($topic->updated_at ? : $topic->created_at)); ?>

                </span>
            </div>
        </div>
        <div>
            <?php if($topic->views): ?>
                <span class="text-838793">
                    <i class="fa fa-eye mr-2"></i><?php echo e($topic->views); ?>

                </span>
            <?php endif; ?>
        </div>
    </div>
</a>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/topic/loop.blade.php ENDPATH**/ ?>