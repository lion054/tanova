<?php $userInfo = $post->user ?>
<div class="bc-post-item">
    <div class="post-top">
        <div class="post-author">
            <div class="media">
                <div class="media-left mr-3">
                    <?php if($avatar_url = $userInfo->getAvatarUrl()): ?>
                        <img class="avatar" src="<?php echo e($avatar_url); ?>" alt="<?php echo e($userInfo->getDisplayName()); ?>">
                    <?php else: ?>
                        <span class="s-avatar-text"><?php echo e(ucfirst($userInfo->getDisplayName()[0])); ?></span>
                    <?php endif; ?>
                </div>
                <div class="media-body">
                    <h4 class="media-heading"><?php echo e($userInfo->getDisplayName()); ?></h4>
                    <div class="date"><?php echo e(display_datetime($post->created_at)); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="post-body">
        <?php echo e($post->content); ?>

    </div>
    <div class="post-action nav-pills nav nav-fill">
        <div class="nav-item"><span class="nav-link"><i class="fa fa-thumbs-up"></i> <?php echo e(__('Like')); ?></span></div>
        <div class="nav-item"><span class="nav-link"><i class="fa fa-comments"></i> <?php echo e(__('Comments')); ?></span></div>
        <div class="nav-item"><span class="nav-link"><i class="fa fa-share"></i> <?php echo e(__('Share')); ?></span></div>

    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Social/Views/frontend/posts/loop.blade.php ENDPATH**/ ?>