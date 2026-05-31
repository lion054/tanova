
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('dist/frontend/module/flight/css/flight.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/ion_rangeslider/css/ion.rangeSlider.min.css')); ?>" />
    <style>
        .bc_wrap .bc_search_flight .bc_form_search {
            margin-bottom: 0px;
        }
    </style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc_search_flight">
        <div class="container">
            <div class=" pt-40 pb-40">
                <div class="text-center">
                    <h1 class="text-30 fw-600">
                        <?php echo e(setting_item_with_lang('flight_page_search_title')); ?>

                    </h1>
                </div>

                <?php echo $__env->make('Flight::frontend.layouts.search.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            </div>
        </div>
        <div class="layout-pt-md layout-pb-md bg-light-2">
            <div class="container">
                <div class="row">
                    <div class="col-xl-3 col-lg-4">
                        <?php echo $__env->make('Flight::frontend.layouts.search.filter-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-xl-9 col-lg-8">
                        <?php echo $__env->make('Flight::frontend.layouts.search.list-item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo $__env->make('Flight::frontend.layouts.search.modal-form-book', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/ion_rangeslider/js/ion.rangeSlider.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('js/filter.js?_ver=' . config('app.asset_version'))); ?>"></script>
    <script type="text/javascript"
        src="<?php echo e(asset('themes/gotrip/module/flight/js/flight.js?_ver=' . config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/search.blade.php ENDPATH**/ ?>