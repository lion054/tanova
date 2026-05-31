
<?php $__env->startSection('content'); ?>

    <div class="b-container">
        <div class="b-panel">
            <?php switch($to):
                case ('admin'): ?>
                    <h3 class="email-headline"><strong><?php echo e(__('Hello Administrator')); ?></strong></h3>
                    <p><?php echo e(__('New booking has been made')); ?></p>
                <?php break; ?>
                <?php case ('vendor'): ?>
                    <h3 class="email-headline"><strong><?php echo e(__('Hello :name',['name'=>$booking->vendor->nameOrEmail ?? ''])); ?></strong></h3>
                    <p><?php echo e(__('Your service has new booking')); ?></p>
                <?php break; ?>

                <?php case ('customer'): ?>
                    <h3 class="email-headline"><strong><?php echo e(__('Hello :name',['name'=>$booking->first_name ?? ''])); ?></strong></h3>
                    <p><?php echo e(__('Thank you for booking with us. Here are your booking information:')); ?></p>
                <?php break; ?>

            <?php endswitch; ?>

            <?php if(!empty($service->email_new_booking_file) && view()->exists($service->email_new_booking_file)): ?>
                <?php echo $__env->make($service->email_new_booking_file, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php else: ?>
                <table class="b-table" cellspacing="0" cellpadding="0">
                    <tr><td class="label"><?php echo e(__('Booking Number')); ?></td><td class="val">#<?php echo e($booking->id); ?></td></tr>
                    <tr><td class="label"><?php echo e(__('Service')); ?></td><td class="val"><?php echo e($service->title ?? $booking->object_model ?? '—'); ?></td></tr>
                    <tr><td class="label"><?php echo e(__('Start Date')); ?></td><td class="val"><?php echo e($booking->start_date ? display_date($booking->start_date) : '—'); ?></td></tr>
                    <tr><td class="label"><?php echo e(__('End Date')); ?></td><td class="val"><?php echo e($booking->end_date ? display_date($booking->end_date) : '—'); ?></td></tr>
                    <tr><td class="label"><?php echo e(__('Status')); ?></td><td class="val"><?php echo e($booking->statusName); ?></td></tr>
                    <tr><td class="label" style="font-size:18px"><?php echo e(__('Total')); ?></td><td class="val" style="font-size:18px"><strong style="color:#FA5636"><?php echo e(format_money($booking->total)); ?></strong></td></tr>
                    <tr><td class="label" style="font-size:18px"><?php echo e(__('Paid')); ?></td><td class="val" style="font-size:18px"><strong style="color:#FA5636"><?php echo e(format_money($booking->paid)); ?></strong></td></tr>
                </table>
                <div class="text-center mt20">
                    <a href="<?php echo e(route('user.booking_history')); ?>" target="_blank" class="btn btn-primary manage-booking-btn"><?php echo e(__('Manage Bookings')); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php echo $__env->make('Booking::emails.parts.panel-customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Booking::emails.parts.panel-passengers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Booking/Views/emails/new-booking.blade.php ENDPATH**/ ?>