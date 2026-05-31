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
                <div>
                    <?php if($image_url = $service->image_url): ?>
                        <?php if(!empty($disable_lazyload)): ?>
                            <img src="<?php echo e($service->image_url); ?>" class="img-responsive" alt="<?php echo clean($service_translation->title); ?>">
                        <?php else: ?>
                            <?php echo get_image_tag($service->image_id,'medium',['class'=>'img-responsive','alt'=>$service_translation->title]); ?>

                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php $vendor = $service->author; ?>
                <?php if($vendor->hasPermission('dashboard_vendor_access') and !$vendor->hasPermission('dashboard_access')): ?>
                    <div class="mt-2">
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
                            <?php echo e(display_datetime($booking->start_date)); ?>

                        </div>
                    </li>
                    <li>
                        <div class="label"><?php echo e(__('End date:')); ?></div>
                        <div class="val">
                            <?php echo e(display_datetime($booking->end_date)); ?>

                        </div>
                    </li>
                    <li>
                        <div class="label"><?php echo e(__('Durations:')); ?></div>
                        <div class="val">
                            <?php if($booking->getMeta('type_date') == 'per_hour'): ?>
                                <?php echo e($booking->duration_hours); ?> <?php echo e(Str::plural(__('hour'),$booking->duration_hours)); ?>

                            <?php else: ?>
                                <?php echo e($booking->duration_nights); ?> <?php echo e(Str::plural(__('day'),$booking->duration_nights)); ?>

                            <?php endif; ?>
                        </div>
                    </li>
                <?php endif; ?>
                <li class="flex-wrap">
                    <div class="flex-grow-0 flex-shrink-0 w-100">
                        <p class="text-center">
                            <a data-toggle="modal" data-target="#detailBookingDate<?php echo e($booking->code); ?>" aria-expanded="false"
                               aria-controls="detailBookingDate<?php echo e($booking->code); ?>">
                                <?php echo e(__('Detail')); ?> <i class="icofont-list"></i>
                            </a>
                        </p>
                    </div>
                </li>
            </ul>
        </div>
        <div class="review-section total-review">
            <ul class="review-list">
                <?php
                    $price_item = $booking->total_before_extra_price;
                ?>
                <?php if(!empty($price_item)): ?>
                    <li>
                        <div class="label"><?php echo e(__('Rental price')); ?>

                        </div>
                        <div class="val">
                            <?php echo e(format_money( $price_item)); ?>

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

<?php
$dateDetail = $service->detailBookingEachDate($booking);
;?>
<div class="modal fade" id="detailBookingDate<?php echo e($booking->code); ?>" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center"><?php echo e(__('Detail')); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="review-list list-unstyled">
                    <li class="mb-3 pb-1 border-bottom">
                        <h6 class="label text-center font-weight-bold mb-1"></h6>
                        <div>
                            <?php if ($__env->exists("Boat::frontend.booking.detail-date",['rows'=>$dateDetail,'number'=>$booking->number])) echo $__env->make("Boat::frontend.booking.detail-date",['rows'=>$dateDetail,'number'=>$booking->number], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="d-flex justify-content-between font-weight-bold px-2">
                            <span><?php echo e(__("Total:")); ?></span>
                            <span><?php echo e(format_money(array_sum(\Illuminate\Support\Arr::pluck($dateDetail,['price']))*$booking->number)); ?></span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Boat/Views/frontend/booking/detail.blade.php ENDPATH**/ ?>