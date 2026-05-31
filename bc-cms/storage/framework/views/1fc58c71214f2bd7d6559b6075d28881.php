<div class="bc-section" style="<?php echo e(empty($__isPreview) and !empty($css_code) ? $css_code : ''); ?>">
    <div class="<?php if($is_container === 'no'): ?> container-fluid <?php else: ?> container <?php endif; ?>">
        <div class="row ">
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
                <div class="b-border-dashed b-border-2 b-rounded-2xl b-m-10 b-min-h-64 b-border-blue-500">

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Template/Views/frontend/blocks/row.blade.php ENDPATH**/ ?>