<?php
$replies = $row->replies()->orderByDesc('id')->paginate(10);

?>
<div class="all-answers">
    <h3 class="title">All Replies</h3>
    <div class="list-group">
        <?php $__currentLoopData = $replies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reply): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="forum-comment list-group-item py-4">
                <div class="forum-post-top d-flex align-items-center">
                    <a class="author-avatar mr-3 flex-shrink-0" href="<?php echo e(route('user.profile',['id'=>$reply->user->id])); ?>">
                        <img style="width:50px;" class="rounded-lg" src="<?php echo e($reply->user->avatar_url ?? ''); ?>" alt="author avatar">
                    </a>
                    <div class="reply-post-author">
                        <a
                            class="author-name text-16 font-weight-medium" href="<?php echo e(route('user.profile',['id'=>$reply->user->id])); ?>"
                        ><?php echo e($reply->user->display_name); ?></a>
                        <div class="reply-author-meta d-flex">
                            <div class="author-badge mr-2">
                                <?php if($reply->user): ?>
                                    <div class="author-badge badge <?php if($reply->user_id === $row->customer_id): ?> badge-info <?php else: ?>  badge-warning <?php endif; ?>">
                                        <i class="fa fa-shield"></i>
                                        <span class=""><?php echo e(ucfirst($reply->user->role_name)); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="author-badge">
                                <i class="fa fa-clock-o"></i>
                                <span><?php echo e(human_time_diff(strtotime($reply->created_at))); ?> ago</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="comment-content pl-5 mt-2">
                    <div class="ml-3">
                        <?php echo clean($reply->content); ?>

                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="pagination-wrapper">
        <div class="view-post-of"></div>
        <div class="post-pagination">
            <?php echo e($replies->appends(request()->query())->links()); ?>

        </div>
    </div>
</div><?php echo $__env->make('Support::frontend.layouts.ticket.form-reply', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/ticket/replies.blade.php ENDPATH**/ ?>