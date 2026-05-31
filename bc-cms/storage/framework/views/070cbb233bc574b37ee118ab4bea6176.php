
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('Topic Tags')); ?> </h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-title"><?php echo e(__('Add Tag')); ?></div>
                    <div class="panel-body">
                        <form action="<?php echo e(route('support.admin.topic.tag.store',['id'=>-1])); ?>" method="post">
                            <?php echo csrf_field(); ?>
                            <?php echo $__env->make('Support::admin/topic/tag/form',['parents'=>$rows], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
                                method="post"
                                action="<?php echo e(route('support.admin.topic.tag.bulkEdit')); ?>"
                                class="filter-form filter-form-left d-flex justify-content-start"
                            >
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
                            action="<?php echo e(route('support.admin.topic.tag.index')); ?> "
                            class="filter-form filter-form-right d-flex justify-content-end"
                            role="search"
                        >
                            <?php echo csrf_field(); ?>
                            <input placeholder="<?php echo e(__("Search keyword ...")); ?>" type="text" name="s" value="<?php echo e(Request()->s); ?>" class="form-control">
                            <button class="btn-info btn btn-icon btn_search" id="search-submit" type="submit"><?php echo e(__('Search Tag')); ?></button>
                        </form>
                    </div>
                </div>
                <div class="text-right">
                    <p><i><?php echo e(__('Found :total items',['total'=>$rows->total()])); ?></i></p>
                </div>
                <div class="panel">
                    <form action="" class="bc-form-item">
                        <div class="panel-body">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th width="60px"><input type="checkbox" class="check-all"></th>
                                    <th><?php echo e(__('Name')); ?></th>
                                    <th><?php echo e(__('Slug')); ?></th>
                                    <th><?php echo e(__('Date')); ?></th>
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
                                                <a href="<?php echo e(route('support.admin.topic.tag.edit',['id'=>$row->id])); ?>"><?php echo e($row->name); ?></a>
                                            </td>
                                            <td><?php echo e($row->slug); ?></td>
                                            <td><?php echo e(display_date($row->updated_at)); ?></td>
                                            <td>
                                                <a
                                                    class="btn btn-sm btn-default"
                                                    href="<?php echo e(route('support.admin.topic.tag.edit',['id'=>$row->id])); ?>"
                                                ><?php echo e(__("Edit")); ?></a>
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
                </div>
                <?php echo e($rows->appends(request()->query())->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/admin/topic/tag/index.blade.php ENDPATH**/ ?>