<?php

use Modules\Booking\Models\Booking;

?>
<div class="row booking-success-notice">
    <div class="col-lg-8 col-md-8">
        <div class="d-flex align-items-center">
            <?php if(in_array($booking->status , ['cancelled','unpaid',Booking::DRAFT])): ?>
                <img src="<?php echo e(url('images/ico_warning.svg')); ?>" alt="Payment Success">
                <div class="notice-success">
                    <p class="line1"><span><?php echo e($booking->first_name); ?>,</span>
                        <?php echo e(__('Your Booking Order is :name yet!',['name'=>$booking->status_name])); ?>

                    </p>
                    <p class="line2"><?php echo e(__('The order detail is saved in the Booking history.')); ?></p>
                    <?php if($note = $gateway->getOption("payment_note")): ?>
                        <div class="line2"><?php echo clean($note); ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <img src="<?php echo e(url('images/ico_success.svg')); ?>" alt="Payment Success">
                <div class="notice-success">
                    <p class="line1"><span><?php echo e($booking->first_name); ?>,</span>
                        <?php echo e(__('your booking was submitted successfully!')); ?>

                    </p>
                    <p class="line2"><?php echo e(__('Booking details has been sent to:')); ?> <span><?php echo e($booking->email); ?></span></p>
                    <?php if($note = $gateway->getOption("payment_note")): ?>
                        <div class="line2"><?php echo clean($note); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4 col-md-4">
        <ul class="booking-info-detail">
            <li><span><?php echo e(__('Booking Number')); ?>:</span> <?php echo e($booking->id); ?></li>
            <li><span><?php echo e(__('Booking Date')); ?>:</span> <?php echo e(display_date($booking->created_at)); ?></li>
            <?php if(!empty($gateway)): ?>
                <li><span><?php echo e(__('Payment Method')); ?>:</span> <?php echo e($gateway->name); ?></li>
            <?php endif; ?>
            <li><span><?php echo e(__('Booking Status')); ?>:</span> <span class="badge badge-primary badge-<?php echo e($booking->status); ?>"><?php echo e($booking->status_name); ?></span></li>
        </ul>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Booking/Views/frontend/global/booking-detail-notice.blade.php ENDPATH**/ ?>