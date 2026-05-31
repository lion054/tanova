<?php if($totalPassenger = $booking->calTotalPassenger()  and $booking->object_model !== 'visa'): ?>
    <?php $old_data = old('passengers', []) ?>
    <div class="form-section mt-40">
        <h3 class="text-22 fw-500 mb-20"><?php echo e(__("Tickets / Guests Information:")); ?></h3>
        <div class="accordion -simple row y-gap-20 pt-30 js-accordion" id="passengers_info">
            <?php for($i = 1 ; $i <= $totalPassenger ; $i ++): ?>
                <?php $old_item = $old_data[$i] ?? []; ?>
                <div class="col-12 accordion__item px-20 py-20 border-light rounded-4">
                    <div class="accordion__button d-flex items-center" id="passenger_heading_<?php echo e($i); ?>">
                        <div class="accordion__icon size-40 flex-center bg-light-2 rounded-full mr-20">
                            <i class="icon-plus"></i>
                            <i class="icon-minus"></i>
                        </div>
                        <button class="button text-dark-1" data-toggle="collapse" data-target="#passenger_<?php echo e($i); ?>" aria-expanded="true"
                            aria-controls="passenger_<?php echo e($i); ?>">
                            <?php echo e(__("Guest #:number",['number'=>$i])); ?>:
                        </button>
                    </div>

                    <div id="passenger_<?php echo e($i); ?>" class="accordion__content <?php if($i == 1): ?> show <?php endif; ?>"
                         aria-labelledby="passenger_heading_<?php echo e($i); ?>" data-parent="#passengers_info">
                        <div class="pt-20 pl-60">
                            <div class="row y-gap-20">
                                <div class="col-md-6">
                                    <div class="form-input">
                                        <input type="text"  class="form-control"
                                               value="<?php echo e($old_item['first_name'] ?? ''); ?>"
                                               name="passengers[<?php echo e($i); ?>][first_name]">
                                        <label class="lh-1 text-16 text-light-1"><?php echo e(__("First Name")); ?> </label>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-input">
                                        <input type="text"  class="form-control"
                                               value="<?php echo e($old_item['last_name'] ?? ''); ?>"
                                               name="passengers[<?php echo e($i); ?>][last_name]">
                                        <label class="lh-1 text-16 text-light-1"><?php echo e(__("Last Name")); ?></label>

                                    </div>
                                </div>
                                <div class="col-md-6 field-email ">
                                    <div class="form-input">
                                        <input type="email"
                                               class="form-control" value="<?php echo e($old_item['email'] ?? ''); ?>"
                                               name="passengers[<?php echo e($i); ?>][email]">
                                        <label class="lh-1 text-16 text-light-1"><?php echo e(__("Email")); ?> </label>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-input">
                                        <input type="text"  class="form-control"
                                               value="<?php echo e($old_item['phone'] ?? ''); ?>" name="passengers[<?php echo e($i); ?>][phone]">
                                        <label class="lh-1 text-16 text-light-1"><?php echo e(__("Phone")); ?> </label>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Booking/Views/frontend/booking/checkout-passengers.blade.php ENDPATH**/ ?>