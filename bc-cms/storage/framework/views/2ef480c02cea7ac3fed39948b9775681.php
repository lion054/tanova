<?php
    $list_sidebars = setting_item_with_lang("news_sidebar");
?>
<?php if($list_sidebars): ?>
    <?php
        $list_sidebars = json_decode($list_sidebars);
    ?>
    <?php $__currentLoopData = $list_sidebars; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $__env->make('News::frontend.layouts.sidebars.'.$item->type, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/News/Views/frontend/layouts/details/news-sidebar.blade.php ENDPATH**/ ?>