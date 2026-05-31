
<?php $__env->startSection('content'); ?>
    <link href="<?php echo e(asset('dist/frontend/css/notification.css')); ?>" rel="newest stylesheet">
    <div id="bc_notify">
        <div class="pt-50 pb-50 bg-light-2 mb-40">
            <div class="text-center">
                <h1 class="text-30 fw-600"><?php echo e(__('All Notifications')); ?></h1>
            </div>
        </div>
        <div class="container my-5">
            <div class="row">
                <div class="col-3">
                    <div class="panel">
                        <ul class="dropdown-list-items p-0">
                            <li class="notification <?php if(empty($type)): ?> active <?php endif; ?>">
                                <i class="fa fa-inbox fa-lg mr-2"></i> <a
                                    href="<?php echo e(route('core.notification.loadNotify')); ?>">&nbsp;<?php echo e(__('All')); ?></a>
                            </li>
                            <li class="notification <?php if(!empty($type) && $type == 'unread'): ?> active <?php endif; ?>">
                                <i class="fa fa-envelope-o fa-lg mr-2"></i> <a
                                    href="<?php echo e(route('core.notification.loadNotify', ['type' => 'unread'])); ?>"><?php echo e(__('Unread')); ?></a>
                            </li>
                            <li class="notification <?php if(!empty($type) && $type == 'read'): ?> active <?php endif; ?>">
                                <i class="fa fa-envelope-open-o fa-lg mr-2"></i> <a
                                    href="<?php echo e(route('core.notification.loadNotify', ['type' => 'read'])); ?>"><?php echo e(__('Read')); ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php echo $__env->make('Core::frontend.notification.list', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
    <hr>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Core/Views/frontend/notification/index.blade.php ENDPATH**/ ?>