<?php $__env->startSection('title', __('Create Account') . ' — ' . config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="row align-items-start">

    <div class="col-lg-5 d-none d-lg-block text-center" style="padding-top:40px;">
        <div class="mb-30">
            <h2 style="color:#0D2D2F;font-size:26px;font-weight:600;"><?php echo e(__('Join')); ?> <?php echo e(config('app.name')); ?></h2>
            <p style="color:#666;margin-top:10px;font-size:15px;">
                <?php echo e(__('Create an account to access the portal.')); ?><br>
                <?php echo e(__('Already signed up?')); ?>

                <a href="<?php echo e(route('login')); ?>" style="color:#C5E0DF;font-weight:600;"><?php echo e(__('Sign in')); ?></a>
            </p>
        </div>
        <img src="<?php echo e(url('/orion/images/sign-up.svg')); ?>" alt="" class="auth-illustration">
    </div>

    <div class="col-lg-6 ml-auto">
        <div class="user-form-wrapper">
            <div class="title-area pb-30">
                <h3><?php echo e(__('Create Account')); ?></h3>
                <p class="d-lg-none">
                    <?php echo e(__('Already have an account?')); ?>

                    <a href="<?php echo e(route('login')); ?>" style="color:#C5E0DF;"><?php echo e(__('Sign in')); ?></a>
                </p>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert-danger"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('auth.register.store')); ?>" class="bc-form-register">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="redirect" value="<?php echo e(request()->get('redirect')); ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('First Name')); ?></label>
                            <input type="text" name="first_name" value="<?php echo e(old('first_name')); ?>" required placeholder="<?php echo e(__('First Name')); ?>">
                        </div>
                        <span class="invalid-feedback error error-first_name" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('Last Name')); ?></label>
                            <input type="text" name="last_name" value="<?php echo e(old('last_name')); ?>" required placeholder="<?php echo e(__('Last Name')); ?>">
                        </div>
                        <span class="invalid-feedback error error-last_name" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('Phone')); ?></label>
                            <input type="text" name="phone" value="<?php echo e(old('phone')); ?>" placeholder="<?php echo e(__('Phone number')); ?>">
                        </div>
                        <span class="invalid-feedback error error-phone" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('Email')); ?></label>
                            <input type="email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="<?php echo e(__('Email address')); ?>">
                        </div>
                        <span class="invalid-feedback error error-email" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                    <div class="col-12">
                        <div class="input-group-wrapper">
                            <label><?php echo e(__('Password')); ?></label>
                            <input type="password" name="password" required minlength="8" placeholder="<?php echo e(__('Min. 8 characters')); ?>">
                        </div>
                        <span class="invalid-feedback error error-password" style="color:#e74c3c;font-size:13px;display:block;margin-top:-20px;margin-bottom:15px;"></span>
                    </div>
                </div>

                <div class="agreement-checkbox" style="margin-bottom:30px;">
                    <div>
                        <input type="checkbox" id="register-term" name="term">
                        <label for="register-term"><?php echo e(__('I accept the')); ?> <a href="#"><?php echo e(__('Terms')); ?></a> <?php echo e(__('and')); ?> <a href="#"><?php echo e(__('Privacy Policy')); ?></a></label>
                    </div>
                    <span class="error error-term" style="color:#e74c3c;font-size:13px;"></span>
                </div>

                <div class="error message-error" style="color:#e74c3c;font-size:14px;margin-bottom:15px;"></div>

                <button type="submit" class="theme-button-one" style="width:100%;display:block;border:none;cursor:pointer;">
                    <?php echo e(__('Create Account')); ?>

                </button>
            </form>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/resources/views/auth/register.blade.php ENDPATH**/ ?>