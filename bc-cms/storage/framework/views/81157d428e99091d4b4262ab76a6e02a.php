<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            
            <?php if(!empty($subscription) && $subscription->isActive()): ?>
                <?php
                    $daysLeft = (int) now()->diffInDays($subscription->ends_at, false);
                    $isExpiringSoon = $daysLeft <= 7;
                ?>
                <div class="alert alert-<?php echo e($isExpiringSoon ? 'warning' : 'success'); ?>">
                    <?php if($isExpiringSoon): ?>
                        <strong><?php echo e(__('Your plan expires in :d day(s).', ['d' => $daysLeft])); ?></strong>
                        <?php echo e(__('Contact the platform admin to renew before your access is suspended.')); ?>

                    <?php else: ?>
                        <strong><?php echo e(__('Active subscription')); ?></strong> — <?php echo e(__('your plan is current.')); ?>

                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Current Plan')); ?></strong></div>
                    <div class="panel-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="200px"><?php echo e(__('Plan')); ?></th>
                                <td><strong><?php echo e($subscription->plan->name); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php echo e(__('Platform Commission')); ?></th>
                                <td><?php echo e($subscription->plan->base_commission); ?>% <?php echo e(__('per booking')); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo e(__('Billing Cycle')); ?></th>
                                <td><?php echo e($subscription->billing_cycle_label); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo e(__('Amount Paid')); ?></th>
                                <td><?php echo e(format_money($subscription->amount_paid)); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo e(__('Started')); ?></th>
                                <td><?php echo e(display_date($subscription->starts_at)); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo e(__('Expires')); ?></th>
                                <td>
                                    <?php echo e(display_date($subscription->ends_at)); ?>

                                    <span class="text-muted">(<?php echo e($daysLeft); ?> <?php echo e(__('days remaining')); ?>)</span>
                                </td>
                            </tr>
                        </table>

                        <a href="<?php echo e(route('vendor.subscription.plans')); ?>" class="btn btn-outline-primary btn-sm">
                            <?php echo e(__('View All Plans')); ?>

                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">
                    <strong><?php echo e(__('No active subscription.')); ?></strong>
                    <?php echo e(__('You need an active plan to manage your listings on this platform.')); ?>

                    <a href="<?php echo e(route('vendor.subscription.plans')); ?>" class="alert-link"><?php echo e(__('View Plans')); ?></a>
                </div>
            <?php endif; ?>

            
            <?php if($history->total() > 0): ?>
            <div class="panel mt-4">
                <div class="panel-title"><strong><?php echo e(__('Subscription History')); ?></strong></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th><?php echo e(__('Plan')); ?></th>
                                <th><?php echo e(__('Cycle')); ?></th>
                                <th><?php echo e(__('Amount')); ?></th>
                                <th><?php echo e(__('Start')); ?></th>
                                <th><?php echo e(__('End')); ?></th>
                                <th><?php echo e(__('Status')); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($sub->plan->name); ?></td>
                                <td><?php echo e($sub->billing_cycle_label); ?></td>
                                <td><?php echo e(format_money($sub->amount_paid)); ?></td>
                                <td><?php echo e(display_date($sub->starts_at)); ?></td>
                                <td><?php echo e(display_date($sub->ends_at)); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo e($sub->status === 'active'     ? 'success'   :
                                        ($sub->status === 'expired'   ? 'warning'   :
                                        ($sub->status === 'cancelled' ? 'danger'    : 'secondary'))); ?>">
                                        <?php echo e(\Modules\Vendor\Models\VendorSubscription::getAllStatuses()[$sub->status] ?? $sub->status); ?>

                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php echo e($history->links()); ?>

                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/subscription/index.blade.php ENDPATH**/ ?>