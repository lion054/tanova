<?php
$vendor_payout_methods = json_decode(setting_item('vendor_payout_methods'));
if(!is_array($vendor_payout_methods)) return;

$vendor_payout_methods = array_values(\Illuminate\Support\Arr::sort($vendor_payout_methods, function ($value) {
    return $value->order ?? 0;
}));

$payout_accounts = $currentUser->payout_accounts;
?>
<h4><?php echo e(__('Setup your payment accounts')); ?></h4>
<div class="">
    <a href="#vendor_payout_accounts" data-toggle="modal" class="btn btn-primary btn-sm"><?php echo e(__("Setup accounts")); ?></a>
</div>
<br>
<p><i><?php echo e(__("To create payout request, please setup your payment account first")); ?></i></p>

<div class="modal bc-form" tabindex="-1" role="dialog" id="vendor_payout_accounts">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo e(__("Setup payout accounts")); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo e(__("Method")); ?></th>
                            <th><?php echo e(__("Your account")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $vendor_payout_methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php ($method_id = $method->id); ?>
                            <tr>
                                <td>#<?php echo e($k+1); ?></td>
                                <td>
                                    <span class="method-name"><strong><?php echo e($method->name); ?></strong></span>
                                    <div class="method-desc"><?php echo clean($method->desc); ?></div>
                                </td>
                                <td>
                                    <textarea name="payout_accounts[<?php echo e($method->id); ?>]" class="form-control" cols="30" rows="3" placeholder="<?php echo e(__("Your account info")); ?>"><?php echo e($payout_accounts->$method_id ?? ''); ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <div class="message_box alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('Close')); ?></button>
                <button type="button" class="btn btn-success " onclick="vendorPayout.saveAccounts(this)"><?php echo e(__('Save changes')); ?>

                    <i class="fa fa-spinner"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Vendor/Views/frontend/payouts/setup.blade.php ENDPATH**/ ?>