<?php $__env->startSection('content'); ?>

    
    <div class="portal-header">
        <div>
            <p class="portal-eyebrow"><?php echo e(__("Account")); ?></p>
            <h1 class="portal-h1"><?php echo e(__("Booking History")); ?></h1>
        </div>
    </div>

    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="portal-card booking-history-manager">
        <div class="tabs -underline-2 js-tabs">

            
            <div class="tabs__controls row x-gap-32 y-gap-10 js-tabs-controls"
                 style="border-bottom:1px solid #e8e8e8;padding-bottom:0;margin-bottom:24px;">
                <?php $status_type = Request::query('status'); ?>
                <div class="col-auto">
                    <a href="<?php echo e(route('user.booking_history')); ?>"
                       class="tabs__button fw-500 pb-5 <?php if(empty($status_type)): ?> is-tab-el-active <?php endif; ?>">
                        <?php echo e(__("All")); ?>

                    </a>
                </div>
                <?php if(!empty($statues)): ?>
                    <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-auto">
                        <a href="<?php echo e(route('user.booking_history', ['status' => $status])); ?>"
                           class="tabs__button fw-500 pb-5 <?php if(!empty($status_type) && $status_type == $status): ?> is-tab-el-active <?php endif; ?>">
                            <?php echo e(booking_status_to_text($status)); ?>

                        </a>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>

            
            <div class="tabs__content js-tabs-content">
                <div class="tabs__pane -tab-item-1 is-tab-el-active">
                    <div class="overflow-scroll scroll-bar-1">
                        <?php if(!empty($bookings) && $bookings->total() > 0): ?>
                            <table class="table-3 -border-bottom col-12">
                                <thead class="bg-light-2">
                                    <tr>
                                        <th><?php echo e(__("Type")); ?></th>
                                        <th><?php echo e(__("Title")); ?></th>
                                        <th class="a-hidden"><?php echo e(__("Order Date")); ?></th>
                                        <th class="a-hidden"><?php echo e(__("Dates")); ?></th>
                                        <th><?php echo e(__("Total")); ?></th>
                                        <th><?php echo e(__("Paid")); ?></th>
                                        <th><?php echo e(__("Remain")); ?></th>
                                        <th class="a-hidden"><?php echo e(__("Status")); ?></th>
                                        <th><?php echo e(__("Action")); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php echo $__env->make(ucfirst($booking->object_model).'::frontend.bookingHistory.loop', ['key' => $key], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                            <div class="bc-pagination pt-30">
                                <?php echo e($bookings->appends(request()->query())->links()); ?>

                            </div>
                        <?php else: ?>
                            <div class="portal-empty">
                                <i class="fa fa-clock-o" style="font-size:28px;color:#d0d0d0;display:block;margin-bottom:10px;"></i>
                                <?php echo e(__("No bookings found")); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
$('.btn-info-booking').on('click', function (e) {
    var btn   = $(this);
    var modal = $('#modal_booking_detail');
    modal.find('.user_id').html(btn.data('id'));
    modal.find('.modal-body').html('<div class="d-flex justify-content-center"><?php echo e(__("Loading...")); ?></div>');
    $.get(btn.data('ajax'), function (html) {
        modal.find('.modal-body').html(html);
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/bookingHistory.blade.php ENDPATH**/ ?>