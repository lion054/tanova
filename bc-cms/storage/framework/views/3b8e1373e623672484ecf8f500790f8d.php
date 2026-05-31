<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e($key->name); ?> — <small class="text-muted"><?php echo e($key->vendor?->email); ?></small></h1>
        <?php if($key->active): ?>
        <form method="POST" action="<?php echo e(route('vendor.admin.api-keys.revoke', $key->id)); ?>"
              onsubmit="return confirm('Revoke this key?')">
            <?php echo csrf_field(); ?>
            <button class="btn btn-danger">Revoke Key</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0"><?php echo e(number_format($monthly_count)); ?></div>
                <small class="text-muted">Requests this month</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0"><?php echo e(number_format($key->rate_limit)); ?></div>
                <small class="text-muted">Monthly limit</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0"><?php echo e($key->active ? 'Active' : 'Revoked'); ?></div>
                <small class="text-muted">Status</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="h4 mb-0"><?php echo e($key->last_used_at?->diffForHumans() ?? '—'); ?></div>
                <small class="text-muted">Last used</small>
            </div>
        </div>
    </div>

    
    <div class="panel mb-4">
        <div class="panel-heading"><h3 class="panel-title">Daily usage — last 30 days</h3></div>
        <div class="panel-body">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Requests</th><th>Errors</th><th>Avg ms</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $usage_by_day; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($day->date); ?></td>
                    <td><?php echo e($day->total); ?></td>
                    <td class="<?php echo e($day->errors > 0 ? 'text-danger' : ''); ?>"><?php echo e($day->errors); ?></td>
                    <td><?php echo e(round($day->avg_ms)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="text-center">No usage in the last 30 days.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="panel">
        <div class="panel-heading"><h3 class="panel-title">Top endpoints (this month)</h3></div>
        <div class="panel-body">
            <table class="table table-sm">
                <thead><tr><th>Method</th><th>Endpoint</th><th>Calls</th><th>Avg ms</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $top_endpoints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><span class="badge badge-secondary"><?php echo e($ep->method); ?></span></td>
                    <td><code><?php echo e($ep->endpoint); ?></code></td>
                    <td><?php echo e($ep->total); ?></td>
                    <td><?php echo e(round($ep->avg_ms)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="text-center">No data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/api-keys/show.blade.php ENDPATH**/ ?>