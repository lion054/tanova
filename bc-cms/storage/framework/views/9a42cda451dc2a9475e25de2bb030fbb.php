
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('dist/frontend/module/boat/css/boat.css?_ver='.config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset("libs/ion_rangeslider/css/ion.rangeSlider.min.css")); ?>"/>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="header-margin"></div>

    <?php if($layout != 'grid'): ?>
        <section class="pt-40 pb-40 bg-light-2">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="text-center">
                            <h1 class="text-30 fw-600"><?php echo e(setting_item_with_lang("boat_page_search_title")); ?></h1>
                        </div>

                        <?php echo $__env->make('Boat::frontend.layouts.search.form-search', ['style' => 'default'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="layout-pt-md layout-pb-lg">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-lg-4">
                    <?php if(!is_mobile()): ?>
                        <?php echo $__env->make('Boat::frontend.layouts.search.sidebar-form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endif; ?>
                </div>
                <div class="col-xl-9 col-lg-8">
                    <?php echo $__env->make('Boat::frontend.layouts.search.list-item', ['layout' => $layout], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </section>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset("libs/ion_rangeslider/js/ion.rangeSlider.min.js")); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('js/filter.js?_ver='.config('app.asset_version'))); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('module/boat/js/boat.js?_ver='.config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/search.blade.php ENDPATH**/ ?>