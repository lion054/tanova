<table class="b-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="label"><?php echo e(__('Booking Number')); ?></td>
        <td class="val">#<?php echo e($booking->id); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo e(__('Status')); ?></td>
        <td class="val"><?php echo e($booking->statusName); ?></td>
    </tr>
    <?php if($booking->gatewayObj): ?>
    <tr>
        <td class="label"><?php echo e(__('Payment Method')); ?></td>
        <td class="val"><?php echo e($booking->gatewayObj->getOption('name')); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="label"><?php echo e(__('Trip')); ?></td>
        <td class="val"><?php echo e($service->title ?? '—'); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo e(__('Destination')); ?></td>
        <td class="val"><?php echo e($service->destination ?? '—'); ?></td>
    </tr>
    <?php if($booking->start_date): ?>
    <tr>
        <td class="label"><?php echo e(__('Check-in')); ?></td>
        <td class="val"><?php echo e(display_date($booking->start_date)); ?></td>
    </tr>
    <?php endif; ?>
    <?php if($booking->end_date): ?>
    <tr>
        <td class="label"><?php echo e(__('Check-out')); ?></td>
        <td class="val"><?php echo e(display_date($booking->end_date)); ?></td>
    </tr>
    <?php endif; ?>
    <?php if($service && $service->guests): ?>
    <tr>
        <td class="label"><?php echo e(__('Guests')); ?></td>
        <td class="val"><?php echo e($service->guests); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="label" style="font-size:18px"><?php echo e(__('Total')); ?></td>
        <td class="val" style="font-size:18px"><strong style="color:#FA5636"><?php echo e(format_money($booking->total)); ?></strong></td>
    </tr>
    <tr>
        <td class="label" style="font-size:18px"><?php echo e(__('Paid')); ?></td>
        <td class="val" style="font-size:18px"><strong style="color:#FA5636"><?php echo e(format_money($booking->paid)); ?></strong></td>
    </tr>
    <?php if($booking->total > $booking->paid): ?>
    <tr>
        <td class="label" style="font-size:18px"><?php echo e(__('Remaining')); ?></td>
        <td class="val" style="font-size:18px"><strong style="color:#FA5636"><?php echo e(format_money($booking->total - $booking->paid)); ?></strong></td>
    </tr>
    <?php endif; ?>
</table>
<div class="text-center mt20">
    <a href="<?php echo e(route('admin.tanova.show', $service)); ?>" target="_blank" class="btn btn-primary manage-booking-btn">
        <?php echo e(__('View Itinerary')); ?>

    </a>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Tanova/Views/emails/booking-detail.blade.php ENDPATH**/ ?>