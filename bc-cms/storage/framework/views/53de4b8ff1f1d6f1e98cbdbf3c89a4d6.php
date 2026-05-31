
<?php $__env->startSection('content'); ?>
    <h2 class="title-bar no-border-bottom">
        <?php echo e($row->id ? __('Edit: ').$row->title : __('Add new seat')); ?>

        <div class="title-action">
            <a class="btn btn-info" href="<?php echo e(route('flight.vendor.seat.index',['flight_id'=>$currentFlight->id])); ?>">
                <i class="fa fa-hand-o-right"></i> <?php echo e(__("Manage Seats")); ?>

            </a>
        </div>
    </h2>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="lang-content-box">
        <form novalidate action="<?php echo e(route('flight.vendor.seat.store',['flight_id'=>$currentFlight->id,'id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])); ?>" class="needs-validation" method="post">
            <?php echo csrf_field(); ?>
            <div class="form-add-service">
                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                    <a data-toggle="tab" href="#nav-tour-content" aria-selected="true" class="active"><?php echo e(__("1. Seat Content")); ?></a>
                </div>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-tour-content">
                        <?php echo $__env->make('Flight::admin.flight.seat.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn btn-primary btn_submit" type="submit"><i class="fa fa-save"></i> <?php echo e(__('Save Changes')); ?></button>
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

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Flight/Views/frontend/manageFlight/seat/detail.blade.php ENDPATH**/ ?>