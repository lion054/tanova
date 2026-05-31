<tr>
    <td class="booking-history-type">
        <?php if($service = $booking->service): ?>
            <i class="<?php echo e($service->getServiceIconFeatured()); ?>"></i>
        <?php endif; ?>
        <small><?php echo e($booking->object_model); ?></small>
    </td>
    <td>
        <?php if($service = $booking->service): ?>
            <a target="_blank" href="<?php echo e($service->getDetailUrl()); ?>">
                <?php echo e($service->title); ?>

            </a>
            <small>
                <div><?php echo e(__("Customer Info")); ?></div>
                <div>
                    <?php echo e(__("First Name")); ?>: <?php echo e($booking->first_name); ?>

                </div>
                <div>
                    <?php echo e(__("Last Name")); ?>: <?php echo e($booking->last_name); ?>

                </div>
            </small>
        <?php else: ?>
            <?php echo e(__("[Deleted]")); ?>

        <?php endif; ?>
    </td>
    <td class="a-hidden"><?php echo e(display_date($booking->created_at)); ?></td>
    <td class="a-hidden">
        <?php echo e(__("Start date")); ?> : <?php echo e(display_datetime($booking->start_date)); ?> <br>
        <?php echo e(__("End date")); ?> : <?php echo e(display_datetime($booking->end_date)); ?> <br>
        <?php if($booking->getMeta('type_date') == 'per_day'): ?>
            <?php echo e(__("Durations")); ?>: <?php echo e($booking->duration_nights); ?> <?php echo e(Str::plural(__('day'),$booking->duration_nights)); ?>

        <?php else: ?>
            <?php echo e(__("Durations")); ?>: <?php echo e($booking->duration_hours); ?> <?php echo e(Str::plural(__('hour'),$booking->duration_hours)); ?>

        <?php endif; ?>
    </td>
    <td>
        <div><?php echo e(__("Total")); ?>: <?php echo e(format_money_main($booking->total)); ?></div>
        <div><?php echo e(__("Paid")); ?>: <?php echo e(format_money_main($booking->paid)); ?></div>
        <div><?php echo e(__("Remain")); ?>: <?php echo e(format_money($booking->total - $booking->paid)); ?></div>
    </td>
    <td>
        <?php echo e(format_money($booking->commission)); ?>

    </td>
    <td class="<?php echo e($booking->status); ?> a-hidden"><?php echo e($booking->statusName); ?></td>
    <td width="2%">
        <?php if($service = $booking->service): ?>
            <a class="btn btn-xs btn-primary btn-info-booking" data-ajax="<?php echo e(route('booking.modal',['booking'=>$booking])); ?>" data-toggle="modal" data-id="<?php echo e($booking->id); ?>" data-target="#modal_booking_detail">
                <i class="fa fa-info-circle"></i><?php echo e(__("Details")); ?>

            </a>
        <?php endif; ?>
        <a href="<?php echo e(route('user.booking.invoice',['code'=>$booking->code])); ?>" class="btn btn-xs btn-primary btn-info-booking open-new-window mt-1" onclick="window.open(this.href); return false;">
            <i class="fa fa-print"></i><?php echo e(__("Invoice")); ?>

        </a>
        <?php if(!empty(setting_item("boat_allow_vendor_can_change_their_booking_status"))): ?>
            <a class="btn btn-xs btn-info btn-make-as w-100" data-bs-toggle="dropdown" data-toggle="dropdown">
                <i class="icofont-ui-settings"></i>
                <?php echo e(__("Action")); ?>

            </a>
            <div class="dropdown-menu">
                <?php if(!empty($statues)): ?>
                    <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route("boat.vendor.booking_report.bulk_edit" , ['id'=>$booking->id , 'status'=>$status])); ?>">
                            <i class="icofont-long-arrow-right"></i> <?php echo e(__('Mark as: :name',['name'=>booking_status_to_text($status)])); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
            <?php if(!empty(setting_item("boat_allow_vendor_can_change_paid_amount"))): ?>
                <a class="btn btn-xs btn-info btn-make-as w-100" data-toggle="modal" data-target="#modal-paid-<?php echo e($booking->id); ?>">
                    <i class="fa fa-dollar"></i><?php echo e(__("Set Paid")); ?>

                </a>
                <?php echo $__env->make($service->set_paid_modal_file ?? '', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
    </td>
</tr>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Boat/Views/frontend/bookingReport/loop.blade.php ENDPATH**/ ?>