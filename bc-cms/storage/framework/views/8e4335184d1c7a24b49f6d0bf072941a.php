<a class="list-group-item list-group-item-action py-3" href="<?php echo e($ticket->getDetailUrl()); ?>">
    <div class="topic-item d-flex justify-content-between align-items-center">
        <div class="flex-grow-1">
            <h3 class="topic-name  text-16">
                <i class="fa fa-file-text-o mr-1"></i> <?php echo e($ticket->title); ?>

                <?php if($ticket->last_reply_by != auth()->id()): ?>
                    <i class="fa fa-info-circle" title="<?php echo e(__("Need Response")); ?>" style="color: #ffc107;"></i>
                <?php endif; ?>
            </h3>
            <div class="ml-3 mt-2 topic-meta">
                <?php if(!empty($is_agent)): ?>
                    <span class="mr-1">
                        <i class="fa fa-user-o mr-1"></i> <?php echo e($ticket->customer->display_name ?? ''); ?>

                    </span>
                <?php endif; ?>
                <?php if($ticket->cat): ?>
                    <?php $cat_trans = $ticket->cat->translate() ?>
                    <span class="mr-1">
                        <i class="fa fa-folder-o mr-1"></i> <?php echo e($cat_trans->name ?? ''); ?></span>
                <?php endif; ?>
                <?php if($ticket->last_reply_at): ?>
                    <span>
                        <i class="fa fa-clock-o"></i> <?php echo e(human_time_diff($ticket->last_reply_at)); ?> ago
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="mr-3">
            <?php if($ticket->last_reply): ?>
                <?php echo e($ticket->last_reply->display_name); ?>

            <?php endif; ?>
        </div>
        <div class="div">
            <span class="badge badge-<?php echo e($ticket->status_badge_class); ?>"><?php echo e($ticket->status_text); ?></span>
        </div>
    </div>
</a>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/ticket/loop.blade.php ENDPATH**/ ?>