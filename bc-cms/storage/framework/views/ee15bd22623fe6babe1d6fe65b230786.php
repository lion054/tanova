<div class="effect">
    <div class="owl-carousel">
        <?php $__currentLoopData = $list_slider; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $img = get_file_url($item['bg_image'],'full') ?>
            <div class="item" style="background-image: linear-gradient(0deg,rgba(0, 0, 0, 0.2),rgba(0, 0, 0, 0.2)),url('<?php echo e($img); ?>') !important">
                <h2 class="sub-heading text-center"><?php echo e($item['desc'] ?? ""); ?></h2>
                <h1 class="text-heading text-center"><?php echo e($item['title'] ?? ""); ?></h1>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <?php echo $__env->make("Template::frontend.blocks.form-search-all-service.form-search", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Template/Views/frontend/blocks/form-search-all-service/style-slider-ver2.blade.php ENDPATH**/ ?>