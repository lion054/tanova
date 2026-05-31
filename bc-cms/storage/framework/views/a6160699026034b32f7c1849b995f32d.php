<div class="card_stripe">
    <i class="icofont-ui-v-card bg"></i>
    <label>
        <span><?php echo e(__('Name on the Card')); ?></span>
        <input id="bc_card_name" name="card_name" placeholder="<?php echo e(__('Card Name')); ?>">
    </label>
    <label>
        <span><?php echo e(__('Card Number')); ?></span>
        <div id="bc_card_number" class="input"></div>
        <i class="icofont-credit-card"></i>
    </label>
    <label class="item">
        <span><?php echo e(__('Expiration')); ?></span>
        <div id="bc_stripe_card_expiry" class="input"></div>
    </label>
    <label class="item">
        <span><?php echo e(__('CVC')); ?></span>
        <div id="bc_stripe_card_cvc" class="input"></div>
    </label>
    <input name="token" type="hidden" value="" id="bc_stripe_token" />
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Booking/Views/frontend/gateways/stripe.blade.php ENDPATH**/ ?>