
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('All Orders')); ?></h1>
        </div>
        <?php echo $__env->make('Layout::admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="filter-div d-flex justify-content-between">
            <div class="col-left">
                <form method="post" action="<?php echo e(route('order.admin.bulkEdit')); ?>"
                    class="filter-form filter-form-left d-flex justify-content-start">
                    <?php echo csrf_field(); ?>
                    <select name="action" class="form-control">
                        <option value=""><?php echo e(__('-- Bulk Actions --')); ?></option>
                        <?php if(!empty($statues)): ?>
                            <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>"><?php echo e(__('Mark as: :name', ['name' => ucfirst($status)])); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                        <option value="delete"><?php echo e(__('DELETE orders')); ?></option>
                    </select>
                    <button data-confirm="<?php echo e(__('Do you want to delete?')); ?>"
                        class="btn-default btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                </form>
            </div>
            <div class="col-left">
                <form method="get" action="" class="filter-form filter-form-right d-flex justify-content-end">
                    <?php if(!empty($booking_manage_others)): ?>
                        <?php
                        $user = !empty(Request()->vendor_id) ? App\User::find(Request()->vendor_id) : false;
                        \App\Helpers\AdminForm::select2(
                            'vendor_id',
                            [
                                'configs' => [
                                    'ajax' => [
                                        'url' => url('/admin/module/user/getForSelect2'),
                                        'dataType' => 'json',
                                    ],
                                    'allowClear' => true,
                                    'placeholder' => __('-- Vendor --'),
                                ],
                            ],
                            !empty($user->id) ? [$user->id, $user->name_or_email . ' (#' . $user->id . ')'] : false,
                        );
                        ?>
                    <?php endif; ?>
                    <input type="text" name="s" value="<?php echo e(Request()->s); ?>"
                        placeholder="<?php echo e(__('Search by name or ID')); ?>" class="form-control">
                    <button class="btn-default btn btn-icon" type="submit"><?php echo e(__('Filter')); ?></button>
                </form>
            </div>
        </div>
        <div class="text-right">
            <p><i><?php echo e(__('Found :total items', ['total' => $rows->total()])); ?></i></p>
        </div>
        <div class="panel booking-history-manager">
            <div class="panel-title"><?php echo e(__('Orders')); ?></div>
            <div class="panel-body">
                <form action="" class="bc-form-item bc-form-item">
                    <table class="table table-hover bc-list-item">
                        <thead>
                            <tr>
                                <th width="80px"><input type="checkbox" class="check-all"></th>
                                <th><?php echo e(__('Customer')); ?></th>
                                <th><?php echo e(__('Total')); ?></th>
                                <th width="80px"><?php echo e(__('Status')); ?></th>
                                <th width="150px"><?php echo e(__('Payment Method')); ?></th>
                                <th width="120px"><?php echo e(__('Created At')); ?></th>
                                <th width="80px"><?php echo e(__('Actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td width="6%">
                                        <input type="checkbox" class="check-item" name="ids[]"
                                            value="<?php echo e($row->id); ?>">#<?php echo e($row->id); ?>

                                    </td>
                                    <td>
                                        <?php
                                            $billing = $row->getJsonMeta('billing');
                                            $note = $row->getMeta('note');
                                        ?>
                                        <?php if(!empty($billing)): ?>
                                            <ul>
                                                <li> <?php echo e(__('Full Name:')); ?> <?php echo e($billing['first_name'] ?? ''); ?> <?php echo e($billing['last_name'] ?? ''); ?> </li>
                                                <li> <?php echo e(__('Email:')); ?> <?php echo e($row->email); ?></li>
                                                <li> <?php echo e(__('Phone:')); ?> <?php echo e($billing['phone'] ?? ''); ?></li>
                                                <li> <?php echo e(__('Address:')); ?> <?php echo e($billing['address'] ?? ''); ?></li>

                                                <?php if($note): ?>
                                                    <li> <?php echo e(__('Note:')); ?> <?php echo e($note); ?></li>
                                                <?php endif; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e(format_money($row->total)); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo e($row->status_badge); ?>"><?php echo e($row->status_text); ?></span>
                                    </td>
                                    <td>
                                        <?php echo e($row->gatewayObj ? $row->gatewayObj->getDisplayName() : ''); ?>

                                    </td>
                                    <td><?php echo e(display_datetime($row->order_date ?: $row->created_at)); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-default dropdown-toggle btn-sm" type="button"
                                                id="dropdownMenuButton" data-toggle="dropdown" aria-expanded="false">
                                                <?php echo e(__('Actions')); ?>

                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" data-toggle="modal" data-target="#modal-order"
                                                    data-id="<?php echo e($row->id); ?>"
                                                    data-ajax="<?php echo e(route('order.modal', ['code' => $row->code])); ?>"
                                                    type="button"><i class="fa fa-eye"></i> <?php echo e(__('Detail')); ?></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <?php echo e($rows->withQueryString()->links()); ?>

        </div>
    </div>
    <div class="modal" tabindex="-1" id="modal-order">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo e(__('Order ID: #')); ?> <span class="order_id"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center"><?php echo e(__('Loading...')); ?></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('Close')); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script>
        $('#modal-order').on('show.bs.modal', function(e) {
            console.log(e)
            var btn = $(e.relatedTarget);
            $(this).find('.order_id').html(btn.data('id'));
            $(this).find('.modal-body').html(
                '<div class="d-flex justify-content-center"><?php echo e(__('Loading...')); ?></div>');
            var modal = $(this);
            $.get(btn.data('ajax'), function(html) {
                modal.find('.modal-body').html(html);
            })
        })
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/admin/order/index.blade.php ENDPATH**/ ?>