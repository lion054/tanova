
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-20 lg:pb-40 md:pb-20">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"><?php echo e($row->id ? __('Edit: ').$row->title : __('Add new seat')); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__('AI-native airline operations. Seat inventory, fares, ancillaries, and distribution.')); ?></div>
        </div>
        <div class="col-auto">
            <a class="btn btn-info" href="<?php echo e(route('flight.vendor.seat.index',['flight_id'=>$currentFlight->id])); ?>">
                <i class="fa fa-hand-o-right"></i> <?php echo e(__("Manage Seats")); ?>

            </a>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="lang-content-box">
        <form novalidate action="<?php echo e(route('flight.vendor.seat.store',['flight_id'=>$currentFlight->id,'id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])); ?>" class="needs-validation" method="post">
            <?php echo csrf_field(); ?>
            <div class="form-add-service">
                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                    <a data-bs-toggle="tab" data-bs-target="#nav-tour-content" aria-selected="true" class="active"><?php echo e(__("1. Seat Content")); ?></a>
                </div>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-tour-content">
                        <?php echo $__env->make('Flight::admin.flight.seat.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <button class="button h-50 px-24 -dark-1 bg-blue-1 text-white" type="submit"><i class="fa fa-save mr-2"></i> <?php echo e(__('Save Changes')); ?></button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('libs/tinymce/js/tinymce/tinymce.min.js')); ?>" ></script>
    <script type="text/javascript" src="<?php echo e(asset('js/condition.js?_ver='.config('app.asset_version'))); ?>"></script>
    <script type="text/javascript" >
        jQuery(function ($) {
            "use strict"
            $(".btn_submit").on('click',function () {
                $(this).closest("form").submit();
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/manageFlight/seat/detail.blade.php ENDPATH**/ ?>