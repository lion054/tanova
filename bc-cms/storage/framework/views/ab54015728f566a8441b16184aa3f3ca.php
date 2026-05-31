
<?php $__env->startPush('css'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('dist/frontend/module/support/css/support.css?_v='.config('app.asset_version'))); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Support::frontend.layouts.topic.search-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="topic-lists-wrap mb-4">
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <div class="page-header d-flex align-items-center justify-content-between">
                        <h1 class="text-24">
                            <?php if(!empty($cat)): ?>
                                <i class="fa fa-folder mr-1"></i>
                            <?php endif; ?>
                            <?php echo e($page_title); ?></h1>
                        <?php if(auth()->user()->hasPermission('support_ticket_create')): ?>

                            <a href="<?php echo e(route('support.ticket.create')); ?>" class="mb-4 btn btn-info btn-sm">
                                <i class="fa fa-plus"></i> <?php echo e(__("Ask a question")); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="topic-lists py-4 list-group">
                        <div class="list-group-item list-group-item-light py-3">
                            <div class="topic-item d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <span class="text-16"><?php echo e(__("Ticket name")); ?></span>
                                </div>
                                <div class="mr-3">
                                    <span class="text-16"><?php echo e(__("Last reply")); ?></span>
                                </div>
                                <div>
                                    <span class="text-16"><?php echo e(__("Status")); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('Support::frontend.layouts.ticket.loop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="bc-pagination">
                        <?php echo e($rows->appends(request()->query())->links()); ?>

                        <?php if($rows->total() > 0): ?>
                            <span class="count-string"><?php echo e(__("Showing :from - :to of :total tickets",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if(!count($rows)): ?>
                        <div class="alert alert-warning"><?php echo e(__("No ticket found")); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <?php echo $__env->make('Support::frontend.layouts.ticket.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/ticket/index.blade.php ENDPATH**/ ?>