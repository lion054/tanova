

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Layout::parts.bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="page-profile-content page-template-content page-all-services">
        <div class="container">
            <div class="">
                <div class="row">
                    <div class="col-md-3">
                        <?php echo $__env->make('User::frontend.profile.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-9">
                        <?php if(view()->exists(ucfirst($type).'::frontend.profile.service')): ?>
                            <?php echo $__env->make(ucfirst($type).'::frontend.profile.service',['view_all'=>1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/User/Views/frontend/profile/all-services.blade.php ENDPATH**/ ?>