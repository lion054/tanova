<?php
$translation = $service->translate();
$lang_local = app()->getLocale();
?>
<div class="b-panel-title"><?php echo e(__('Property information')); ?></div>
<div class="b-table-wrap">
    <table class="b-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="label"><?php echo e(__('Booking Number')); ?></td>
            <td class="val">#<?php echo e($booking->id); ?></td>
        </tr>
        <tr>
            <td class="label"><?php echo e(__('Booking Status')); ?></td>
            <td class="val"><?php echo e($booking->statusName); ?></td>
        </tr>
        <?php if($booking->gatewayObj): ?>
            <tr>
                <td class="label"><?php echo e(__('Payment method')); ?></td>
                <td class="val"><?php echo e($booking->gatewayObj->getOption('name')); ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="label"><?php echo e(__('Property name')); ?></td>
            <td class="val">
                <a href="<?php echo e($service->getDetailUrl()); ?>"><?php echo e($translation->title); ?></a>
            </td>

        </tr>
        <tr>
            <?php if($translation->address): ?>
                <td class="label"><?php echo e(__('Address')); ?></td>
                <td class="val">
                    <?php echo e($translation->address); ?>

                </td>
            <?php endif; ?>
        </tr>
        <?php if($booking->start_date && $booking->end_date): ?>
            <tr>
                <td class="label"><?php echo e(__('Start date')); ?></td>
                <td class="val"><?php echo e(display_date($booking->start_date)); ?></td>
            </tr>
            <tr>
                <td class="label"><?php echo e(__('End date:')); ?></td>
                <td class="val">
                    <?php echo e(display_date($booking->end_date)); ?>

                </td>
            </tr>
            <tr>
                <td class="label"><?php echo e(__('Days:')); ?></td>
                <td class="val">
                    <?php echo e($booking->duration_days); ?>

                </td>
            </tr>
        <?php endif; ?>

        <?php if($meta = $booking->getMeta('adults')): ?>
            <tr>
                <td class="label"><?php echo e(__('Adults')); ?>:</td>
                <td class="val">
                    <strong><?php echo e($meta); ?></strong>
                </td>
            </tr>
        <?php endif; ?>
        <?php if($meta = $booking->getMeta('children')): ?>
            <tr>
                <td class="label"><?php echo e(__('Children')); ?>:</td>
                <td class="val">
                    <strong><?php echo e($meta); ?></strong>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="label"><?php echo e(__('Pricing')); ?></td>
            <td class="val">
                <table class="pricing-list" width="100%">
                    <tr>
                        <td class="label"><?php echo e(format_money($booking->total_before_fees/$booking->duration_days)); ?> *
                            <?php if($booking->duration_days <= 1): ?>
                                <?php echo e(__(':count day',['count'=>$booking->duration_days])); ?>

                            <?php else: ?>
                                <?php echo e(__(':count days',['count'=>$booking->duration_days])); ?>

                            <?php endif; ?>
                            :</td>
                        <td class="val no-r-padding">
                            <strong><?php echo e(format_money($booking->total_before_fees)); ?></strong>
                        </td>
                    </tr>
                    <?php $extra_price = $booking->getJsonMeta('extra_price')?>

                    <?php if(!empty($extra_price)): ?>
                        <tr>
                            <td colspan="2" class="label-title"><strong><?php echo e(__("Extra Prices:")); ?></strong></td>
                        </tr>
                        <tr class="">
                            <td colspan="2" class="no-r-padding no-b-border">
                                <table width="100%">
                                    <?php $__currentLoopData = $extra_price; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="label"><?php echo e($type['name']); ?>:</td>
                                            <td class="val no-r-padding">
                                                <strong><?php echo e(format_money($type['total'] ?? 0)); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </table>
                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php if(!empty($booking->buyer_fees)): ?>
                        <?php
                            $buyer_fees = json_decode($booking->buyer_fees , true);
                            foreach ($buyer_fees as $buyer_fee){
                                $fee_price = $buyer_fee['price'];
                                if(!empty($buyer_fee['type']) or $buyer_fee['type'] == "percent"){
                                    $fee_price = ( $booking->total_before_fees / 100 ) * $buyer_fee['price'];
                                }
                        ?>
                        <tr>
                            <td class="label">
                                <?php echo e($buyer_fee['name_'.$lang_local] ?? $buyer_fee['name']); ?>

                                <i class="icofont-info-circle" data-toggle="tooltip" data-placement="top" title="<?php echo e($buyer_fee['desc_'.$lang_local] ?? $buyer_fee['desc']); ?>"></i>
                                <?php if(!empty($buyer_fee['per_person']) and $buyer_fee['per_person'] == "on"): ?>
                                    : <?php echo e($booking->total_guests); ?> * <?php echo e(format_money( $fee_price )); ?>

                                <?php endif; ?>
                            </td>
                            <td class="val">
                                <?php if(!empty($buyer_fee['per_person']) and $buyer_fee['per_person'] == "on"): ?>
                                    <?php echo e(format_money( $fee_price * $booking->total_guests )); ?>

                                <?php else: ?>
                                    <?php echo e(format_money( $fee_price )); ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php endif; ?>

                </table>
            </td>
        </tr>
        <tr>
            <td class="label fsz21"><?php echo e(__('Total')); ?></td>
            <td class="val fsz21"><strong style="color: #FA5636"><?php echo e(format_money($booking->total)); ?></strong></td>
        </tr>
        <tr>
            <td class="label fsz21"><?php echo e(__('Paid')); ?></td>
            <td class="val fsz21"><strong style="color: #FA5636"><?php echo e(format_money($booking->paid)); ?></strong></td>
        </tr>
        <?php if($booking->total > $booking->paid): ?>
            <tr>
                <td class="label fsz21"><?php echo e(__('Remain')); ?></td>
                <td class="val fsz21"><strong style="color: #FA5636"><?php echo e(format_money($booking->total - $booking->paid)); ?></strong></td>
            </tr>
        <?php endif; ?>
    </table>
</div>
<div class="text-center mt20">
    <a href="<?php echo e(route("user.booking_history")); ?>" target="_blank" class="btn btn-primary manage-booking-btn"><?php echo e(__('Manage Bookings')); ?></a>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Property/Views/emails/new_booking_detail.blade.php ENDPATH**/ ?>