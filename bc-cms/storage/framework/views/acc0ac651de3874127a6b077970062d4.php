<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4"><?php echo e(__('Choose a Plan')); ?></h2>
            <p class="text-muted mb-4">
                <?php echo e(__('Each plan includes a platform subscription fee and a commission rate on every booking you receive. Higher plans carry lower commission rates.')); ?>

            </p>

            <?php if(!empty($currentSubscription) && $currentSubscription->isActive()): ?>
                <div class="alert alert-info">
                    <?php echo e(__('You are currently on the :plan plan, expiring :date.', [
                        'plan' => $currentSubscription->plan->name,
                        'date' => display_date($currentSubscription->ends_at),
                    ])); ?>

                    <?php echo e(__('To change your plan, please contact the platform administrator.')); ?>

                </div>
            <?php endif; ?>

            <div class="row">
                <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $isCurrent = !empty($currentSubscription) && $currentSubscription->plan_id == $plan->id && $currentSubscription->isActive();
                        $metas = $plan->meta->keyBy('post_type');
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 <?php echo e($isCurrent ? 'border-success shadow' : ''); ?>">
                            <?php if($isCurrent): ?>
                                <div class="card-header bg-success text-white text-center">
                                    <strong><?php echo e(__('Your Current Plan')); ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h4 class="card-title"><?php echo e($plan->name); ?></h4>

                                
                                <div class="mb-3">
                                    <span class="h3 font-weight-bold"><?php echo e(format_money($plan->price)); ?></span>
                                    <span class="text-muted">/<?php echo e(__('month')); ?></span>
                                    <?php if($plan->price_annual): ?>
                                        <?php $saving = round((1 - $plan->price_annual / ($plan->price * 12)) * 100) ?>
                                        <div class="text-success small mt-1">
                                            <?php echo e(format_money($plan->price_annual)); ?>/<?php echo e(__('year')); ?>

                                            <span class="badge badge-success"><?php echo e(__('Save :p%', ['p' => $saving])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                
                                <div class="alert alert-light border mb-3 py-2">
                                    <strong><?php echo e($plan->base_commission); ?>%</strong> <?php echo e(__('platform commission per booking')); ?>

                                </div>

                                
                                <ul class="list-unstyled flex-grow-1">
                                    <?php $__currentLoopData = $metas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($meta->enable): ?>
                                        <li class="mb-1">
                                            <i class="fa fa-check text-success mr-1"></i>
                                            <strong><?php echo e(ucfirst($type)); ?></strong>:
                                            <?php if($meta->maximum_create): ?>
                                                <?php echo e(__('up to :n listings', ['n' => $meta->maximum_create])); ?>

                                            <?php else: ?>
                                                <?php echo e(__('unlimited listings')); ?>

                                            <?php endif; ?>
                                            <?php if($meta->auto_publish): ?>
                                                <span class="badge badge-info badge-sm"><?php echo e(__('auto-publish')); ?></span>
                                            <?php endif; ?>
                                            <?php if($meta->commission && $meta->commission != $plan->base_commission): ?>
                                                <span class="text-muted small">(<?php echo e($meta->commission); ?>% commission)</span>
                                            <?php endif; ?>
                                        </li>
                                        <?php else: ?>
                                        <li class="mb-1 text-muted">
                                            <i class="fa fa-times text-danger mr-1"></i>
                                            <?php echo e(ucfirst($type)); ?>

                                        </li>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>

                                
                                <div class="mt-3">
                                    <?php if($isCurrent): ?>
                                        <button class="btn btn-success btn-block" disabled><?php echo e(__('Current Plan')); ?></button>
                                    <?php else: ?>
                                        <div class="alert alert-light border text-center small py-2">
                                            <?php echo e(__('To subscribe or upgrade, contact the platform administrator or email')); ?>

                                            <a href="mailto:<?php echo e(setting_item('admin_email', 'admin@tsokatravel.com')); ?>">
                                                <?php echo e(setting_item('admin_email', 'admin@tsokatravel.com')); ?>

                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php if($plans->isEmpty()): ?>
                <div class="alert alert-info"><?php echo e(__('No plans are available at the moment. Please check back soon.')); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/subscription/plans.blade.php ENDPATH**/ ?>