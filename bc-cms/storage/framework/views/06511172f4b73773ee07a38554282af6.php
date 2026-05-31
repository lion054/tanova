

<?php $__env->startSection('content'); ?>
    <form action="<?php echo e(route('user.admin.verification.store',['id'=>$row->id])); ?>" method="post" class="needs-validation" novalidate>
        <?php echo csrf_field(); ?>
        <div class="container">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar"><?php echo e($row->id ? 'Verify Request: '.$row->getDisplayName() : 'Add new user'); ?></h1>
                </div>
            </div>
            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <div class="panel">
                        <div class="panel-title"><strong><?php echo e(__('Data')); ?></strong></div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo e(__('No')); ?></th>
                                            <th><?php echo e(__("Information")); ?></th>
                                            <th width="200px"><?php echo e(__("Mark as verified")); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php ($only_show_data = true); ?>
                                    <?php ($value_col_size = 9); ?>
                                    <?php ($i = 0); ?>
                                    <?php $__currentLoopData = $row->verification_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($i+1); ?></td>
                                            <td>
                                                <?php switch($field['type']):
                                                    case ("email"): ?>
                                                    <?php echo $__env->make('User::frontend.verification.fields.email', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                    <?php break; ?>
                                                    <?php case ("phone"): ?>
                                                    <?php echo $__env->make('User::frontend.verification.fields.phone', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                    <?php break; ?>
                                                    <?php case ("file"): ?>
                                                    <?php echo $__env->make('User::frontend.verification.fields.file', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                    <?php break; ?>
                                                    <?php case ("multi_files"): ?>
                                                    <?php echo $__env->make('User::frontend.verification.fields.multi_files', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                    <?php break; ?>
                                                    <?php case ('text'): ?>
                                                    <?php default: ?>
                                                    <?php echo $__env->make('User::frontend.verification.fields.text', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                    <?php break; ?>
                                                <?php endswitch; ?>
                                            </td>
                                            <td>
                                                <input <?php if($row->isVerifiedField($field['id'])): ?> checked <?php endif; ?> type="checkbox" name="fields[]" value="<?php echo e($field['id']); ?>" >
                                            </td>
                                        </tr>
                                        <?php ($i ++); ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <span></span>
                <button class="btn btn-primary" type="submit"><?php echo e(__('Save Change')); ?></button>
            </div>
        </div>
    </form>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/User/Views/admin/verification/detail.blade.php ENDPATH**/ ?>