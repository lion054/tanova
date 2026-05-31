<?php $__env->startSection('title', __('Confirm Password') . ' — ' . config('app.name', 'Tsoka Travel')); ?>

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
    <h1>Secure<br><em>area.</em></h1>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('brand-sub'); ?>
    <p>Confirm your password to continue into this protected section of your account.</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="form-heading">
    <h2><?php echo e(__('Confirm Password')); ?></h2>
    <p><?php echo e(__('This is a protected area. Please re-enter your password to proceed.')); ?></p>
</div>

<?php if($errors->any()): ?>
    <div class="alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('password.confirm')); ?>">
    <?php echo csrf_field(); ?>

    <div class="field">
        <label><?php echo e(__('Password')); ?></label>
        <input type="password" name="password" required autocomplete="current-password"
               placeholder="••••••••••" autofocus>
    </div>

    <button type="submit" class="btn-auth"><?php echo e(__('Confirm')); ?></button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/auth/confirm-password.blade.php ENDPATH**/ ?>