
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('All Reply')); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-4">
                <div class="panel">
                    <form action="<?php echo e(route('vendor.enquiry_report.replyStore',['enquiry'=>$enquiry])); ?>" method="post">
                        <?php echo csrf_field(); ?>
                    <div class="panel-title"><strong><?php echo e(__("Add Reply")); ?></strong></div>
                    <div class="panel-body">
                            <div class="form-group">
                                <label><?php echo e(__("Client Message:")); ?></label>
                                <div><strong><?php echo e(__("Name:")); ?></strong> <?php echo e($enquiry->name); ?></div>
                                <div><strong><?php echo e(__("Email:")); ?></strong> <?php echo e($enquiry->email); ?></div>
                                <div><strong><?php echo e(__("Phone:")); ?></strong> <?php echo e($enquiry->phone); ?></div>
                                <div><strong><?php echo e(__("Content:")); ?></strong> <?php echo e($enquiry->note); ?></div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label><?php echo e(__("Reply Content")); ?></label>
                                <textarea required name="content" class="form-control" cols="30" rows="10"></textarea>
                            </div>
                    </div>
                    <div class="panel-footer">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?php echo e(__('Add New')); ?></button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="p-3 bg-white rounded shadow-sm">
                    <h6 class="border-bottom border-gray pb-2 mb-0"><?php echo e(__('Recent updates')); ?></h6>
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="media text-muted pt-3">
                            <div class="bd-placeholder-img mr-2 rounded">
                                <img src="<?php echo e($row->author->avatar_url); ?>" class="bd-placeholder-img mr-2 rounded" width="32" height="32" alt="">
                            </div>
                            <div class="d-flex flex-justify-between flex-grow-1">
                                <div class="media-body pb-3 mb-0 small lh-125 border-bottom border-gray flex-grow-1">
                                    <strong class="d-block text-gray-dark"><?php echo e($row->author->display_name); ?></strong>
                                    <div>
                                        <?php echo clean($row->content); ?>

                                    </div>
                                </div>
                                <div class="flex-shrink-0"><?php echo e(display_datetime($row->created_at)); ?></div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="d-flex justify-content-end">
                    <?php echo e($rows->links()); ?>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/enquiry/reply.blade.php ENDPATH**/ ?>