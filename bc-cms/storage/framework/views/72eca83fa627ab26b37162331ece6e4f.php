<?php $__env->startComponent('mail::message'); ?>
# API Key Rotated

Hello <?php echo e($vendorName); ?>,

Your API key **"<?php echo e($keyName); ?>"** has been successfully rotated.

**A new key was generated.** If you did not copy it at the time of rotation, you will need to log into the Tsoka portal and rotate it again to receive a new one.

<?php $__env->startComponent('mail::panel'); ?>
If you did **not** initiate this rotation, please contact support immediately and revoke all your API keys from the portal.
<?php echo $__env->renderComponent(); ?>

<?php $__env->startComponent('mail::button', ['url' => url('/vendor/portal/api-keys')]); ?>
Manage API Keys
<?php echo $__env->renderComponent(); ?>

Thanks,<br>
<?php echo e(config('app.name')); ?> Team
<?php echo $__env->renderComponent(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/emails/api-key-rotated.blade.php ENDPATH**/ ?>