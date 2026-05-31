
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('module/booking/css/checkout.css?_ver='.config('app.asset_version'))); ?>" rel="stylesheet">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc-booking-page padding-content" >
        <div class="container">
            <div class="row booking-success-notice">
                <div class="col-lg-8 col-md-8">
                    <div class="d-flex align-items-center">
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
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <ul class="booking-info-detail">
                        <li><span><?php echo e(__('Booking Number')); ?>:</span> <?php echo e($booking->id); ?></li>
                        <li><span><?php echo e(__('Booking Date')); ?>:</span> <?php echo e(display_date($booking->created_at)); ?></li>
                        <?php if(!empty($gateway)): ?>
                        <li><span><?php echo e(__('Payment Method')); ?>:</span> <?php echo e($gateway->name); ?></li>
                        <?php endif; ?>
                        <li><span><?php echo e(__('Booking Status')); ?>:</span> <?php echo e($booking->status_name); ?></li>
                    </ul>
                </div>
            </div>
            <div class="row booking-success-detail">
                <div class="col-md-8">
                    <?php echo $__env->make($service->booking_customer_info_file ?? 'Booking::frontend/booking/booking-customer-info', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="text-center">
                        <a href="<?php echo e(route('user.booking_history')); ?>" class="btn btn-primary"><?php echo e(__('Booking History')); ?></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <?php echo $__env->make($service->checkout_booking_detail_file ?? '', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Booking/Views/frontend/detail.blade.php ENDPATH**/ ?>