<div class="form-section mt-40">
    <h3 class="text-22 fw-500 mb-20"><?php echo e(__("How do you want to pay?")); ?></h3>
    <div class="accordion -simple row y-gap-20 pt-30 js-accordion">
        <?php $__currentLoopData = $gateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$gateway): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-12 accordion__item px-20">
                <div class=" border-light rounded-4 py-20">
                    <div class="accordion__button d-flex items-center">
                        <button class="button text-dark-1 shrink-0  px-20 " type="button">
                            <label class="d-flex items-center" data-toggle="collapse" data-target="#gateway_<?php echo e($k); ?>" >
                                <input type="radio" name="payment_gateway" value="<?php echo e($k); ?>">
                                <?php if($logo = $gateway->getDisplayLogo()): ?>
                                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($gateway->getDisplayName()); ?>">
                                <?php endif; ?>
                                <span class="shrink-0 ml-20"><?php echo e($gateway->getDisplayName()); ?></span>

                            </label>
                        </button>
                    </div>
                    <div id="gateway_<?php echo e($k); ?>" class="accordion__content" aria-labelledby="headingOne" data-parent="#accordionExample">
                        <div class="pt-20 pl-60">
                            <div class="gateway_name">
                                <?php echo $gateway->getDisplayName(); ?>

                            </div>
                            <?php echo $gateway->getDisplayHtml(); ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Booking/Views/frontend/booking/checkout-payment.blade.php ENDPATH**/ ?>