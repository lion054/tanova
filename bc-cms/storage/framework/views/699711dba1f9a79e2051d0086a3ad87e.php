
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__("All tickets")); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                <?php if(!empty($rows)): ?>
                    <form
                        method="post"
                        action="<?php echo e(route('support.admin.ticket.bulkEdit')); ?>"
                        class="filter-form filter-form-left d-flex justify-content-start"
                    >
                        <?php echo e(csrf_field()); ?>

                        <select name="action" class="form-control">
                            <option value=""><?php echo e(__(" Bulk Actions ")); ?></option>
                            <option value="publish"><?php echo e(__(" Publish ")); ?></option>
                            <option value="draft"><?php echo e(__(" Move to Draft ")); ?></option>
                            <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                        </select>
                        <button
                            data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"
                        ><?php echo e(__('Apply')); ?></button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="col-left">
                <form
                    method="get"
                    action="<?php echo e(route('support.admin.ticket.index')); ?> "
                    class="filter-form filter-form-right d-flex justify-content-end flex-column flex-sm-row"
                    role="search"
                >
                    <input
                        type="text" name="s" value="<?php echo e(Request()->s); ?>" placeholder="<?php echo e(__('Search by name')); ?>" class="form-control"
                    >
                    <select name="cat_id" class="form-control">
                        <option value=""><?php echo e(__('--All Category --')); ?> </option>
                        <?php
                        if (!empty($categories)) {
                            foreach ($categories as $category) {
                                printf("<option value='%s' %s >%s</option>", $category->id, request('cat_id') == $category->id ? "selected" : '', strtoupper($category->product_line) . ' - ' . $category->name);
                            }
                        }
                        ?>
                    </select>
                    <button class="btn-info btn btn-icon btn_search" type="submit"><?php echo e(__('Search topic')); ?></button>
                </form>
            </div>
        </div>
        <div class="text-right">
            <p>
                <i><?php echo e(__('Found :total items',['total'=>$rows->total()])); ?></i>
            </p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="panel">
                    <div class="panel-body">
                        <form action="" class="bc-form-item">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th width="60px">
                                            <input type="checkbox" class="check-all">
                                        </th>
                                        <th class="title"> <?php echo e(__('Name')); ?></th>
                                        <th width="200px"> <?php echo e(__('Category')); ?></th>
                                        <th width="130px"> <?php echo e(__('Customer')); ?></th>
                                        <th width="130px"> <?php echo e(__('Agent')); ?></th>
                                        <th width="100px"> <?php echo e(__('Date')); ?></th>
                                        <th width="100px"><?php echo e(__('Status')); ?></th>
                                        <th width="100px"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if($rows->total() > 0): ?>
                                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="check-item" name="ids[]" value="<?php echo e($row->id); ?>">
                                                </td>
                                                <td class="title">
                                                    <a href="<?php echo e($row->getEditUrl()); ?>"><?php echo e($row->title); ?></a>
                                                </td>
                                                <td><?php echo e($row->cat->name ?? ''); ?></td>
                                                <td>
                                                    <?php echo e($row->customer->display_name ?? ''); ?>

                                                </td>
                                                <td>
                                                    <?php if($row->agent): ?>
                                                        <?php echo e($row->agent->display_name); ?>

                                                    <?php else: ?>
                                                        <div class="badge badge-warning"><?php echo e(__('Unassigned')); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td> <?php echo e(display_date($row->updated_at)); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo e($row->status); ?>"><?php echo e($row->status); ?></span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button
                                                            class="btn btn-default btn-sm dropdown-toggle"
                                                            type="button"
                                                            data-toggle="dropdown"
                                                            aria-expanded="false"
                                                        >
                                                            <?php echo e(__("Actions")); ?>

                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a href="<?php echo e($row->getEditUrl()); ?>" class="dropdown-item">
                                                                <i class="fa fa-edit"></i> <?php echo e(__('View Ticket')); ?></a>
                                                        </div>
                                                    </div>
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
                            </div>
                        </form>
                        <?php echo e($rows->appends(request()->query())->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/admin/ticket/index.blade.php ENDPATH**/ ?>