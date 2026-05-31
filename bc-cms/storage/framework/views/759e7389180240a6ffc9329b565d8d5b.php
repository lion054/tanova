
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-20 lg:pb-40 md:pb-20">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"> <?php echo e($row->id ? __('Edit: ').$row->title : __('Add new room')); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__('AI-native property management. Live inventory, smart pricing, automated operations.')); ?></div>
        </div>
        <div class="col-auto">
            <a class="btn btn-info" href="<?php echo e(route('hotel.vendor.room.index',['hotel_id'=>$hotel->id])); ?>">
                <i class="fa fa-hand-o-right"></i> <?php echo e(__("Manage Rooms")); ?>

            </a>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="mb-2">
        <?php if($row->id): ?>
            <?php echo $__env->make('Language::admin.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
    <div class="lang-content-box">
        <form novalidate action="<?php echo e(route('hotel.vendor.room.store',['hotel_id'=>$hotel->id,'id'=>($row->id) ? $row->id : '-1','lang'=>request()->query('lang')])); ?>" class="needs-validation" method="post">
            <?php echo csrf_field(); ?>
            <div class="form-add-service">
                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                    <a data-bs-toggle="tab" data-bs-target="#nav-tour-content" aria-selected="true" class="active"><?php echo e(__("1. Room Content")); ?></a>
                    <?php if(is_default_lang()): ?>
                        <a data-bs-toggle="tab" data-bs-target="#nav-tour-pricing" aria-selected="false"><?php echo e(__("2. Pricing")); ?></a>
                        <a data-bs-toggle="tab" data-bs-target="#nav-attribute" aria-selected="false"><?php echo e(__("3. Attributes")); ?></a>
                        <a data-bs-toggle="tab" data-bs-target="#nav-ical" aria-selected="false"><?php echo e(__("4. Ical")); ?></a>

                    <?php endif; ?>
                </div>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-tour-content">
                        <?php echo $__env->make('Hotel::admin.room.form-detail.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <?php if(is_default_lang()): ?>
                        <div class="tab-pane fade" id="nav-tour-pricing">
                            <?php echo $__env->make('Hotel::admin.room.form-detail.pricing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="tab-pane fade" id="nav-attribute">
                            <?php echo $__env->make('Hotel::admin.room.form-detail.attributes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="tab-pane fade" id="nav-ical">
                            <?php echo $__env->make('Hotel::admin.room.form-detail.ical', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endif; ?>
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
            $(".btn_submit").click(function () {
                $(this).closest("form").submit();
            });
        });
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/vendorHotel/room/detail.blade.php ENDPATH**/ ?>