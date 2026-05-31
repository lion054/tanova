<?php $lang_local = app()->getLocale() ?>
<?php
    $service_translation = $service->translate($lang_local);
    $reviewData = $service->getScoreReview(); $score_total = $reviewData['score_total'];
?>
<div class="booking-review">
    <h4 class="booking-review-title"><?php echo e(__("Your Booking")); ?></h4>
    <div class="booking-review-content">
        <div class="review-section">
            <div class="row x-gap-15 y-gap-20">
                <div class="col-auto">
                    <a href="<?php echo e($service->getDetailUrl()); ?>">
                    <?php if($image_url = $service->getImageUrl()): ?>
                        <img class="size-140 rounded-4 object-cover"  src="<?php echo e($image_url); ?>" alt="<?php echo e($service_translation->title); ?>">
                    <?php endif; ?>
                    </a>
                </div>

                <div class="col">
                    <div class="pb-10">
                        <?php echo $__env->make('Layout::common.rating',['score_total'=>$score_total], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>

                    <div class="lh-17 fw-500"><a href="<?php echo e($service->getDetailUrl()); ?>"><?php echo e($service_translation->title); ?></a></div>
                    <?php if($service_translation->address): ?>
                        <div class="text-14 lh-15 mt-5">
                            <?php echo e($service_translation->address); ?>

                        </div>
                    <?php endif; ?>

                    <div class="row x-gap-10 y-gap-10 items-center pt-10">
                        <div class="col-auto">
                            <?php $vendor = $service->author; ?>
                            <?php if($vendor->hasPermission('dashboard_vendor_access') and !$vendor->hasPermission('dashboard_access')): ?>
                                <div class="mt-1">
                                    <i class="icofont-info-circle"></i>
                                    <?php echo e(__("Vendor")); ?>: <a href="<?php echo e(route('user.profile',['id'=>$vendor->id])); ?>" target="_blank" ><?php echo e($vendor->getDisplayName()); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
                    <?php if($booking->getMeta("booking_type") == "ticket"): ?>
                        <li>
                            <div class="label"><?php echo e(__('Duration:')); ?></div>
                            <div class="val">
                                <?php $duration = $booking->getMeta("duration") ?>
                                <?php echo e(duration_format($duration)); ?>

                            </div>
                        </li>
                    <?php endif; ?>
                    <?php if($booking->getMeta("booking_type") == "time_slot"): ?>
                        <li>
                            <div class="label"><?php echo e(__('Duration:')); ?></div>
                            <div class="val">
                                <?php echo e($booking->getMeta("duration")); ?>

                                <?php echo e($booking->getMeta("duration_unit")); ?>

                            </div>
                        </li>
                        <li class="flex-wrap">
                            <div class="label w-100 mb-2"><?php echo e(__('Start Time:')); ?></div>
                            <div class="val w-100">
                                <div class="slots-wrapper d-flex justify-content-start flex-wrap">
                                    <?php if(!empty($timeSlots = $booking->time_slots)): ?>
                                        <?php $__currentLoopData = $timeSlots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="btn btn-sm mr-2 mb-2 btn-success">
                                                <?php echo e(date( "H:i",strtotime($item->start_time))); ?>

                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if($booking->getMeta("booking_type") == "ticket"): ?>
                    <?php $ticket_types = $booking->getJsonMeta('ticket_types')?>
                    <?php if(!empty($ticket_types)): ?>
                        <?php $__currentLoopData = $ticket_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <div class="label"><?php echo e($type['name_'.$lang_local] ?? $type['name']); ?>:</div>
                                <div class="val">
                                    <?php echo e($type['number']); ?>

                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
        <?php do_action('booking.checkout.before_total_review'); ?>
        <div class="review-section total-review">
            <ul class="review-list">
                <?php if($booking->getMeta("booking_type") == "time_slot"): ?>
                    <li>
                        <div class="label"><?php echo e($booking->total_guests); ?> x <?php echo e(format_money( $booking->getJsonMeta('base_price'))); ?></div>
                        <div class="val">
                            <?php echo e(format_money( $booking->getJsonMeta('base_price') * $booking->total_guests )); ?>

                        </div>
                    </li>
                <?php endif; ?>
                <?php if($booking->getMeta("booking_type") == "ticket"): ?>
                    <?php $ticket_types = $booking->getJsonMeta('ticket_types') ?>
                    <?php if(!empty($ticket_types)): ?>
                        <?php $__currentLoopData = $ticket_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <div class="label"><?php echo e($type['name_'.$lang_local] ?? $type['name']); ?>: <?php echo e($type['number']); ?> * <?php echo e(format_money($type['price'])); ?></div>
                                <div class="val">
                                    <?php echo e(format_money($type['price'] * $type['number'])); ?>

                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
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
                                    <div class="label"><?php echo e($type['name_'.$lang_local] ?? $type['name']); ?>:</div>
                                    <div class="val">
                                        <?php echo e(format_money($type['total'] ?? 0)); ?>

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
                                <?php if(!empty($item['per_ticket']) and $item['per_ticket'] == "on"): ?>
                                    : <?php echo e($booking->total_guests); ?> * <?php echo e(format_money( $fee_price )); ?>

                                <?php endif; ?>
                            </div>
                            <div class="val">
                                <?php if(!empty($item['per_ticket']) and $item['per_ticket'] == "on"): ?>
                                    <?php echo e(format_money( $fee_price * $booking->total_guests )); ?>

                                <?php else: ?>
                                    <?php echo e(format_money( $fee_price )); ?>

                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
                <li class="d-block px-20 py-20 bg-blue-2 rounded-4 mt-20">
                    <div class="row y-gap-5 justify-between">
                        <div class="col-auto">
                            <div class="text-18 lh-13 fw-500"><?php echo e(__("Total:")); ?></div>
                        </div>
                        <div class="col-auto">
                            <div class="text-18 lh-13 fw-500"><?php echo e(format_money($booking->total)); ?></div>
                        </div>
                    </div>
                    <?php if($booking->status !='draft'): ?>
                        <div class="row y-gap-5 justify-between">
                            <div class="col-auto">
                                <div class="text-18 lh-13 fw-500"><?php echo e(__("Paid:")); ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="text-18 lh-13 fw-500"><?php echo e(format_money($booking->paid)); ?></div>
                            </div>
                        </div>
                        <?php if($booking->paid < $booking->total ): ?>
                            <div class="row y-gap-5 justify-between">
                                <div class="col-auto">
                                    <div class="text-18 lh-13 fw-500"><?php echo e(__("Remain:")); ?></div>
                                </div>
                                <div class="col-auto">
                                    <div class="text-18 lh-13 fw-500"><?php echo e(format_money($booking->total - $booking->paid)); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
                <?php echo $__env->make('Booking::frontend/booking/checkout-deposit-amount', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </ul>
        </div>
    </div>
</div>
<div class="px-30 py-30 border-light rounded-4 mt-30">
    <?php if ($__env->exists('Coupon::frontend/booking/checkout-coupon')) echo $__env->make('Coupon::frontend/booking/checkout-coupon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Event/Views/frontend/booking/detail.blade.php ENDPATH**/ ?>