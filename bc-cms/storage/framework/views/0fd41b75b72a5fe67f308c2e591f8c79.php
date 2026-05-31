<tr>
    <td class="booking-history-type">
        <?php if($service = $booking->service): ?>
            <i class="<?php echo e($service->getServiceIconFeatured()); ?>"></i>
        <?php endif; ?>
        <small><?php echo e($booking->object_model); ?></small>
    </td>
    <td>
        <?php if($service = $booking->service): ?>
            <?php
                $translation = $service->translate();
            ?>
            <a target="_blank" href="<?php echo e($service->getDetailUrl()); ?>">
                <?php echo e($translation->title); ?>

            </a>
        <?php else: ?>
            <?php echo e(__("[Deleted]")); ?>

        <?php endif; ?>
    </td>
    <td class="a-hidden"><?php echo e(display_date($booking->created_at)); ?></td>
    <td class="a-hidden">
        <?php echo e(__("Start date")); ?> : <?php echo e(display_date($booking->start_date)); ?> <br>
        <?php echo e(__("Duration")); ?> :
        <?php $duration = $booking->getMeta("duration") ?>
        <?php echo e(duration_format($duration)); ?>

    </td>
    <td><?php echo e(format_money($booking->total)); ?></td>
    <td><?php echo e(format_money($booking->paid)); ?></td>
    <td><?php echo e(format_money($booking->total - $booking->paid)); ?></td>
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
            <?php if(in_array($booking->status,[\Modules\Booking\Models\Booking::COMPLETED,\Modules\Booking\Models\Booking::PAID]) and isPro()): ?>
                <a
                    href="<?php echo e(route('user.booking.ticket',['code'=>$booking->code])); ?>"
                    class="btn btn-xs btn-primary btn-info-booking open-new-window mt-1"
                    onclick="window.open(this.href); return false;"
                >
                    <i class="fa fa-print"></i><?php echo e(__("Print Ticket")); ?>

                </a>
            <?php endif; ?>
        <?php if($booking->status == 'unpaid'): ?>
            <a href="<?php echo e(route('booking.checkout',['code'=>$booking->code])); ?>" class="btn btn-xs btn-primary btn-info-booking open-new-window mt-1">
                <?php echo e(__("Pay now")); ?>

            </a>
        <?php endif; ?>
    </td>
</tr>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Event/Views/frontend/bookingHistory/loop.blade.php ENDPATH**/ ?>