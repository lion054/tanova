<ul class="nav nav-tabs mb-2">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#booking-detail-<?php echo e($booking->id); ?>"><?php echo e(__("Booking Detail")); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#booking-customer-<?php echo e($booking->id); ?>">
            <?php if(!empty($informationRole)): ?>
                <?php echo e(__("Customer Information")); ?>

            <?php else: ?>
                <?php echo e(__('Personal Information')); ?>

            <?php endif; ?>
        </a>
    </li>
    <?php if(count($booking->passengers)): ?>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#booking-guests-<?php echo e($booking->id); ?>">
                <?php echo e(__('Guests Information')); ?>

            </a>
        </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#booking-note-<?php echo e($booking->id); ?>">
            <?php echo e(__('Booking Note')); ?>

        </a>
    </li>
</ul>
<div class="tab-content">
    <div id="booking-detail-<?php echo e($booking->id); ?>" class="tab-pane active">
        <div class="booking-review">
            <div class="booking-review-content">
                <div class="review-section">
                    <div class="info-form">
                        <ul>
                            <li>
                                <div class="label"><?php echo e(__('Booking Status')); ?></div>
                                <div class="val"><?php echo e($booking->statusName); ?></div>
                            </li>
                            <li>
                                <div class="label"><?php echo e(__('Booking Date')); ?></div>
                                <div class="val"><?php echo e(display_date($booking->created_at)); ?></div>
                            </li>
                            <?php if(!empty($booking->gateway)): ?>
                                <?php $gateway = get_payment_gateway_obj($booking->gateway);?>
                                <?php if($gateway): ?>
                                    <li>
                                        <div class="label"><?php echo e(__('Payment Method')); ?></div>
                                        <div class="val"><?php echo e($gateway->name); ?></div>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php $vendor = $service->author; ?>
                            <?php if($vendor->hasPermission('dashboard_vendor_access') and !$vendor->hasPermission('dashboard_access')): ?>
                                <li>
                                    <div class="label"><?php echo e(__("Vendor")); ?></div>
                                    <div class="val"><a href="<?php echo e(route('user.profile',['id'=>$vendor->id])); ?>" target="_blank" ><?php echo e($vendor->getDisplayName()); ?></a></div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="more-booking-review">
            <?php echo $__env->make($service->checkout_booking_detail_file ?? '', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
    <div id="booking-customer-<?php echo e($booking->id); ?>" class="tab-pane fade">
        <?php echo $__env->make($service->booking_customer_info_file ?? 'Booking::frontend/booking/booking-customer-info', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <div id="booking-guests-<?php echo e($booking->id); ?>" class="tab-pane fade">
        <?php echo $__env->make($service->booking_passengers_info_file ?? 'Booking::frontend.detail.passengers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <div id="booking-note-<?php echo e($booking->id); ?>" class="tab-pane fade">
        <div class="bc-note-form">
            <div class="form-group ">
                <label for="note"><?php echo e(__("Note")); ?></label>
                <textarea class="form-control mb-2" id="note" name="note" rows="5"><?php echo e($booking->getMeta("note_for_vendor")); ?></textarea>
                <input type="hidden" name="id" value="<?php echo e($booking->id); ?>">
                <div class="message_box"></div>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary btn-submit-note-vendor"> <?php echo e(__("Save")); ?>

                    <i class="fa icon-loading fa-spinner fa-spin fa-fw d-none"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Booking/Views/frontend/detail/modal.blade.php ENDPATH**/ ?>