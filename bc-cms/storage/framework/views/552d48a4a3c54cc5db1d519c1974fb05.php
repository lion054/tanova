

<?php $__env->startSection('content'); ?>

    <h2 class="title-bar">
        <?php echo e(__("Vendor Teams")); ?>

    </h2>
    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <p><?php echo e(__('As an author, you can add other users to your team. People on your team will be able to manage your services.')); ?></p>
    <hr>
    <form method="post" action="<?php echo e(route('vendor.team.add')); ?>">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <label class="font-weight-bold"><?php echo e(__("Add someone to your team:")); ?></label>
                <input type="email" value="<?php echo e(old('email')); ?>" name="email" required class="form-control" placeholder="<?php echo e(__("Email address")); ?>" aria-label="<?php echo e(__("Email address")); ?>" aria-describedby="button-addon2">
            </div>
            <div class="col-md-3">
                <label class="font-weight-bold"><?php echo e(__("Permissions")); ?></label>
                <?php $__currentLoopData = get_bookable_services(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_id=>$service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><label ><input <?php if(in_array($service_id,old('permissions',[]))): ?> checked <?php endif; ?> type="checkbox" name="permissions[]" value="<?php echo e($service_id); ?>"><?php echo e($service::getModelName()); ?></label></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <button class="btn btn-success"><i class="fa fa-plus"></i> <?php echo e(__("Add")); ?></button>
    </form>

    <hr>
    <h4><?php echo e(__("Users on your team")); ?></h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-booking-history">
            <thead>
            <tr>
                <th width="2%"><?php echo e(__("#")); ?></th>
                <th><?php echo e(__("Display Name")); ?></th>
                <th><?php echo e(__("Email")); ?></th>
                <th><?php echo e(__("Permissions")); ?></th>
                <th><?php echo e(__("Status")); ?></th>
                <th><?php echo e(__("Actions")); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendorTeam): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td>#<?php echo e($vendorTeam->member->id ?? ''); ?></td>
                    <th><?php echo e($vendorTeam->member->display_name ?? ''); ?></th>
                    <td>
                        <?php echo e($vendorTeam->member->email?? ''); ?>

                    </td>
                    <td><?php echo e(implode(', ',$vendorTeam->permissions)); ?></td>
                    <td><span class="badge badge-<?php echo e($vendorTeam->status_badge); ?>"><?php echo e($vendorTeam->status_text); ?></span></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                <?php echo e(__("Actions")); ?>

                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo e(route('vendor.team.edit',['vendorTeam'=>$vendorTeam])); ?>"><?php echo e(__("Edit")); ?></a>
                                <?php if($vendorTeam->status == Modules\Vendor\Models\VendorTeam::STATUS_PENDING): ?>
                                    <a class="dropdown-item" href="<?php echo e(route('vendor.team.re-send-request',['vendorTeam'=>$vendorTeam])); ?>"><?php echo e(__("Send email")); ?></a>
                                <?php endif; ?>
                                <a class="dropdown-item" href="<?php echo e(URL::signedRoute('vendor.team.delete',['vendorTeam'=>$vendorTeam->id])); ?>"><?php echo e(__("Delete")); ?></a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Vendor/Views/frontend/team/index.blade.php ENDPATH**/ ?>