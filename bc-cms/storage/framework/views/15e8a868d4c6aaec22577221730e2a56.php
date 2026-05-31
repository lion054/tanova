<div class="bc-column <?php if(empty($__isPreview)): ?> col-<?php echo e($size ?? 6); ?> <?php endif; ?>"
     style="<?php echo e((!empty($css_code) and empty($__isPreview)) ? $css_code : ''); ?>">
    <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nodeId => $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(componentExists($child['type'])): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split($child['type'], $child['model'] ?? []);

$__html = app('livewire')->mount($__name, $__params, $nodeId, $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if(empty($children) && !empty($__isPreview)): ?>
        <div class="b-border-dashed b-border-2 b-rounded-2xl b-m-10 b-min-h-64">

        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Template/Views/frontend/blocks/column.blade.php ENDPATH**/ ?>