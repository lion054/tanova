
<?php $__env->startSection('content'); ?>
    <div class="row y-gap-20 justify-between items-end pb-60 lg:pb-40 md:pb-32">
        <div class="col-auto">
            <h1 class="text-30 lh-14 fw-600"><?php echo e($page_title); ?></h1>
            <div class="text-15 text-light-1"><?php echo e(__("Real-time revenue intelligence — every booking tracked, reconciled, and AI-optimised.")); ?></div>
        </div>
        <div class="col-auto"></div>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="booking-history-manager">
        <div class="tabbable">
            <div class="overflow-scroll scroll-bar-1">
                <table class="table-3 -border-bottom col-12">
                    <thead class="bg-light-2">
                    <tr>
                        <th width="2%"><?php echo e(__("Type")); ?></th>
                        <th><?php echo e(__('Service Info')); ?></th>
                        <th><?php echo e(__('Customer Info')); ?></th>
                        <th width="80px"><?php echo e(__('Status')); ?></th>
                        <th width="80px"><?php echo e(__('Replies')); ?></th>
                        <th width="180px"><?php echo e(__('Created At')); ?></th>
                        <th><?php echo e(__("Action")); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if($rows->total() > 0): ?>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="booking-history-type d-flex items-center">
                                    <?php if($service = $row->service): ?>
                                        <i class="<?php echo e($service->getServiceIconFeatured()); ?>"></i>
                                    <?php endif; ?>
                                    <small class="ml-2 text-capitalize"><?php echo e($row->object_model); ?></small>
                                </td>
                                <td>
                                    <?php if($service = $row->service): ?>
                                        <a href="<?php echo e($service->getDetailUrl()); ?>" target="_blank"><?php echo e($service->title ?? ''); ?></a>
                                    <?php else: ?>
                                        <?php echo e(__("[Deleted]")); ?>

                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo e(__("Name:")); ?> <?php echo e($row->name); ?></div>
                                    <div><?php echo e(__("Email:")); ?> <?php echo e($row->email); ?></div>
                                    <div><?php echo e(__("Phone:")); ?> <?php echo e($row->phone); ?></div>
                                    <div><?php echo e(__("Notes:")); ?> <?php echo e($row->note); ?></div>
                                </td>
                                <td>
                                    <span class="label label-<?php echo e($row->status); ?>"><?php echo e($row->statusName); ?></span>
                                </td>
                                <td><?php echo e($row->replies_count); ?></td>
                                <td><?php echo e(display_datetime($row->updated_at)); ?></td>
                                <td width="5%">
                                    <?php if(!empty( $has_permission_enquiry_update )): ?>
                                        <div class="dropdown js-dropdown js-actions-1-active">
                                            <div class="dropdown__button d-flex items-center rounded-4 text-blue-1 bg-blue-1-05 text-14 px-15 py-5" data-el-toggle=".js-actions-1-toggle" data-el-toggle-active=".js-actions-1-active">
                                                <span class="js-dropdown-title"><?php echo e(__("Action")); ?></span>
                                                <i class="icon icon-chevron-sm-down text-7 ml-10"></i>
                                            </div>
                                            <div class="toggle-element -dropdown-2 js-click-dropdown js-actions-1-toggle w-200 start-0" style="max-width:none;left: 0">
                                                <div class="text-14 fw-500 js-dropdown-list">
                                                    <div>
                                                        <a class="d-block" href="<?php echo e(route('vendor.enquiry_report.reply',['enquiry'=>$row])); ?>"><i class="icofont-long-arrow-right"></i> <?php echo e(__("Add Reply")); ?></a>
                                                    </div>
                                                    <?php if(!empty($statues)): ?>
                                                        <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div>
                                                                <a class="d-block" href="<?php echo e(\Illuminate\Support\Facades\URL::signedRoute("vendor.enquiry_report.bulk_edit" , ['id'=>$row->id , 'status'=>$status])); ?>">
                                                                    <i class="icofont-long-arrow-right"></i> <?php echo e(__('Mark as: :name',['name'=>booking_status_to_text($status)])); ?>

                                                                </a>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php echo e(__("No data")); ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="bc-pagination pt-30">
                    <?php echo e($rows->appends(request()->query())->links()); ?>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Vendor/Views/frontend/enquiry/index.blade.php ENDPATH**/ ?>