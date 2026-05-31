<p><strong><?php echo e(__("Order ID:")); ?></strong> #<?php echo e($order->id); ?></p>
<p><strong><?php echo e(__("Order Item ID:")); ?></strong> #<?php echo e($orderItem->id); ?></p>
<p><strong><?php echo e(__("Order Date:")); ?></strong> <?php echo e(display_datetime($order->created_at)); ?></p>
<p><strong><?php echo e(__("Gateway:")); ?></strong> <?php echo e($order->gateway_name); ?></p>
<p><strong><?php echo e(__("Status:")); ?></strong> <?php echo e($orderItem->status_text); ?></p>
<br>
<table class="b-table" border="1px" cellpadding="0" cellspacing="0">
    <thead>
    <tr style="border-bottom: 1px solid #EAEEF3" class="carttable_row">
        <th style="padding: 10px" class="cartm_title"><?php echo e(__('Product')); ?></th>
        <th style="padding: 10px" class="cartm_title"><?php echo e(__('Quantity')); ?></th>
        <th style="padding: 10px" class="cartm_title"><?php echo e(__('Price')); ?></th>
    </tr>
    </thead>
    <tbody class="table_body">

        <?php $model = $orderItem->model; ?>
        <tr style="border-bottom: 1px solid #EAEEF3">
            <td style="border-bottom: 1px solid #EAEEF3;padding: 10px" scope="row">
                <?php if($model): ?>
                    <?php echo e($model->title); ?>

                <?php else: ?>
                    <?php echo e($orderItem->name); ?>

                <?php endif; ?>

                <?php if(!empty($orderItem->meta['package'])): ?>
                    <div class="mt-3"><?php echo e(__('Package: ')); ?> <?php echo e(package_key_to_name($orderItem->meta['package'])); ?> (<?php echo e(format_money($orderItem->price)); ?>)</div>
                <?php endif; ?>
                <?php if(!empty($orderItem->meta['extra_prices'])): ?>
                    <div><strong><?php echo e(__("Extra Prices:")); ?></strong></div>
                    <ul class="list-unstyled">
                        <?php $__currentLoopData = $orderItem->meta['extra_prices']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $extra_price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($extra_price['name'] ?? ''); ?> : <?php echo e(format_money($extra_price['price'] ?? 0)); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </td>
            <td style="border-bottom: 1px solid #EAEEF3;padding: 10px"><?php echo e($orderItem->qty); ?></td>
            <td style="border-bottom: 1px solid #EAEEF3;padding: 10px"><?php echo e(format_money($orderItem->subtotal)); ?></td>
        </tr>

    </tbody>
</table>
<br>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/emails/order-item/order-detail.blade.php ENDPATH**/ ?>