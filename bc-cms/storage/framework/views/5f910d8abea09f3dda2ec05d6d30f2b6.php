<?php $__env->startSection('title', __('Two-Factor Authentication') . ' — ' . config('app.name', 'Tsoka Travel')); ?>

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
    <h1>Two-factor<br><em>check.</em></h1>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('brand-sub'); ?>
    <p>Open your authenticator app and enter the code to complete sign in.</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php if(request('type') == 'recovery_code'): ?>
<div class="form-heading">
    <h2><?php echo e(__('Recovery Code')); ?></h2>
    <p><a href="<?php echo e(route('two-factor.login')); ?>"><?php echo e(__('Use an authentication code instead')); ?></a></p>
</div>
<?php else: ?>
<div class="form-heading">
    <h2><?php echo e(__('Authentication Code')); ?></h2>
    <p><a href="<?php echo e(route('two-factor.login', ['type' => 'recovery_code'])); ?>"><?php echo e(__('Use a recovery code instead')); ?></a></p>
</div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(url('two-factor-challenge')); ?>">
    <?php echo csrf_field(); ?>

    <?php if(request('type') == 'recovery_code'): ?>
        <div class="field">
            <label><?php echo e(__('Recovery Code')); ?></label>
            <input type="text" name="recovery_code" required autofocus
                   autocomplete="one-time-code" placeholder="xxxx-xxxx-xxxx">
        </div>
    <?php else: ?>
        <div class="field">
            <label><?php echo e(__('6-digit Code')); ?></label>
            <input type="text" name="code" required autofocus
                   autocomplete="one-time-code" inputmode="numeric"
                   maxlength="6" placeholder="000000">
        </div>
    <?php endif; ?>

    <button type="submit" class="btn-auth"><?php echo e(__('Verify')); ?></button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/auth/two-factor-challenge.blade.php ENDPATH**/ ?>