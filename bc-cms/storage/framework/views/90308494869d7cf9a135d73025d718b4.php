<h4><?php echo e(__("You have enabled factor authentication")); ?></h4>
<div class="mb-4 font-medium text-sm text-green-600">
    <?php echo e(__("When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application.")); ?>

</div>
<?php if(session('status') == 'two-factor-authentication-enabled'): ?>
    <div class="mb-4 font-medium text-sm text-green-600">
        <?php echo e(__("Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application.")); ?>

    </div>
    <?php echo request()->user()->twoFactorQrCodeSvg(); ?>

    <?php
    $codes = (array) request()->user()->recoveryCodes();
    ?>
    <?php if(!empty($codes)): ?>
        <hr>
        <div class="mt-3">
            <p><?php echo e(__('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.')); ?></p>
            <div class="p-3" style="background: #f3f3f3">
                <?php $__currentLoopData = $codes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="mb-2 font-weight-medium"><?php echo e($code); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<hr>
<button class="btn btn-danger btn-xs btn-disable-2fa"><?php echo e(__("Disable two factor authentication")); ?></button>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/2fa/parts/info.blade.php ENDPATH**/ ?>