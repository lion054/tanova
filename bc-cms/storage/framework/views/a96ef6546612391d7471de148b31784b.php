
<?php $__env->startSection('content'); ?>
    <div class="b-container">
        <div class="b-panel">
            <?php switch($email_to):
                case ("customer"): ?>
                <h1><?php echo e(__("Hello ")); ?> <?php echo e($order->customer->display_name ?? ''); ?></h1>
                <?php break; ?>
                <?php case ("admin"): ?>
                <h1><?php echo e(__("Hello administrator")); ?></h1>
                <?php break; ?>
                <?php case ("vendor"): ?>
                <h1><?php echo e(__("Hello")); ?> <?php echo e($vendor->display_name ?? ''); ?></h1>
                <?php break; ?>
            <?php endswitch; ?>
            <p><?php echo e(__("Your order item has been updated:")); ?></p>

            <?php echo $__env->make('order.emails.order-item.order-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.email', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/emails/order-item/updated_order.blade.php ENDPATH**/ ?>