<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e(__("Vendor Subscriptions")); ?></h1>
        <div class="title-actions">
            <a href="<?php echo e(route('vendor.admin.subscription.assign')); ?>" class="btn btn-primary">
                <i class="fa fa-plus"></i> <?php echo e(__("Assign Plan")); ?>

            </a>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="filter-div d-flex justify-content-end mb-3">
        <form method="get" action="<?php echo e(route('vendor.admin.subscription.index')); ?>" class="d-flex flex-wrap" style="gap:8px">
            <?php
            $filterVendor = !empty(request('vendor_id')) ? \App\User::find(request('vendor_id')) : false;
            \App\Helpers\AdminForm::select2('vendor_id', [
                'configs' => [
                    'ajax'        => ['url' => route('user.admin.getForSelect2'), 'dataType' => 'json'],
                    'allowClear'  => true,
                    'placeholder' => __('-- All Vendors --'),
                ]
            ], !empty($filterVendor) ? [$filterVendor->id, $filterVendor->getDisplayName() . ' (#' . $filterVendor->id . ')'] : false);
            ?>
            <select name="plan_id" class="form-control" style="width:180px">
                <option value=""><?php echo e(__('-- All Plans --')); ?></option>
                <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($plan->id); ?>" <?php echo e(request('plan_id') == $plan->id ? 'selected' : ''); ?>><?php echo e($plan->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="status" class="form-control" style="width:150px">
                <option value=""><?php echo e(__('-- All Statuses --')); ?></option>
                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($key); ?>" <?php echo e(request('status') == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="btn btn-info"><?php echo e(__('Filter')); ?></button>
        </form>
    </div>

    <div class="text-right mb-2">
        <i><?php echo e(__('Found :total subscriptions', ['total' => $rows->total()])); ?></i>
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th width="70px"><?php echo e(__('#')); ?></th>
                        <th><?php echo e(__('Vendor')); ?></th>
                        <th><?php echo e(__('Plan')); ?></th>
                        <th width="110px"><?php echo e(__('Cycle')); ?></th>
                        <th width="120px"><?php echo e(__('Amount Paid')); ?></th>
                        <th width="140px"><?php echo e(__('Starts')); ?></th>
                        <th width="140px"><?php echo e(__('Expires')); ?></th>
                        <th width="90px"><?php echo e(__('Status')); ?></th>
                        <th width="100px"><?php echo e(__('Actions')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="status-<?php echo e($sub->status); ?>">
                            <td>#<?php echo e($sub->id); ?></td>
                            <td>
                                <a href="<?php echo e(route('user.admin.detail', ['id' => $sub->vendor_id])); ?>">
                                    <?php echo e($sub->vendor->getDisplayName()); ?>

                                </a>
                                <small class="text-muted d-block"><?php echo e($sub->vendor->email); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo e(route('vendor.admin.plan.edit', ['id' => $sub->plan_id])); ?>">
                                    <?php echo e($sub->plan->name); ?>

                                </a>
                                <small class="text-muted d-block"><?php echo e($sub->plan->base_commission); ?>% commission</small>
                            </td>
                            <td><?php echo e($sub->billing_cycle_label); ?></td>
                            <td><?php echo e(format_money($sub->amount_paid)); ?></td>
                            <td><?php echo e(display_date($sub->starts_at)); ?></td>
                            <td>
                                <?php echo e(display_date($sub->ends_at)); ?>

                                <?php if($sub->status === 'active' && $sub->ends_at): ?>
                                    <?php $daysLeft = (int) now()->diffInDays($sub->ends_at, false) ?>
                                    <?php if($daysLeft <= 7 && $daysLeft >= 0): ?>
                                        <span class="badge badge-warning"><?php echo e(__(':d days left', ['d' => $daysLeft])); ?></span>
                                    <?php elseif($daysLeft < 0): ?>
                                        <span class="badge badge-danger"><?php echo e(__('Overdue')); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo e($sub->status === 'active'    ? 'success' :
                                    ($sub->status === 'expired'  ? 'warning' :
                                    ($sub->status === 'cancelled'? 'danger'  : 'secondary'))); ?>">
                                    <?php echo e($statuses[$sub->status] ?? $sub->status); ?>

                                </span>
                            </td>
                            <td>
                                <?php if($sub->status === 'active'): ?>
                                    <form action="<?php echo e(route('vendor.admin.subscription.cancel', $sub->id)); ?>" method="post"
                                          onsubmit="return confirm(<?php echo json_encode(__('Cancel this subscription?'), 15, 512) ?>)"
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-sm btn-danger"><?php echo e(__('Cancel')); ?></button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo e(route('vendor.admin.subscription.assign')); ?>?vendor_id=<?php echo e($sub->vendor_id); ?>"
                                       class="btn btn-sm btn-info"><?php echo e(__('Renew')); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="9"><?php echo e(__('No subscriptions found.')); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php echo e($rows->appends(request()->query())->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/subscriptions/index.blade.php ENDPATH**/ ?>