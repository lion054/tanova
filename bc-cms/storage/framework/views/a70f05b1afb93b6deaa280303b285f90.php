
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__("Visa Types")); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row">
            <div class="col-md-4 mb40">
                <div class="panel">
                    <div class="panel-title"><?php echo e(__("Add Visa Type")); ?></div>
                    <div class="panel-body">
                        <form wire:submit.prevent="store" method="post">
                            <?php echo $__env->make('Visa::admin.type.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <div class="">
                                <button class="btn btn-primary" type="submit"><?php echo e(__("Add new")); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="filter-div d-flex justify-content-between ">
                    <div class="col-left">
                        <?php if(!empty($rows)): ?>
                            <form wire:submit.prevent="bulkEdit" class="filter-form filter-form-left d-flex justify-content-start">
                                <select wire:model="action" class="form-control">
                                    <option value=""><?php echo e(__(" Bulk Action ")); ?></option>
                                    <option value="publish"><?php echo e(__(" Publish ")); ?></option>
                                    <option value="draft"><?php echo e(__(" Move to Draft ")); ?></option>
                                    <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                                </select>
                                <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon" type="submit"><?php echo e(__('Apply')); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="col-left">
                        <div class="filter-form filter-form-right d-flex justify-content-end" role="search">
                            <input type="text" wire:model="s" value="<?php echo e(Request()->s); ?>" class="form-control" placeholder="<?php echo e(__("Search by name")); ?>">
                            <button wire:click="$refresh" class="btn-info btn btn-icon btn_search" id="search-submit" type="submit"><?php echo e(__('Search')); ?></button>
                        </div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-body">
                        <div class="bc-form-item">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th width="60px"><input type="checkbox" class="check-all"></th>
                                    <th><?php echo e(__("Name")); ?></th>
                                    <th class="status"><?php echo e(__("Status")); ?></th>
                                    <th class="date "><?php echo e(__("Date")); ?></th>
                                    <th><?php echo e(__("Actions")); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if($rows->total() > 0): ?>
                                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><input type="checkbox" wire:model="ids" name="ids[]" value="<?php echo e($row->id); ?>" class="check-item">
                                        <td class="title">
                                            <a wire:navigate href="<?php echo e(route('visa.admin.type.edit',['id'=>$row->id])); ?>"><?php echo e($row->name); ?></a>
                                        </td>
                                        <td><span class="badge badge-<?php echo e($row->status); ?>"><?php echo e($row->status); ?></span></td>
                                        <td><?php echo e(display_date($row->updated_at)); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a wire:navigate href="<?php echo e(route('visa.admin.type.edit',['id'=>$row->id])); ?>" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5"><?php echo e(__("No data")); ?></td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                            <?php echo e($rows->links()); ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/admin/type/index.blade.php ENDPATH**/ ?>