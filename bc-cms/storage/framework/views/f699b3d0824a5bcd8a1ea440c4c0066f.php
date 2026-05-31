<h3><strong><?php echo e(__("Customer Details:")); ?></h3>
<p><strong><?php echo e(__("Display Name:")); ?></strong> <?php echo e($order->first_name.' '.$order->last_name); ?></p>
<p><strong><?php echo e(__("Email:")); ?></strong> <?php echo e($order->email ?? ''); ?></p>
<p><strong><?php echo e(__("Phone:")); ?></strong> <?php echo e($order->phone ?? ''); ?></p>
<br>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/emails/parts/customer-detail.blade.php ENDPATH**/ ?>