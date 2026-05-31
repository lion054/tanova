<?php $__env->startSection('title', __('Set New Password') . ' — ' . config('app.name', 'Tsoka Travel')); ?>

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
    <h1>Set a new<br><em>password.</em></h1>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('brand-sub'); ?>
    <p>Choose something strong. You won't need to do this often.</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="form-heading">
    <h2><?php echo e(__('New Password')); ?></h2>
    <p><?php echo e(__('Back to')); ?> <a href="<?php echo e(route('login')); ?>"><?php echo e(__('sign in')); ?></a></p>
</div>

<?php if($errors->any()): ?>
    <div class="alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('password.update')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="token" value="<?php echo e(request()->route('token')); ?>">

    <div class="field">
        <label><?php echo e(__('Email Address')); ?></label>
        <input type="email" name="email" value="<?php echo e(old('email', request()->email)); ?>"
               required autofocus placeholder="you@example.com">
    </div>

    <div class="field">
        <label><?php echo e(__('New Password')); ?></label>
        <input type="password" name="password" required placeholder="••••••••••">
    </div>

    <div class="field">
        <label><?php echo e(__('Confirm Password')); ?></label>
        <input type="password" name="password_confirmation" required placeholder="••••••••••">
    </div>

    <button type="submit" class="btn-auth"><?php echo e(__('Reset Password')); ?></button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/auth/passwords/reset.blade.php ENDPATH**/ ?>