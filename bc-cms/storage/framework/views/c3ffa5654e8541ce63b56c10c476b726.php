<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb20">
        <div>
            <h1 class="title-bar">Legals</h1>
            <p class="text-muted">Manage your platform's legal documents. Changes take effect immediately on the live site.</p>
        </div>
        <a href="<?php echo e(route('admin.integrations.hub')); ?>" class="btn btn-outline-secondary btn-sm">← Integrations</a>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="row g-4">
        <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="d-flex align-items-center justify-content-center rounded"
                             style="width:42px;height:42px;background:#f5f5f5;font-size:1.2rem;flex-shrink:0">
                            <i class="<?php echo e($meta['icon']); ?>"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?php echo e($meta['title']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo e($meta['desc']); ?></p>
                        </div>
                    </div>

                    <?php
                        $hasContent = !empty(setting_item("legal_{$key}_content"));
                        $updatedAt  = setting_item("legal_{$key}_updated_at");
                    ?>

                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <div>
                            <?php if($hasContent): ?>
                                <span class="badge bg-success">Published</span>
                                <?php if($updatedAt): ?>
                                <small class="text-muted ms-2"><?php echo e(\Carbon\Carbon::parse($updatedAt)->diffForHumans()); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">Empty</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo e(route('admin.integrations.legals.edit', $key)); ?>" class="btn btn-sm btn-primary">
                            <?php echo e($hasContent ? 'Edit' : 'Create'); ?>

                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Integrations/Views/admin/legals/index.blade.php ENDPATH**/ ?>