<?php $__env->startSection('title', __('Verify Email') . ' — ' . config('app.name', 'Tsoka Travel')); ?>

<?php $__env->startSection('panel-style'); ?>
<style>
    #auth-left-panel {
        background:
            linear-gradient(to bottom,
                rgba(0,0,0,.85) 0%,
                rgba(0,0,0,.65) 40%,
                rgba(0,0,0,.88) 100%),
            url('https://images.unsplash.com/photo-1488085061387-422e29b40080?auto=format&fit=crop&w=900&q=85')
            center center / cover no-repeat;
    }
    #auth-left-panel::before { display: none; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('panel-deco'); ?>
    <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:28px;">
        <span style="width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.4);display:inline-block;"></span>
        <span style="font-size:10px;font-weight:500;letter-spacing:.12em;color:rgba(255,255,255,.4);text-transform:uppercase;">EMEA Travel Portal</span>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('brand-heading'); ?>
    <h1>Check your<br><em>inbox.</em></h1>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('brand-sub'); ?>
    <p>We sent a verification link to your email address. Click it to activate your account.</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="form-heading">
    <h2><?php echo e(__('Verify Your Email')); ?></h2>
    <p><?php echo e(__('Wrong account?')); ?> <a href="<?php echo e(route('logout')); ?>"
        onclick="event.preventDefault(); document.getElementById('verify-logout').submit();"><?php echo e(__('Sign out')); ?></a></p>
</div>

<?php if(session('resent')): ?>
    <div class="alert-success"><?php echo e(__('A fresh verification link has been sent to your email address.')); ?></div>
<?php endif; ?>

<p style="font-size:13px;color:var(--g600);line-height:1.7;margin-bottom:24px;">
    <?php echo e(__('Before proceeding, please check your email for a verification link.')); ?>

    <?php echo e(__("If you didn't receive it, click below to resend.")); ?>

</p>

<form action="<?php echo e(route('verification.send')); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <button type="submit" class="btn-auth"><?php echo e(__('Resend Verification Email')); ?></button>
</form>

<form id="verify-logout" action="<?php echo e(route('logout')); ?>" method="POST" style="display:none;"><?php echo csrf_field(); ?></form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/auth/verify.blade.php ENDPATH**/ ?>