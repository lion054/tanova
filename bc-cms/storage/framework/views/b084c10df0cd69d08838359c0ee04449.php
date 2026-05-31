<tr>
    <td>
        <?php if($service = $booking->service): ?>
            <i class="<?php echo e($service->getServiceIconFeatured()); ?>"></i>
        <?php endif; ?>
        <?php echo e($booking->object_model); ?>

    </td>
    <td>
        <?php if($service = $booking->service): ?>
            <?php echo clean($service->title); ?>

            (<?php echo clean($service->code); ?>)
        <?php else: ?>
            <?php echo e(__("[Deleted]")); ?>

        <?php endif; ?>
    </td>
    <td><?php echo e(display_date($booking->created_at)); ?></td>
    <td class="lh-16">
        <?php echo e(__("Departure time")); ?> : <?php echo e(display_datetime($booking->start_date)); ?> <br>
        <?php echo e(__("Arrival Time ")); ?> : <?php echo e(display_datetime($booking->end_date)); ?> <br>
        <?php echo e(__("Duration")); ?> : <?php echo e(__(':duration hrs',['duration'=>@$booking->service->duration])); ?>


    </td>
    <td class="fw-500"><?php echo e(format_money($booking->total)); ?></td>
    <td><?php echo e(format_money($booking->paid)); ?></td>
    <td><?php echo e(format_money($booking->total - $booking->paid)); ?></td>
    <td class="<?php echo e($booking->status); ?>">
        <span class="rounded-100 py-4 px-10 text-center text-14 fw-500"><?php echo e($booking->statusName); ?></span>
    </td>
    <td>
        <div class="dropdown js-dropdown js-actions-1-active">
            <div class="dropdown__button d-flex items-center rounded-4 text-blue-1 bg-blue-1-05 text-14 px-15 py-5" data-el-toggle=".js-actions-<?php echo e($key + 1); ?>-toggle" data-el-toggle-active=".js-actions-<?php echo e($key + 1); ?>-active">
                <span class="js-dropdown-title"><?php echo e(__("Actions")); ?></span>
                <i class="icon icon-chevron-sm-down text-7 ml-10"></i>
            </div>

            <div class="toggle-element -dropdown-2 js-click-dropdown js-actions-<?php echo e($key + 1); ?>-toggle">
                <div class="text-14 fw-500 js-dropdown-list">
                    <?php if($service = $booking->service): ?>
                        <div><a href="#" class="d-block js-dropdown-link btn-info-booking" data-ajax="<?php echo e(route('booking.modal',['booking'=>$booking])); ?>" data-toggle="modal" data-id="<?php echo e($booking->id); ?>" data-target="#modal_booking_detail"><?php echo e(__("Details")); ?></a></div>
                    <?php endif; ?>

                    <div><a href="<?php echo e(route('user.booking.invoice',['code'=>$booking->code])); ?>" class="d-block js-dropdown-link btn-info-booking open-new-window" onclick="window.open(this.href); return false;"><?php echo e(__("Invoice")); ?></a></div>

                    <?php if($booking->status == 'unpaid'): ?>
                        <a href="<?php echo e(route('booking.checkout',['code'=>$booking->code])); ?>" class="d-block js-dropdown-link btn-info-booking" onclick="window.location.href = this.getAttribute('href')">
                            <?php echo e(__("Pay now")); ?>

                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </td>
</tr>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/bookingHistory/loop.blade.php ENDPATH**/ ?>