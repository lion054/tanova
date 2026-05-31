<ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#order-detail"><?php echo e(__("Order Detail")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#order-customer">
            <?php echo e(__("Customer Information")); ?>

        </a>
    </li>
</ul>
<div class="tab-content">
    <div id="order-detail" class="tab-pane active">
        <br>
        <div class="booking-review">
            <div class="booking-review-content">
                <div class="review-section">
                    <div class="info-form">
                        <ul>
                            <li>
                                <div class="label"><?php echo e(__('Order Status')); ?></div>
                                <div class="val"><?php echo e($order->status_text); ?></div>
                            </li>
                            <li>
                                <div class="label"><?php echo e(__('Order Date')); ?></div>
                                <div class="val"><?php echo e(display_date($order->created_at)); ?></div>
                            </li>
                            <?php if(!empty($order->gateway)): ?>
                                    <?php $gateway = get_payment_gateway_obj($order->gateway); ?>
                                <?php if($gateway): ?>
                                    <li>
                                        <div class="label"><?php echo e(__('Payment Method')); ?></div>
                                        <div class="val"><?php echo e($gateway->name); ?></div>
                                    </li>
                                <?php endif; ?>
                                <?php if($gateway and $note = $gateway->getOption('payment_note')): ?>
                                    <li>
                                        <div class="label"><?php echo e(__('Payment Note')); ?></div>
                                        <div class="val"><?php echo clean($note); ?></div>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="order-box border-top pt-3">
            <h4 class="fs-18"><?php echo e(__('Order details')); ?></h4>
            <table class="table">
                <thead>
                <tr>
                    <th><strong><?php echo e(__('Product')); ?></strong></th>
                    <th width="20%"><strong><?php echo e(__('Subtotal')); ?></strong></th>
                </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $orderItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $model = $orderItem->product; ?>
                    <tr class="cart-item">
                        <td class="product-name"><?php echo e($model->getBuyableName()); ?> x<?php echo e($orderItem->qty); ?>

                            <?php
                                // TODO: show variation
                            ?>
                            <?php if(!empty($orderItem->meta['extra_prices'])): ?>
                                <div class="mt-3"><strong><?php echo e(__("Extra Prices:")); ?></strong></div>
                                <ul class="list-unstyled mt-2">
                                    <?php $__currentLoopData = $orderItem->meta['extra_prices']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $extra_price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($extra_price['name'] ?? ''); ?> : <?php echo e(format_money($extra_price['price'] ?? 0)); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td class="product-total"><?php echo e(format_money($orderItem->subtotal)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot>
                <?php if(!empty($order->shipping_amount) and $order->shipping_amount > 0): ?>
                    <tr class="shipping-amount">
                        <td><?php echo e(__('Shipping Amount')); ?></td>
                        <td>
                            <span class="amount"><?php echo e(format_money($order->shipping_amount )); ?></span>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if(!empty($order->discount_amount) and $order->discount_amount > 0): ?>
                    <tr class="discount-amount">
                        <td><?php echo e(__('Discount Amount')); ?></td>
                        <td>
                            <span class="amount">-<?php echo e(format_money($order->discount_amount )); ?></span>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if(!empty($order->tax_amount) and $order->tax_amount > 0): ?>
                    <tr class="shipping-amount">
                        <td>
                            <?php echo e(__('Tax')); ?> <?php if($order->getMeta('prices_include_tax') == "yes"): ?>
                                <span>(<?php echo e(__("include")); ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="amount"><?php echo e(format_money($order->tax_amount )); ?></span>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr class="order-total">
                    <td><?php echo e(__('Total')); ?></td>
                    <td>
                        <span class="amount"><?php echo e(format_money($order->total)); ?></span>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div id="order-customer" class="tab-pane fade">
        <br>
        <?php echo $__env->make('Order::emails.parts.order-address',['order'=>$order], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/frontend/order/modal.blade.php ENDPATH**/ ?>