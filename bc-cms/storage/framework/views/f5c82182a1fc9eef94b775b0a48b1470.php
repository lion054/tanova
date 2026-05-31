
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-60 lg:pb-40 md:pb-32">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"><?php echo e(__("Booking Report")); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__("Real-time revenue intelligence — every booking tracked, reconciled, and AI-optimised.")); ?></div>
        </div>
        <div class="col-auto">
            <button class="btn btn-info"  data-bs-toggle="modal" data-bs-target="#filter"><?php echo e(__('Advanced Filter')); ?></button>
        </div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="py-30 px-30 rounded-4 bg-white shadow-3 booking-history-manager">
        <div class="tabs -underline-2 js-tabs">
            <div class="tabs__controls row x-gap-40 y-gap-10 lg:x-gap-20 js-tabs-controls">
                <?php $status_type = Request::query('status'); ?>
                <div class="col-auto">
                    <a href="<?php echo e(route("vendor.bookingReport")); ?>" class="tabs__button text-18 lg:text-16 text-light-1 fw-500 pb-5 lg:pb-0 <?php if(empty($status_type)): ?> is-tab-el-active <?php endif; ?>">
                        <?php echo e(__("All Booking")); ?>

                    </a>
                </div>
                <?php if(!empty($statues)): ?>
                    <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-auto">
                            <a href="<?php echo e(route("vendor.bookingReport",['status'=>$status])); ?>" class="tabs__button text-18 lg:text-16 text-light-1 fw-500 pb-5 lg:pb-0 <?php if(!empty($status_type) && $status_type == $status): ?> is-tab-el-active <?php endif; ?>" >
                                <?php echo e(booking_status_to_text($status)); ?>

                            </a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>
            <div class="tabs__content pt-30 js-tabs-content">
                <div class="tabs__pane -tab-item-1 is-tab-el-active">
                    <div class="overflow-scroll scroll-bar-1">
                        <?php if(!empty($bookings) and $bookings->total() > 0): ?>
                            <table class="table-3 -border-bottom col-12">
                                <thead class="bg-light-2">
                                <tr>
                                    <th width="2%"><?php echo e(__("Type")); ?></th>
                                    <th><?php echo e(__("Title")); ?></th>
                                    <th class="a-hidden"><?php echo e(__("Order Date")); ?></th>
                                    <th class="a-hidden"><?php echo e(__("Execution Time")); ?></th>
                                    <th width="15%"><?php echo e(__("Payment Detail")); ?></th>
                                    <th><?php echo e(__("Commission")); ?></th>
                                    <th class="a-hidden"><?php echo e(__("Status")); ?></th>
                                    <th><?php echo e(__("Action")); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo $__env->make(ucfirst($booking->object_model).'::frontend.bookingReport.loop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                            <div class="bc-pagination pt-30">
                                <?php echo e($bookings->appends(request()->query())->links()); ?>

                            </div>
                        <?php else: ?>
                            <?php echo e(__("No Booking History")); ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="filter" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content modal-lg">
                <form action="">
                    <input type="hidden" name="status" value="<?php echo e(request()->input('status')); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"><?php echo e(__("Advanced Filter")); ?></h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="exampleInputEmail1"><?php echo e(__("Customer name")); ?></label>
                            <input type="text" name="customer_name" class="form-control" placeholder="<?php echo e(__("Customer Name")); ?>" value="<?php echo e(request()->input("customer_name")); ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1"><?php echo e(__("From - To")); ?></label>
                            <div id="reportrange">
                                <input type="text" class="form-control" name="date" placeholder="<?php echo e(__("From - To")); ?>" value="<?php echo e(request()->input("date")); ?>">
                                <input type="hidden" name="from" value="<?php echo e(date("Y-m-d",strtotime('today'))); ?>">
                                <input type="hidden" name="to" value="<?php echo e(date("Y-m-d",strtotime('today'))); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__("Close")); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo e(__("Filter")); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script>
        $(document).on('click', '#set_paid_btn', function (e) {
            var id = $(this).data('id');
            $.ajax({
                url:bookingCore.url+'/booking/setPaidAmount',
                data:{
                    id: id,
                    remain: $('#modal-paid-'+id+' #set_paid_input').val(),
                },
                dataType:'json',
                type:'post',
                success:function(res){
                    alert(res.message);
                    window.location.reload();
                }
            });
        });
        jQuery(function ($){
            $('.btn-info-booking').on('click',function (e){
                var btn = $(this);
                var modal = $("#modal_booking_detail");
                modal.find('.user_id').html(btn.data('id'));
                modal.find('.modal-body').html('<div class="d-flex justify-content-center"><?php echo e(__("Loading...")); ?></div>');
                $.get(btn.data('ajax'), function (html){
                        modal.find('.modal-body').html(html);
                    }
                )
            })
        })
        jQuery(function ($){
            function cb(start, end) {
                $('#reportrange input[name=date]').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                $('#reportrange input[name=from]').val(start.format('YYYY-MM-DD'));
                $('#reportrange input[name=to]').val(end.format('YYYY-MM-DD'));
            }
            $('#reportrange  input[name=date]').daterangepicker({
                "alwaysShowCalendars": true,
                "opens": "center",
                "showDropdowns": true,
            }, cb).on('apply.daterangepicker', function (ev, picker) {
                $('#reportrange input[name=from]').val(picker.startDate.format('YYYY-MM-DD'));
                $('#reportrange input[name=to]').val(picker.endDate.format('YYYY-MM-DD'));
            });
        })
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Vendor/Views/frontend/bookingReport/index.blade.php ENDPATH**/ ?>