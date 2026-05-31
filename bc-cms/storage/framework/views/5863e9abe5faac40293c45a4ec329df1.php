<div
    id="block-<?php echo e(str_replace('.','',$__nodeId) ?? ''); ?>" class="live-block-preview selectable <?php echo e($wrapper_class  ?? ''); ?>"
    x-on:click.prevent.stop="window.LivePreview.selectItem('<?php echo e($__nodeId ?? ''); ?>')"
    style="<?php echo e($css_code ?? ''); ?>"

>
    <div class="block-info">
        <div><?php echo e($this->getTitle()); ?></div>
    </div>
    <div class="block-preview">
        <?php echo $__env->make($__view, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Template/Views/frontend/preview-layout.blade.php ENDPATH**/ ?>