<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e(__("Assign / Renew Vendor Plan")); ?></h1>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="panel">
                <div class="panel-title"><strong><?php echo e(__('Subscription Details')); ?></strong></div>
                <div class="panel-body">
                    <form action="<?php echo e(route('vendor.admin.subscription.doAssign')); ?>" method="post">
                        <?php echo csrf_field(); ?>

                        <div class="form-group">
                            <label><?php echo e(__('Vendor')); ?> <span class="text-danger">*</span></label>
                            <?php
                            \App\Helpers\AdminForm::select2('vendor_id', [
                                'configs' => [
                                    'ajax'        => ['url' => route('user.admin.getForSelect2'), 'dataType' => 'json'],
                                    'allowClear'  => false,
                                    'placeholder' => __('Search vendor by name or email...'),
                                ]
                            ], !empty($vendor) ? [$vendor->id, $vendor->getDisplayName() . ' (#' . $vendor->id . ')'] : false);
                            ?>
                            <?php $__errorArgs = ['vendor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-danger"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label><?php echo e(__('Plan')); ?> <span class="text-danger">*</span></label>
                            <select name="plan_id" class="form-control" id="planSelect" required>
                                <option value=""><?php echo e(__('-- Select Plan --')); ?></option>
                                <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($plan->id); ?>"
                                        data-price-monthly="<?php echo e($plan->price); ?>"
                                        data-price-annual="<?php echo e($plan->price_annual); ?>"
                                        data-commission="<?php echo e($plan->base_commission); ?>"
                                        <?php echo e(old('plan_id') == $plan->id ? 'selected' : ''); ?>>
                                        <?php echo e($plan->name); ?> — <?php echo e(format_money($plan->price)); ?>/mo
                                        <?php if($plan->price_annual): ?> (<?php echo e(format_money($plan->price_annual)); ?>/yr) <?php endif; ?>
                                        — <?php echo e($plan->base_commission); ?>% commission
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['plan_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-danger"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label><?php echo e(__('Billing Cycle')); ?> <span class="text-danger">*</span></label>
                            <div>
                                <label class="mr-4">
                                    <input type="radio" name="billing_cycle" value="monthly"
                                        <?php echo e(old('billing_cycle','monthly') === 'monthly' ? 'checked' : ''); ?>

                                        onchange="updatePrice()">
                                    <?php echo e(__('Monthly')); ?>

                                </label>
                                <label>
                                    <input type="radio" name="billing_cycle" value="yearly"
                                        <?php echo e(old('billing_cycle') === 'yearly' ? 'checked' : ''); ?>

                                        onchange="updatePrice()">
                                    <?php echo e(__('Yearly')); ?>

                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo e(__('Start Date')); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="starts_at" class="form-control"
                                value="<?php echo e(old('starts_at', now()->toDateString())); ?>" required>
                            <?php $__errorArgs = ['starts_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-danger"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label><?php echo e(__('Amount Charged')); ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><?php echo e(setting_item('currency_symbol','$')); ?></span>
                                </div>
                                <input type="number" name="amount_paid" id="amountInput" class="form-control"
                                    value="<?php echo e(old('amount_paid', 0)); ?>" min="0" step="0.01" required>
                            </div>
                            <small class="text-muted" id="priceHint"></small>
                            <?php $__errorArgs = ['amount_paid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-danger"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="form-group">
                            <label><?php echo e(__('Internal Notes')); ?> <small class="text-muted">(<?php echo e(__('not shown to vendor')); ?>)</small></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="<?php echo e(__('e.g. Payment received via bank transfer ref #12345')); ?>"><?php echo e(old('notes')); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo e(route('vendor.admin.subscription.index')); ?>" class="btn btn-secondary"><?php echo e(__('Cancel')); ?></a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check"></i> <?php echo e(__('Assign Plan & Activate')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
<script>
    var planData = <?php echo json_encode($plans->keyBy('id')->map(fn($p) => ['monthly' => $p->price, 'yearly' => $p->price_annual]), 512) ?>;

    function updatePrice() {
        var planId = document.getElementById('planSelect').value;
        var cycle  = document.querySelector('input[name=billing_cycle]:checked')?.value ?? 'monthly';
        var hint   = document.getElementById('priceHint');
        var input  = document.getElementById('amountInput');

        if (!planId || !planData[planId]) { hint.textContent = ''; return; }

        var price = cycle === 'yearly' ? planData[planId].yearly : planData[planId].monthly;
        if (!price && cycle === 'yearly') {
            hint.textContent = '<?php echo e(__("No annual price set for this plan — using monthly.")); ?>';
            price = planData[planId].monthly;
        } else {
            hint.textContent = '';
        }
        if (price !== null && price !== undefined) {
            input.value = parseFloat(price).toFixed(2);
        }
    }

    document.getElementById('planSelect').addEventListener('change', updatePrice);
    document.querySelectorAll('input[name=billing_cycle]').forEach(el => el.addEventListener('change', updatePrice));
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/subscriptions/assign.blade.php ENDPATH**/ ?>