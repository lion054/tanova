
<?php $__env->startPush('css'); ?>
    <style type="text/css">
        .bc-contact-block .section {
            padding: 80px 0 !important;
        }
    </style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div id="bc_content-wrapper">
        <?php echo $__env->make('Contact::frontend.blocks.contact.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Contact/Views/index.blade.php ENDPATH**/ ?>