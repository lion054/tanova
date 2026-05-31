
<?php $__env->startPush('css'); ?>
    <style type="text/css">
        .profile-service-tabs .bc-profile-list-services .container{
            padding: 0;
        }
    </style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div class="page-profile-content page-template-content">
    <div class="container">
        <div class="">
            <div class="row">
                <div class="col-md-3">
                    <?php echo $__env->make('User::frontend.profile.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
                <div class="col-md-9">
                    <h3 class="profile-name"><?php echo e(__("Hi, I'm :name",['name'=>$user->getDisplayName()])); ?></h3>
                    <div class="profile-bio"><?php echo $user->bio; ?></div>
                    <?php echo $__env->make('User::frontend.profile.services', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="div" style="margin-top: 40px;">
                        <?php echo $__env->make('User::frontend.profile.reviews', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/profile/profile.blade.php ENDPATH**/ ?>