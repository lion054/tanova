<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb20">
        <div>
            <h1 class="title-bar"><?php echo e($meta['title']); ?></h1>
            <?php if($updatedAt): ?>
            <small class="text-muted">Last saved <?php echo e(\Carbon\Carbon::parse($updatedAt)->format('d M Y H:i')); ?></small>
            <?php endif; ?>
        </div>
        <a href="<?php echo e(route('admin.integrations.legals')); ?>" class="btn btn-outline-secondary btn-sm">← All Legals</a>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="panel">
        <div class="panel-body">
            <form method="POST" action="<?php echo e(route('admin.integrations.legals.save', $doc)); ?>">
                <?php echo csrf_field(); ?>

                <div class="mb-4">
                    <label class="form-label fw-semibold"><?php echo e($meta['title']); ?> Content</label>
                    <p class="text-muted small mb-2"><?php echo e($meta['desc']); ?>. Supports basic HTML formatting.</p>

                    
                    <?php
                        $editorLoaded = false;
                    ?>

                    <?php if(function_exists('has_action') && has_action('EDITOR_JS_STACK')): ?>
                        <?php $editorLoaded = true; ?>
                        <textarea name="content" id="legal-content-editor" class="form-control editor-content"
                                  rows="30"><?php echo e(old('content', $content)); ?></textarea>
                    <?php else: ?>
                        <textarea name="content" class="form-control" rows="30"
                                  style="font-family: monospace; font-size: 13px;"
                                  placeholder="Paste your <?php echo e($meta['title']); ?> content here (HTML supported)..."><?php echo e(old('content', $content)); ?></textarea>
                    <?php endif; ?>
                </div>

                
                <div class="mb-4">
                    <p class="small text-muted mb-2">Other legal documents:</p>
                    <?php
                        $allLegals = [
                            'tos'     => 'Terms of Service',
                            'privacy' => 'Privacy Policy',
                            'dpa'     => 'Data Processing Agreement',
                            'cookie'  => 'Cookie Policy',
                            'refund'  => 'Cancellation & Refund Policy',
                            'vendor'  => 'Vendor / Host Agreement',
                        ];
                    ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php $__currentLoopData = $allLegals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($k !== $doc): ?>
                        <a href="<?php echo e(route('admin.integrations.legals.edit', $k)); ?>"
                           class="btn btn-xs btn-outline-secondary"><?php echo e($label); ?></a>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save <?php echo e($meta['title']); ?></button>
                    <a href="<?php echo e(route('admin.integrations.legals')); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    
    <?php if($content): ?>
    <div class="panel mt-4">
        <div class="panel-title">Preview</div>
        <div class="panel-body" style="max-height:400px;overflow-y:auto;font-size:14px;line-height:1.7">
            <?php echo $content; ?>

        </div>
    </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Integrations/Views/admin/legals/edit.blade.php ENDPATH**/ ?>