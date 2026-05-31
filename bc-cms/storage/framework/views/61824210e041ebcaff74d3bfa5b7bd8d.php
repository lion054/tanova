<?php $lang_local = app()->getLocale() ?>
<div class="booking-review">
    <h4 class="booking-review-title"><?php echo e(__("Your Booking")); ?></h4>
    <div class="booking-review-content">
        <div class="review-section">
            <div class="service-info">
                <div>
                    <?php if($image_url = $service->airline->image_url): ?>
                        <?php if(!empty($disable_lazyload)): ?>
                            <img src="<?php echo e($image_url); ?>" class="img-responsive" alt="<?php echo clean($service->airline->name); ?>">
                        <?php else: ?>
                            <?php echo get_image_tag($service->airline->image_id,'medium',['class'=>'img-responsive','alt'=>$service->airline->name]); ?>

                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="mt-2">
                    <h3 class="service-name"><?php echo clean($service->airline->name); ?></h3>
                </div>
                <div class="font-weight-medium  mb-3">
                    <p class="mb-1">
                        <?php echo e(__(':from to :to',['from'=>$service->airportFrom->name,'to'=>$service->airportTo->name])); ?>

                    </p>
                    <?php echo e(__(":duration hrs",['duration'=>$service->duration])); ?>

                </div>

                <div class="flex-self-start justify-content-between">
                    <div class="flex-self-start">
                        <div class="mr-2">
                            <i class="icofont-airplane font-size-30 text-primary"></i>
                        </div>
                        <div class="text-lh-sm ml-1">
                            <h6 class="font-weight-bold font-size-21 text-gray-5 mb-0"><?php echo e($service->departure_time->format('H:i')); ?></h6>
                            <span class="font-size-14 font-weight-normal text-gray-1"><?php echo e($service->airportFrom->name); ?></span>
                        </div>
                    </div>
                    <div class="text-center d-none d-md-block d-lg-none">
                        <div class="mb-1">
                            <h6 class="font-size-14 font-weight-bold text-gray-5 mb-0"><?php echo e(__(":duration hrs",['duration'=>$service->duration])); ?></h6>
                        </div>
                    </div>
                    <div class="flex-self-start">
                        <div class="mr-2">
                            <i class="d-block rotate-90 icofont-airplane-alt font-size-30 text-primary"></i>
                        </div>
                        <div class="text-lh-sm ml-1">
                            <h6 class="font-weight-bold font-size-21 text-gray-5 mb-0"><?php echo e($service->arrival_time->format("H:i")); ?></h6>
                            <span class="font-size-14 font-weight-normal text-gray-1"><?php echo e($service->airportTo->name); ?></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="review-section">
            <ul class="review-list">
                <?php if($booking->start_date): ?>
                    <li>
                        <div class="label"><?php echo e(__("Start Date")); ?></div>
                        <div class="val">
                            <?php echo e(display_date($booking->start_date)); ?>

                        </div>
                    </li>
                    <li>
                        <div class="label"><?php echo e(__("Duration")); ?></div>
                        <div class="val"><?php echo e(human_time_diff($booking->end_date,$booking->start_date)); ?></div>
                    </li>
                <?php endif; ?>
                <?php
                    $flight_seat = $booking->getJsonMeta('flight_seat')?>
                <?php if(!empty($flight_seat)): ?>
                    <?php $__currentLoopData = $flight_seat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(!empty($type['number'])): ?>
                            <li>
                                <div class="label"><?php echo e($type['seat_type']['name']); ?>:</div>
                                <div class="val">
                                    <?php echo e($type['number']); ?>

                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="review-section total-review">
            <ul class="review-list">
                <?php $flight_seat = $booking->getJsonMeta('flight_seat')?>
                <?php $person_types = $booking->getJsonMeta('person_types') ?>
                <?php if(!empty($flight_seat)): ?>
                    <?php $__currentLoopData = $flight_seat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(!empty($type['number'])): ?>
                            <li>
                                <div class="label"><?php echo e($type['seat_type']['name']); ?>: <?php echo e($type['number']); ?> * <?php echo e(format_money($type['price'])); ?></div>
                                <div class="val">
                                    <?php echo e(format_money($type['price'] * $type['number'])); ?>

                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
                <?php $extra_price = $booking->getJsonMeta('extra_price') ?>
                <?php if(!empty($extra_price)): ?>
                    <li>
                        <div>
                            <?php echo e(__("Extra Prices:")); ?>

                        </div>
                    </li>
                    <?php $__currentLoopData = $extra_price; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <div class="label"><?php echo e($type['name_'.$lang_local] ?? __($type['name'])); ?>:</div>
                            <div class="val">
                                <?php echo e(format_money($type['total'] ?? 0)); ?>

                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Flight/Views/frontend/booking/detail.blade.php ENDPATH**/ ?>