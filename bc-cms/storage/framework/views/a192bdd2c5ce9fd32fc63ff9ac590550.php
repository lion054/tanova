
<?php $__env->startSection('content'); ?>
    <div class="b-container">
        <div class="b-panel">
            <?php switch($email_to):
                case ("customer"): ?>
                    <h1><?php echo e(__("Hello ")); ?> <?php echo e($order->customer->display_name ?? ''); ?></h1>
                    <p><?php echo e(__("Thank you for your order. Here is the order information:")); ?></p>
                <?php break; ?>
                <?php case ("admin"): ?>
                    <h1><?php echo e(__("Hello administrator")); ?></h1>
                    <p><?php echo e(__("You have new order. Here is the order information:")); ?></p>
                <?php break; ?>
                <?php case ("vendor"): ?>
                    <h1><?php echo e(__("Hello")); ?> <?php echo e($vendor->display_name ?? ''); ?></h1>
                    <p><?php echo e(__("You have new order. Here is the order information:")); ?></p>
                <?php break; ?>
            <?php endswitch; ?>
            <?php echo $__env->make('Order::emails.parts.order-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('Order::emails.parts.customer-detail', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('Order::emails.parts.order-address', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/emails/new_order.blade.php ENDPATH**/ ?>