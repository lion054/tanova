
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"> <?php echo e(__('Categories')); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-4 mb40">
                <div class="panel">
                    <div class="panel-title"> <?php echo e(__('Add Category')); ?></div>
                    <div class="panel-body">
                        <form action="<?php echo e(route('support.admin.topic.category.store',['id'=>-1])); ?>" method="post">
                            <?php echo csrf_field(); ?>
                            <?php echo $__env->make('Support::admin/topic/category/form',['parents'=>$rows], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <div class="">
                                <button class="btn btn-primary" type="submit"> <?php echo e(__('Add new')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="filter-div d-flex justify-content-between ">
                    <div class="col-left">
                        <?php if(!empty($rows)): ?>
                            <form
                                method="post" action="<?php echo e(route('support.admin.topic.category.bulkEdit')); ?>"
                                  class="filter-form filter-form-left d-flex justify-content-start">
                                <?php echo e(csrf_field()); ?>

                                <select name="action" class="form-control">
                                    <option value=""><?php echo e(__(" Bulk Action ")); ?></option>
                                    <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                                </select>
                                <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="col-left">
                        <form
                            method="get"
                            action="<?php echo e(route('support.admin.topic.category.index')); ?> "
                            class="filter-form filter-form-right d-flex justify-content-end"
                            role="search"
                        >
                            <input type="text" name="s" value="<?php echo e(Request()->s); ?>" class="form-control">
                            <button class="btn-info btn btn-icon btn_search" id="search-submit" type="submit"><?php echo e(__('Search Category')); ?></button>
                        </form>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-body">
                        <form action="" class="bc-form-item">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th width="60px"><input type="checkbox" class="check-all"></th>
                                    <th> <?php echo e(__('Name')); ?></th>
                                    <th> <?php echo e(__('Image')); ?></th>
                                    <th> <?php echo e(__('Display Order')); ?></th>
                                    <th class=""> <?php echo e(__('Date')); ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if(count($rows) > 0): ?>
                                    <?php
                                    $traverse = function ($categories, $prefix = '') use (&$traverse) {
                                    foreach ($categories as $row) {
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" value="<?php echo e($row->id); ?>" class="check-item">
                                        </td>
                                        <td class="title">
                                            <a href="<?php echo e(route('support.admin.topic.category.edit',['id'=>$row->id])); ?>"><?php echo e($prefix.' '.$row->name); ?></a>
                                        </td>
                                        <td>
                                            <?php if($row->image_id): ?>
                                                <img src="<?php echo e(get_file_url($row->image_id)); ?>" width="90" alt="">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo e($row->display_order); ?>

                                        </td>
                                        <td class=""><?php echo e(display_date($row->updated_at)); ?></td>
                                        <td>
                                            <a
                                                class="btn btn-sm btn-default" href="<?php echo e(route('support.admin.topic.category.edit',['id'=>$row->id])); ?>"
                                            ><?php echo e(__("Edit")); ?></a>
                                        </td>
                                    </tr>
                                    <?php
                                    $traverse($row->children, $prefix . '-');
                                    }
                                    };
                                    $traverse($rows);
                                    ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4"><?php echo e(__("No data")); ?></td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/admin/topic/category/index.blade.php ENDPATH**/ ?>