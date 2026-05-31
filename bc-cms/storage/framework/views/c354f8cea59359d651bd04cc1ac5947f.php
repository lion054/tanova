<?php

use Illuminate\Support\Facades\Hash;

?>
<div class="ticket-wrap">
    <div class="ticket-header d-flex justify-content-between">
        <div class="service-info">
            <div class="servive-name"><?php echo e($booking->service->title ?? ''); ?></div>
            <div class="service-location">
                <i class="fa fa-map-marker"></i> <?php echo e($booking->service->address ??''); ?></div>
            <div><strong>
                    <i class="fa fa-ticket"></i> <?php echo e($ticket->meta['name'] ?? ''); ?></strong></div>
        </div>
        <div class="print">
            <button onclick="window.print()" class="btn btn-warning btn-sm">
                <i class="fa fa-print"></i>
            </button>
        </div>
    </div>
    <div class="ticket-body">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="50%">
                    <div class="label">
                        <i class="fa fa-calendar"></i> <?php echo e(__("Date")); ?></div>
                    <div class="val"><?php echo e(display_date($booking->start_date)); ?></div>
                </td>
                <td>
                    <div class="label">
                        <i class="fa fa-money"></i> <?php echo e(__("Price")); ?></div>
                    <div class="val"><?php echo e(format_money($ticket->price)); ?></div>
                </td>
            </tr>
            <tr>
                <td>
                    <?php if($booking->getMeta("booking_type") == "ticket"): ?>
                        <div class="label">
                            <i class="fa fa-ticket"></i> <?php echo e(__("Ticket ID")); ?>: #<?php echo e($ticket->id); ?></div>
                        <div class="val">
                            <?php echo e($ticket->meta['name'] ?? ''); ?>

                        </div>
                    <?php endif; ?>
                    <?php if($booking->getMeta("booking_type") == "time_slot"): ?>
                        <div class="label">
                            <i class="fa fa-ticket"></i> <?php echo e(__("Start Time")); ?></div>
                        <div class="val">
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
                    <?php endif; ?>
                </td>
                <td>
                    <div class="label">
                        <i class="fa fa-user"></i> <?php echo e(__("Customer")); ?></div>
                    <div class="val"><?php echo e($ticket->first_name ?: $booking->first_name); ?> <?php echo e($ticket->last_name ?: $booking->last_name); ?>

                        <br>
                        <?php echo e($ticket->email ?: $booking->email); ?>

                        <br>
                        <?php echo e($ticket->phone ?: $booking->phone); ?>

                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="ticket-footer">
        <?php if(!$ticket->is_scanned): ?>
            <div class="text-center"><?php echo e(__("Show QR Code at the counter")); ?></div>
            <div class="qr-content text-center">
                    <?php
                    $dataForHash = ['b' => $booking->id, 't' => $ticket->id];
                    $code = Hash::make($booking->id . '.' . $ticket->id);
                    $url = route('user.booking.ticket.scan', array_merge($dataForHash, ['code' => $code]));
                    ?>
                <?php echo QrCode::size(200)->generate($url); ?>

            </div>
        <?php else: ?>
            <div class="text-center">
                <span class="badge badge-warning"><?php echo e(__("QR Code scanned at: :time",['time'=>display_datetime($ticket->scanned_at)])); ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Booking/Views/frontend/user/ticket/loop.blade.php ENDPATH**/ ?>