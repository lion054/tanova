
<?php $__env->startSection('content'); ?>
    <div class="b-container">
        <div class="b-panel">
            <?php if($reply->user_id == $ticket->customer_id): ?>
                <h1><?php echo e(__("Hello")); ?> <?php echo e($ticket->customer->display_name ?? ''); ?></h1>
            <?php else: ?>
                <h1><?php echo e(__("Hello")); ?> <?php echo e($ticket->agent->display_name ?? ''); ?></h1>
            <?php endif; ?>
            <p><?php echo e(__('You got new reply for ticket: #')); ?><?php echo e($ticket->id); ?> - <?php echo e($ticket->title); ?></p>
            <p><?php echo e(__("Reply content:")); ?></p>
            <p><?php echo nl2br(clean($reply->content)); ?></p>
            <p><?php echo e(__('You can check the ticket here:')); ?>

                <a href="<?php echo e(route('support.ticket.detail',['id'=>$ticket->id])); ?>"><?php echo e(__('View ticket')); ?></a>
            </p>
            <br>
            <p><?php echo e(__('Regards')); ?>,
                <br><?php echo e(setting_item('site_title')); ?></p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/email/new_reply.blade.php ENDPATH**/ ?>