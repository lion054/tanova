<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e(__("Vendor API Keys")); ?></h1>
    </div>

    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0"><?php echo e(number_format($total_requests)); ?></div>
                <small class="text-muted">Requests this month</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0"><?php echo e($error_rate); ?>%</div>
                <small class="text-muted">Error rate (4xx/5xx)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0"><?php echo e($rows->total()); ?></div>
                <small class="text-muted">Total API keys</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="h4 mb-0"><?php echo e($rows->where('active', true)->count()); ?></div>
                <small class="text-muted">Active keys</small>
            </div>
        </div>
    </div>

    
    <form method="GET" class="d-flex gap-2 mb-3">
        <input type="number" name="vendor_id" class="form-control w-auto" placeholder="Vendor ID" value="<?php echo e(request('vendor_id')); ?>">
        <select name="active" class="form-control w-auto">
            <option value="">All status</option>
            <option value="1" <?php if(request('active')==='1'): echo 'selected'; endif; ?>>Active</option>
            <option value="0" <?php if(request('active')==='0'): echo 'selected'; endif; ?>>Revoked</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel">
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vendor</th>
                        <th>Key name</th>
                        <th>Status</th>
                        <th>Rate limit</th>
                        <th>Requests (this month)</th>
                        <th>Last used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $pct = $key->rate_limit > 0 ? round($key->monthly_requests / $key->rate_limit * 100) : 0; ?>
                    <tr>
                        <td><?php echo e($key->id); ?></td>
                        <td>
                            <a href="<?php echo e(route('user.admin.edit', $key->vendor_id)); ?>">
                                <?php echo e($key->vendor?->name ?? $key->vendor?->email ?? $key->vendor_id); ?>

                            </a>
                        </td>
                        <td><a href="<?php echo e(route('vendor.admin.api-keys.show', $key->id)); ?>"><?php echo e($key->name); ?></a></td>
                        <td>
                            <?php if($key->active): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e(number_format($key->rate_limit)); ?>/mo</td>
                        <td>
                            <div class="progress" style="height:6px; width:100px; display:inline-block; vertical-align:middle;">
                                <div class="progress-bar <?php echo e($pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success')); ?>"
                                     style="width:<?php echo e(min($pct,100)); ?>%"></div>
                            </div>
                            <small><?php echo e(number_format($key->monthly_requests)); ?> (<?php echo e($pct); ?>%)</small>
                        </td>
                        <td><?php echo e($key->last_used_at?->diffForHumans() ?? '—'); ?></td>
                        <td>
                            <a href="<?php echo e(route('vendor.admin.api-keys.show', $key->id)); ?>" class="btn btn-xs btn-info">View</a>
                            <?php if($key->active): ?>
                            <form method="POST" action="<?php echo e(route('vendor.admin.api-keys.revoke', $key->id)); ?>" style="display:inline"
                                  onsubmit="return confirm('Revoke this key?')">
                                <?php echo csrf_field(); ?>
                                <button class="btn btn-xs btn-danger">Revoke</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-center">No API keys found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php echo e($rows->withQueryString()->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/api-keys/index.blade.php ENDPATH**/ ?>