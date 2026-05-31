<div class="modal fade" id="modal-order-<?php echo e($row->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title"><?php echo e(__('Order ID: ')); ?> #<?php echo e($row->id); ?></h4>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#order-detail-<?php echo e($row->id); ?>"><?php echo e(__('Order Detail')); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#order-customer-<?php echo e($row->id); ?>">
                            <?php echo e(__('Customer Information')); ?>

                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="order-detail-<?php echo e($row->id); ?>" class="tab-pane active">
                        <br>
                        <div class="booking-review">
                            <div class="booking-review-content">
                                <div class="review-section">
                                    <div class="info-form">
                                        <ul>
                                            <li class="text-uppercase font-weight-bold info-header">
                                                <div class="label"><?php echo e(__('Items')); ?></div>
                                                <div class="val"><?php echo e(__('Total')); ?></div>
                                            </li>
                                            <?php if(!empty($items = $row->items)): ?>
                                                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li class="info-content">
                                                        <div class="label">
                                                            <div class="name"><?php echo e($item->product->getBuyableName() ?? ''); ?> x <?php echo e($item->qty); ?></div>
                                                            <div class="sold-by"><span style="font-weight: 600"><?php echo e(__('Sold by:')); ?></span> <?php echo e($model->author->display_name); ?></div>
                                                        </div>
                                                        <div class="val" style="color: red"><?php echo e(format_money($item->qty * $item->price)); ?></div>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                            <?php if($row->coupons): ?>
                                                <?php $__currentLoopData = $row->coupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li class="info-content info-coupon">
                                                        <div class="label text-uppercase"><?php echo e(__('Coupon: :coupon',['coupon'=>$coupon->code])); ?></div>
                                                        <div class="val" style="color: red">-<?php echo e(format_money($row->items->sum('discount_amount'))); ?></div>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                            <li class="info-total">
                                                <div class="label font-weight-bold text-uppercase"><?php echo e(__('Total')); ?></div>
                                                <div class="val"><?php echo e(format_money($row->total)); ?></div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="order-customer-<?php echo e($row->id); ?>" class="tab-pane fade">
                        <br>
                        <div class="booking-review">
                            <h4 class="booking-review-title"><?php echo e(__('Your Information')); ?></h4>
                            <div class="booking-review-content">
                                <div class="review-section">
                                    <div class="info-form">
                                        <?php if(!empty($billing)): ?>
                                            <ul>
                                                <li class="info-first-name">
                                                    <div class="label"><?php echo e(__('First name')); ?></div>
                                                    <div class="val"><?php echo e($billing['first_name'] ?? ''); ?></div>
                                                </li>
                                                <li class="info-last-name">
                                                    <div class="label"><?php echo e(__('Last name')); ?></div>
                                                    <div class="val"><?php echo e($billing['last_name'] ?? ''); ?></div>
                                                </li>
                                                <li class="info-email">
                                                    <div class="label"><?php echo e(__('Email')); ?></div>
                                                    <div class="val"><?php echo e($billing['email'] ?? ''); ?></div>
                                                </li>
                                                <li class="info-phone">
                                                    <div class="label"><?php echo e(__('Phone')); ?></div>
                                                    <div class="val"><?php echo e($billing['phone']  ?? ''); ?></div>
                                                </li>
                                                <li class="info-company">
                                                    <div class="label"><?php echo e(__('Company name')); ?></div>
                                                    <div class="val"><?php echo e($billing['company'] ?? ''); ?></div>
                                                </li>
                                                <li class="info-address">
                                                    <div class="label"><?php echo e(__('Address line 1')); ?></div>
                                                    <div class="val"><?php echo e($billing['address']  ?? ''); ?></div>
                                                </li>
                                                <li class="info-address2">
                                                    <div class="label"><?php echo e(__('Address line 2')); ?></div>
                                                    <div class="val"><?php echo e($billing['address2']  ?? ''); ?></div>
                                                </li>
                                                <li class="info-city">
                                                    <div class="label"><?php echo e(__('City')); ?></div>
                                                    <div class="val"><?php echo e($billing['city']  ?? ''); ?></div>
                                                </li>
                                                <li class="info-state">
                                                    <div class="label"><?php echo e(__('State/Province/Region')); ?></div>
                                                    <div class="val"><?php echo e($billing['state'] ?? ''); ?></div>
                                                </li>
                                                <li class="info-zip-code">
                                                    <div class="label"><?php echo e(__('ZIP code/Postal code')); ?></div>
                                                    <div class="val"><?php echo e($billing['postcode']  ?? ''); ?></div>
                                                </li>
                                                <li class="info-country">
                                                    <div class="label"><?php echo e(__('Country')); ?></div>
                                                    <div class="val"><?php echo e(get_country_name($billing['country']) ?? ''); ?></div>
                                                </li>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <span class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('Close')); ?></span>
            </div>
        </div>
    </div>
</div>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/admin/order/detail-modal.blade.php ENDPATH**/ ?>