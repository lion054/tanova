<?php $lang_local = app()->getLocale() ?>
<div class="booking-review">
    <h4 class="booking-review-title"><?php echo e(__("Your Booking")); ?></h4>
    <div class="booking-review-content">
        <div class="review-section">
            <div class="service-info">
                <div>
                    <?php
                        $service_translation = $service->translate($lang_local);
                    ?>
                    <h3 class="service-name"><a href="<?php echo e($service->getDetailUrl()); ?>"><?php echo clean($service_translation->title); ?></a></h3>
                    <?php if($service_translation->address): ?>
                        <p class="address"><i class="fa fa-map-marker"></i>
                            <?php echo e($service_translation->address); ?>

                        </p>
                    <?php endif; ?>
                </div>
                <?php $vendor = $service->author; ?>
                <?php if($vendor->hasPermission('dashboard_vendor_access') and !$vendor->hasPermission('dashboard_access')): ?>
                    <div class="mt-1">
                        <i class="icofont-info-circle"></i>
                        <?php echo e(__("Vendor")); ?>: <a href="<?php echo e(route('user.profile',['id'=>$vendor->id])); ?>" target="_blank" ><?php echo e($vendor->getDisplayName()); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="review-section">
            <ul class="review-list">
                <?php if($booking->start_date): ?>
                    <li>
                        <div class="label"><?php echo e(__('Start date:')); ?></div>
                        <div class="val">
                            <?php echo e(display_date($booking->start_date)); ?>

                        </div>
                    </li>
                    <li>
                        <div class="label"><?php echo e(__('Duration:')); ?></div>
                        <div class="val">
                            <?php echo e(human_time_diff($booking->end_date,$booking->start_date)); ?>

                        </div>
                    </li>
                <?php endif; ?>
                <?php $person_types = $booking->getJsonMeta('person_types')?>
                <?php if(!empty($person_types)): ?>
                    <?php $__currentLoopData = $person_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <div class="label"><?php echo e($type['name_'.$lang_local] ?? __($type['name'])); ?>:</div>
                            <div class="val">
                                <?php echo e($type['number']); ?>

                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <li>
                        <div class="label"><?php echo e(__("Guests")); ?>:</div>
                        <div class="val">
                            <?php echo e($booking->total_guests); ?>

                        </div>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
        <?php do_action('booking.checkout.before_total_review'); ?>
        <div class="review-section total-review">
            <ul class="review-list">
                <?php $person_types = $booking->getJsonMeta('person_types') ?>
                <?php if(!empty($person_types)): ?>
                    <?php $__currentLoopData = $person_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <div class="label"><?php echo e($type['name_'.$lang_local] ?? __($type['name'])); ?>: <?php echo e($type['number']); ?> * <?php echo e(format_money($type['price'])); ?></div>
                            <div class="val">
                                <?php echo e(format_money($type['price'] * $type['number'])); ?>

                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <li>
                        <div class="label"><?php echo e(__("Guests")); ?>: <?php echo e($booking->total_guests); ?> * <?php echo e(format_money($booking->getMeta('base_price'))); ?></div>
                        <div class="val">
                            <?php echo e(format_money($booking->getMeta('base_price') * $booking->total_guests)); ?>

                        </div>
                    </li>
                <?php endif; ?>
                <?php $extra_price = $booking->getJsonMeta('extra_price') ?>
                <?php if(!empty($extra_price)): ?>
                    <li>
                        <div class="label-title"><strong><?php echo e(__("Extra Prices:")); ?></strong></div>
                    </li>
                    <li class="no-flex">
                        <ul>
                            <?php $__currentLoopData = $extra_price; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <div class="label"><?php echo e($type['name_'.$lang_local] ?? __($type['name'])); ?>:</div>
                                    <div class="val">
                                        <?php echo e(format_money($type['total'] ?? 0)); ?>

                                    </div>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php $discount_by_people = $booking->getJsonMeta('discount_by_people')?>
                <?php if(!empty($discount_by_people)): ?>
                    <li>
                        <div class="label-title"><strong><?php echo e(__("Discounts:")); ?></strong></div>
                    </li>
                    <li class="no-flex">
                        <ul>
                            <?php $__currentLoopData = $discount_by_people; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <div class="label">
                                        <?php if(!$type['to']): ?>
                                            <?php echo e(__('from :from guests',['from'=>$type['from']])); ?>

                                        <?php else: ?>
                                            <?php echo e(__(':from - :to guests',['from'=>$type['from'],'to'=>$type['to']])); ?>

                                        <?php endif; ?>
                                        :
                                    </div>
                                    <div class="val">
                                        - <?php echo e(format_money($type['total'] ?? 0)); ?>

                                    </div>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php
                    $list_all_fee = [];
                    if(!empty($booking->buyer_fees)){
                        $buyer_fees = json_decode($booking->buyer_fees , true);
                        $list_all_fee = $buyer_fees;
                    }
                    if(!empty($vendor_service_fee = $booking->vendor_service_fee)){
                        $list_all_fee = array_merge($list_all_fee , $vendor_service_fee);
                    }
                ?>
                <?php if(!empty($list_all_fee)): ?>
                    <?php $__currentLoopData = $list_all_fee; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $fee_price = $item['price'];
                            if(!empty($item['unit']) and $item['unit'] == "percent"){
                                $fee_price = ( $booking->total_before_fees / 100 ) * $item['price'];
                            }
                        ?>
                        <li>
                            <div class="label">
                                <?php echo e($item['name_'.$lang_local] ?? $item['name'] ?? ''); ?>

                                <i
                                    class="icofont-info-circle" data-toggle="tooltip" data-placement="top"
                                    title="<?php echo e($item['desc_'.$lang_local] ?? $item['desc'] ?? ''); ?>"
                                ></i>
                                <?php if(!empty($item['per_person']) and $item['per_person'] == "on"): ?>
                                    : <?php echo e($booking->total_guests); ?> * <?php echo e(format_money( $fee_price )); ?>

                                <?php endif; ?>
                            </div>
                            <div class="val">
                                <?php if(!empty($item['per_person']) and $item['per_person'] == "on"): ?>
                                    <?php echo e(format_money( $fee_price * $booking->total_guests )); ?>

                                <?php else: ?>
                                    <?php echo e(format_money( $fee_price )); ?>

                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
                <?php if ($__env->exists('Coupon::frontend/booking/checkout-coupon')) echo $__env->make('Coupon::frontend/booking/checkout-coupon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <li class="final-total d-block">
                    <div class="d-flex justify-content-between">
                        <div class="label"><?php echo e(__("Total:")); ?></div>
                        <div class="val"><?php echo e(format_money($booking->total)); ?></div>
                    </div>
                    <?php if($booking->status !='draft'): ?>
                        <div class="d-flex justify-content-between">
                            <div class="label"><?php echo e(__("Paid:")); ?></div>
                            <div class="val"><?php echo e(format_money($booking->paid)); ?></div>
                        </div>
                        <?php if($booking->paid < $booking->total ): ?>
                            <div class="d-flex justify-content-between">
                                <div class="label"><?php echo e(__("Remain:")); ?></div>
                                <div class="val"><?php echo e(format_money($booking->total - $booking->paid)); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
                <?php echo $__env->make('Booking::frontend/booking/checkout-deposit-amount', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </ul>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Tour/Views/frontend/booking/detail.blade.php ENDPATH**/ ?>