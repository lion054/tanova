<?php $__env->startSection('title', __('Sign In') . ' — ' . config('app.name', 'Tsoka Travel')); ?>

<?php $__env->startSection('panel-style'); ?>
<style>
    #auth-left-panel {
        background:
            linear-gradient(to bottom,
                rgba(0,0,0,.82) 0%,
                rgba(0,0,0,.60) 40%,
                rgba(0,0,0,.85) 100%),
            url('https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=900&q=85')
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
    <h1>Welcome<br><em>back.</em></h1>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('brand-sub'); ?>
    <p>Sign in to manage your travel operations across EMEA.</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="form-heading">
    <h2><?php echo e(__('Sign In')); ?></h2>
    <p><?php echo e(__("Don't have an account?")); ?>

        <?php if(is_enable_registration()): ?>
            <a href="<?php echo e(route('auth.register')); ?>"><?php echo e(__('Create one')); ?></a>
        <?php endif; ?>
    </p>
</div>

<?php if(session('status')): ?>
    <div class="alert-success"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="alert-danger"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('login')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="redirect" value="<?php echo e(request()->query('redirect')); ?>">

    <div class="field">
        <label><?php echo e(__('Email')); ?></label>
        <input type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
               placeholder="you@example.com">
    </div>

    <div class="field">
        <label><?php echo e(__('Password')); ?></label>
        <input type="password" name="password" required placeholder="••••••••••">
    </div>

    <div class="form-extras">
        <label><input type="checkbox" name="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>> &nbsp;<?php echo e(__('Remember me')); ?></label>
        <a href="<?php echo e(route('password.request')); ?>"><?php echo e(__('Forgot password?')); ?></a>
    </div>

    <button type="submit" class="btn-auth"><?php echo e(__('Sign In')); ?></button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/auth/login.blade.php ENDPATH**/ ?>