<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e(!empty($row->id) ? __("Edit Plan: :name", ['name' => $row->name]) : __("Create Plan")); ?></h1>
    </div>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <form action="" method="post">
        <?php echo csrf_field(); ?>
        <div class="row">
            
            <div class="col-md-8">

                
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Plan Details')); ?></strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label><?php echo e(__('Plan Name')); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $row->name)); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo e(__('Base Commission Rate (%)')); ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="base_commission" class="form-control"
                                    value="<?php echo e(old('base_commission', $row->base_commission ?? 10)); ?>"
                                    min="0" max="100" step="1" required>
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                            <small class="form-text text-muted"><?php echo e(__('Platform commission on each booking for vendors on this plan. Lower rate = more attractive plan.')); ?></small>
                        </div>
                    </div>
                </div>

                
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Subscription Pricing')); ?></strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo e(__('Monthly Price')); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><?php echo e(setting_item('currency_symbol','$')); ?></span></div>
                                        <input type="number" name="price" class="form-control"
                                            value="<?php echo e(old('price', $row->price ?? 0)); ?>"
                                            min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo e(__('Annual Price')); ?> <small class="text-muted"><?php echo e(__('(optional — leave blank to disable annual billing)')); ?></small></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><?php echo e(setting_item('currency_symbol','$')); ?></span></div>
                                        <input type="number" name="price_annual" class="form-control"
                                            value="<?php echo e(old('price_annual', $row->price_annual)); ?>"
                                            min="0" step="0.01">
                                    </div>
                                    <?php if(!empty($row->price) && !empty($row->price_annual)): ?>
                                        <?php $saving = round((1 - $row->price_annual / ($row->price * 12)) * 100) ?>
                                        <small class="text-success"><?php echo e(__(':p% saving vs monthly', ['p' => $saving])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Service Permissions')); ?></strong></div>
                    <div class="panel-body">
                        <p class="text-muted"><?php echo e(__('Control which service types vendors on this plan can create, and set per-service commission overrides.')); ?></p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th><?php echo e(__('Service')); ?></th>
                                    <th width="80px"><?php echo e(__('Enable')); ?></th>
                                    <th width="130px"><?php echo e(__('Max Listings')); ?></th>
                                    <th width="100px"><?php echo e(__('Auto-Publish')); ?></th>
                                    <th width="120px"><?php echo e(__('Commission %')); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $meta = !empty($row->id) ? $row->meta->firstWhere('post_type', $type) : null; ?>
                                    <tr>
                                        <td><strong><?php echo e($label); ?></strong>
                                            <input type="hidden" name="services_options[<?php echo e($type); ?>][post_type]" value="<?php echo e($type); ?>">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="services_options[<?php echo e($type); ?>][enable]" value="1"
                                                <?php echo e(!empty($meta->enable) ? 'checked' : ''); ?>>
                                        </td>
                                        <td>
                                            <input type="number" name="services_options[<?php echo e($type); ?>][maximum_create]"
                                                class="form-control form-control-sm"
                                                value="<?php echo e($meta->maximum_create ?? ''); ?>"
                                                placeholder="<?php echo e(__('Unlimited')); ?>" min="0">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="services_options[<?php echo e($type); ?>][auto_publish]" value="1"
                                                <?php echo e(!empty($meta->auto_publish) ? 'checked' : ''); ?>>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="services_options[<?php echo e($type); ?>][commission]"
                                                    class="form-control"
                                                    value="<?php echo e($meta->commission ?? ''); ?>"
                                                    placeholder="<?php echo e(__('Use base')); ?>" min="0" max="100" step="1">
                                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted"><?php echo e(__('Max Listings: leave blank for unlimited. Commission: leave blank to use the base commission rate above.')); ?></small>
                    </div>
                </div>
            </div>

            
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Publish')); ?></strong></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label><?php echo e(__('Status')); ?></label>
                            <select name="status" class="form-control">
                                <option value="publish" <?php echo e(old('status', $row->status) == 'publish' ? 'selected' : ''); ?>><?php echo e(__('Published')); ?></option>
                                <option value="draft"   <?php echo e(old('status', $row->status) == 'draft'   ? 'selected' : ''); ?>><?php echo e(__('Draft')); ?></option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa fa-save"></i> <?php echo e(!empty($row->id) ? __('Update Plan') : __('Create Plan')); ?>

                        </button>
                        <?php if(!empty($row->id)): ?>
                        <a href="<?php echo e(route('vendor.admin.plan.index')); ?>" class="btn btn-secondary btn-block mt-2">
                            <?php echo e(__('Cancel')); ?>

                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(!empty($row->id)): ?>
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__('Vendors on this Plan')); ?></strong></div>
                    <div class="panel-body">
                        <?php $vendorCount = \App\User::where('vendor_plan_id', $row->id)->count(); ?>
                        <p>
                            <strong><?php echo e($vendorCount); ?></strong> <?php echo e(__('active vendor(s)')); ?>

                        </p>
                        <a href="<?php echo e(route('vendor.admin.subscription.index')); ?>?plan_id=<?php echo e($row->id); ?>" class="btn btn-sm btn-outline-info">
                            <?php echo e(__('View Subscriptions')); ?>

                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/admin/plan/detail.blade.php ENDPATH**/ ?>