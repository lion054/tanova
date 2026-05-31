<div class="form-checkout" id="form-checkout" >
    <input type="hidden" name="code" value="<?php echo e($booking->code); ?>">
    <div class="form-section">
        <div class="row x-gap-20 y-gap-20 pt-20">

            <?php if(is_enable_guest_checkout() && is_enable_registration()): ?>
                <div class="col-12">
                    <div class="">
                        <label for="confirmRegister" class="flex ">
                            <input style="width: auto" type="checkbox" name="confirmRegister" id="confirmRegister" value="1">
                            <?php echo e(__('Create a new account?')); ?>

                        </label>
                    </div>
                </div>
            <?php endif; ?>
            <?php if(is_enable_guest_checkout()): ?>
                <div class="col-12 d-none" id="confirmRegisterContent">
                    <div class="row">
                        <div class="col-md-6" >
                            <div class="form-input ">
                                <input type="password" class="form-control" name="password" autocomplete="off" >
                                <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Password")); ?> <span class="required">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-input ">
                                <input type="password" class="form-control" name="password_confirmation" autocomplete="off">
                                <label class="lh-1 text-16 text-light-1" ><?php echo e(__('Password confirmation')); ?> <span class="required">*</span></label>
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
            <?php endif; ?>
            <div class="col-md-6">
                <div class="form-input ">
                    <input type="text"  class="form-control" value="<?php echo e($user->first_name ?? ''); ?>" name="first_name">
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("First Name")); ?> <span class="required">*</span></label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-input ">
                    <input type="text"  class="form-control" value="<?php echo e($user->last_name ?? ''); ?>" name="last_name">
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Last Name")); ?> <span class="required">*</span></label>
                </div>
            </div>
            <div class="col-md-6 field-email">
                <div class="form-input ">
                    <input type="email"  class="form-control" value="<?php echo e($user->email ?? ''); ?>" name="email">
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Email")); ?> <span class="required">*</span></label>

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-input ">
                    <input type="text"  class="form-control" value="<?php echo e($user->phone ?? ''); ?>" name="phone">
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Phone")); ?> <span class="required">*</span></label>

                </div>
            </div>
            <div class="col-md-6 field-address-line-1">
                <div class="form-input ">
                    <input type="text"  class="form-control" value="<?php echo e($user->address ?? ''); ?>" name="address_line_1">
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Address line 1")); ?> </label>

                </div>
            </div>
            <div class="col-md-6 field-address-line-2">
                <div class="form-input ">
                    <input type="text"  class="form-control" value="<?php echo e($user->address2 ?? ''); ?>" name="address_line_2">
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Address line 2")); ?> </label>

                </div>
            </div>
            <div class="col-md-6 field-city">
                <div class="form-input ">
                    <input type="text" class="form-control" value="<?php echo e($user->city ?? ''); ?>" name="city" >
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("City")); ?> </label>

                </div>
            </div>
            <div class="col-md-6 field-state">
                <div class="form-input ">
                    <input type="text" class="form-control" value="<?php echo e($user->state ?? ''); ?>" name="state" >
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("State/Province/Region")); ?> </label>

                </div>
            </div>
            <div class="col-md-6 field-zip-code">
                <div class="form-input ">
                    <input type="text" class="form-control" value="<?php echo e($user->zip_code ?? ''); ?>" name="zip_code" >
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("ZIP code/Postal code")); ?> </label>
                </div>
            </div>
            <div class="col-md-6 field-country">
                <div class="form-input ">
                    <select name="country" class="form-control">
                        <option value=""><?php echo e(__('-- Select --')); ?></option>
                        <?php $__currentLoopData = get_country_lists(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option <?php if(($user->country ?? '') == $id): ?> selected <?php endif; ?> value="<?php echo e($id); ?>"><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Country")); ?> <span class="required">*</span> </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-input">
                <textarea name="customer_notes" cols="30" rows="6" class="form-control" ></textarea>
                <label class="lh-1 text-16 text-light-1" ><?php echo e(__("Special Requirements")); ?> </label>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('Booking::frontend/booking/checkout-passengers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('Booking::frontend/booking/checkout-deposit', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make($service->checkout_form_payment_file ?? 'Booking::frontend/booking/checkout-payment', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
    $term_conditions = setting_item('booking_term_conditions');
    ?>
    <?php if(setting_item("booking_enable_recaptcha")): ?>
        <div class="form-input ">
            <?php echo e(recaptcha_field('booking')); ?>

        </div>
    <?php endif; ?>
    <div class="html_before_actions"></div>

    <p class="alert-text mt10" v-show=" message.content" v-html="message.content" :class="{'danger':!message.type,'success':message.type}"></p>

    <div class="row y-gap-20 items-center justify-between mb-40">
        <div class="col-auto">
            <div class="d-flex items-center">
                <div class="form-checkbox ">
                    <input type="checkbox" name="term_conditions">
                    <div class="form-checkbox__mark">
                        <div class="form-checkbox__icon icon-check"></div>
                    </div>
                </div>
                <div class="text-14 lh-10 text-light-1 ml-10"><?php echo e(__('I have read and accept the')); ?>  <a target="_blank" href="<?php echo e(get_page_url($term_conditions)); ?>"><?php echo e(__('terms and conditions')); ?></a></div>

            </div>
        </div>

        <div class="col-auto">
            <button class="button h-60 px-24 -dark-1 bg-blue-1 text-white" @click="doCheckout">
                <?php echo e(__('Book Now')); ?>

                <div class="icon-arrow-top-right ml-15"></div>
                <i class="fa fa-spin fa-spinner" v-show="onSubmit"></i>
            </button>

        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Booking/Views/frontend/booking/checkout-form.blade.php ENDPATH**/ ?>