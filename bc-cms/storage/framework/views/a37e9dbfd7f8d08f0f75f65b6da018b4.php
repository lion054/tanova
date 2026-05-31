
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e($order->id ? __("Edit Order: #:order_id",['order_id'=>$order->id]) : __("Create new order")); ?></h1>
        </div>
        <?php echo $__env->make('Layout::admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="row" id="bc_order_form" v-cloak>
            <div class="col-md-9">
                <?php echo $__env->make('Order::admin.order.detail.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('Order::admin.order.detail.items', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <div class="col-md-3">
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__("Publish")); ?></strong></div>
                    <div class="panel-body">
                        <?php if($order->gateway): ?>
                            <h6><?php echo e(__("Payment via: :name",['name'=>$order->gateway_name])); ?></h6>
                            <?php if($order->pay_date): ?>
                                <h6><?php echo e(__("Paid on: :time",['time'=>display_datetime($order->pay_date)])); ?></h6>
                            <?php endif; ?>
                            <hr>
                        <?php endif; ?>
                        <div class="form-group">
                            <label ><?php echo e(__("Status")); ?></label>
                            <select v-model="status" class="form-select form-control">
                                <?php $__currentLoopData = $statues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status_id=>$text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($status_id); ?>"><?php echo e($text); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label ><?php echo e(__("Order date")); ?></label>
                            <bc-datepicker placeholder="<?php echo e(__("Please select")); ?>" v-model="order_date" :settings="created_at_settings"></bc-datepicker>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button class="btn btn-success" @click="save"><i class="fa fa-save"></i> <?php echo e(__("Save changes")); ?>

                            <i v-show="saving" class="fa fa-spinner fa-pulse fa-fw"></i>
                        </button>
                        <div class="mt-3" v-show="message.content" v-bind:class="!message.success ? 'text-danger' : 'text-success'" v-html="message.content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('script.body'); ?>
    <?php echo $__env->make('Layout::admin.components.datepicker', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('Layout::admin.components.select2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('Order::admin.order.detail.components.modal-address', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('Order::admin.order.detail.components.item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script>
        BC.routes.customer = {
            getForSelect2:"<?php echo e(route('customer.admin.getForSelect2',['need_address'=>1])); ?>"
        };
        BC.routes.product = {
            getForSelect2: "<?php echo route('product.admin.getForSelect2',['need_variations'=>1,'select2'=>1]); ?>"
        }
        BC.routes.order = {
            store:'<?php echo route('order.admin.store',['order'=>$order]); ?>'
        }
        var bc_order = <?php echo json_encode(new \Modules\Order\Resources\Admin\OrderResource($order,['items','shipping_methods','tax_lists','price'])); ?>

        var bc_country_list = <?php echo json_encode(get_country_lists()); ?>

    </script>
    <script src="<?php echo e(asset('module/order/admin/detail.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/admin/order/detail.blade.php ENDPATH**/ ?>