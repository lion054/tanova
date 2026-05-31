<div class="page-template-content">
        <?php echo e($this->translation->getProcessedContent(['preview'=>1])); ?>

    </div>
<?php $__env->startPush('css'); ?>
    <link
        rel="stylesheet"
        href="<?php echo e(asset('module/template/preview/dist/css/app.css?_v='.config('app.asset_version'))); ?>"
    />
<?php $__env->stopPush(); ?>
<?php $__env->startPush('js'); ?>
    <script>
        var template_id = <?php echo e($this->translation->id ?? 0); ?>;
        var current_menu_lang = '<?php echo e(request()->query('lang',app()->getLocale())); ?>';
        var preview_routes = {
            preview: '<?php echo e(route('template.admin.live.preview')); ?>'
        }
    </script>
    <script
        type="module"
        src="<?php echo e(asset('module/template/preview/dist/js/app.js?_v='.config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Template/Views/frontend/preview.blade.php ENDPATH**/ ?>