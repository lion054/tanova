<form action="<?php echo e(url('/user/two-factor-authentication')); ?>" id="bc-form-enable-2fa" method="post">
    <?php echo csrf_field(); ?>

    <h4><?php echo e(__("You have not enabled factor authentication")); ?></h4>

    <div class="mb-3"><button class="btn btn-warning"><?php echo e(__("Enable now")); ?></button></div>
    <p><?php echo e(__('Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to sign in')); ?></p>
</form>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/2fa/parts/setup.blade.php ENDPATH**/ ?>