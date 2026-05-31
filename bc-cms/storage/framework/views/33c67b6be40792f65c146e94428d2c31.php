
<?php $__env->startSection('content'); ?>

    <div class="b-container">
        <div class="b-panel">
            <?php switch($to):
                case ('admin'): ?>
                <h3 class="email-headline"><strong><?php echo e(__('Hello Administrator')); ?></strong></h3>
                <p><?php echo e(__('The booking status has been updated')); ?></p>
                <?php break; ?>

                <?php case ('vendor'): ?>
                <h3 class="email-headline"><strong><?php echo e(__('Hello :name',['name'=>$booking->vendor->nameOrEmail ?? ''])); ?></strong></h3>
                <p><?php echo e(__('The booking status has been updated')); ?></p>
                <?php break; ?>


                <?php case ('customer'): ?>
                <h3 class="email-headline"><strong><?php echo e(__('Hello :name',['name'=>$booking->first_name ?? ''])); ?></strong></h3>
                <p><?php echo e(__('Your booking status has been updated')); ?></p>
                <?php break; ?>

            <?php endswitch; ?>

            <?php echo $__env->make($service->email_new_booking_file ?? '', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Booking/Views/emails/status-updated-booking.blade.php ENDPATH**/ ?>