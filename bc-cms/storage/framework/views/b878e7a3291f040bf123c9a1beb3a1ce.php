<?php
$billing_address = $order->getJsonMeta('billing');
$shipping_address = $order->getJsonMeta('shipping');
$fields = ['country', 'state_code', 'city', 'zip', 'address', 'address_2'];
?>
<div class="flex flex-col md:flex-row gap-5 relative items-start justify-between">
    <div class="">
        <h3 class="address-title font-medium"> <?php echo e(__('Customer Information')); ?></h3>
        <address class="address">
            <?php echo e($billing_address['first_name'] ?? ''); ?> <?php echo e($billing_address['last_name'] ?? ''); ?>

            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(!empty($billing_address[$field])): ?>
                    <?php switch($field):
                        case ('country'): ?>
                            <br><?php echo e(get_country_name($billing_address[$field] ?? '')); ?>

                        <?php break; ?>
                        <?php case ('state_code'): ?>
                            <?php if(!empty($billing_address['country'])): ?>
                                <br><?php echo e(\Modules\Location\Helpers\AddressHelper::getStateName($billing_address['country'], $billing_address['state_code'])); ?>

                            <?php endif; ?>
                        <?php break; ?>

                        <?php default: ?>
                            <br><?php echo e($billing_address[$field] ?? ''); ?>

                        <?php break; ?>
                    <?php endswitch; ?>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </address>
    </div>
</div>
<br>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/emails/parts/order-address.blade.php ENDPATH**/ ?>