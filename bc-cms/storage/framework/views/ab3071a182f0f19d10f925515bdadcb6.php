<?php $__env->startSection('title', __('Sign In') . ' — ' . config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="row align-items-center">

    <div class="col-lg-6 d-none d-lg-block text-center">
        <img src="<?php echo e(url('/orion/images/sign-up.svg')); ?>" alt="" class="auth-illustration">
        <div class="mt-30">
            <h2 style="color:#0D2D2F;font-size:28px;font-weight:600;">Welcome to <?php echo e(config('app.name')); ?></h2>
            <p style="color:#666;margin-top:10px;font-size:16px;">Your travel management portal.<br>Sign in to get started.</p>
        </div>
    </div>

    <div class="col-lg-5 ml-auto">
        <div class="user-form-wrapper">
            <div class="title-area pb-40">
                <h3><?php echo e(__('Sign In')); ?></h3>
                <p><?php echo e(__("Welcome back! Please login to your account.")); ?></p>
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

                <div class="row">
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('Email')); ?></label>
                            <input type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus placeholder="<?php echo e(__('Enter your email')); ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('Password')); ?></label>
                            <input type="password" name="password" required placeholder="<?php echo e(__('Enter your password')); ?>">
                        </div>
                    </div>
                </div>

                <div class="agreement-checkbox d-flex justify-content-between align-items-center" style="margin-bottom:30px;">
                    <div>
                        <input type="checkbox" id="remember" name="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                        <label for="remember"><?php echo e(__('Remember Me')); ?></label>
                    </div>
                    <a href="<?php echo e(route('password.request')); ?>" style="color:#0D2D2F;font-size:14px;"><?php echo e(__('Forgot Password?')); ?></a>
                </div>

                <button type="submit" class="theme-button-one" style="width:100%;display:block;border:none;cursor:pointer;">
                    <i class="fa fa-sign-in" aria-hidden="true"></i> &nbsp;<?php echo e(__('Login')); ?>

                </button>
            </form>

            <?php if(is_enable_registration()): ?>
                <p class="mt-20 text-center" style="color:#888;font-size:14px;">
                    <?php echo e(__("Don't have an account?")); ?>

                    <a href="<?php echo e(route('auth.register')); ?>" style="color:#0D2D2F;font-weight:600;"><?php echo e(__('Sign up')); ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/auth/login.blade.php ENDPATH**/ ?>