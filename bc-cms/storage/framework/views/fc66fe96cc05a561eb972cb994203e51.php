<h4><?php echo e(__('Create request')); ?></h4>
<?php if($available_payout_amount): ?>
    <div class="total-amount-payable h4 text-primary">
        <strong><?php echo e(__("Balance: ")); ?></strong>
        <strong><?php echo e(format_money($available_payout_amount)); ?></strong>
    </div>
    <br>
    <div class="">
        <a href="#vendor_create_request" data-toggle="modal" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> <?php echo e(__("Create request")); ?></a>
    </div>
<?php else: ?>
    <div class="alert alert-warning"><?php echo e(__("Your balance is zero")); ?></div>
<?php endif; ?>

<div class="modal bc-form" tabindex="-1" role="dialog" id="vendor_create_request">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo e(__("Create payout request")); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body ">
                <form class="" novalidate onsubmit="return false" >
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?php echo e(__("Available for payout")); ?></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" readonly value="<?php echo e(format_money($available_payout_amount)); ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?php echo e(__("Amount")); ?> <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="number" required max="<?php echo e($available_payout_amount); ?>" class="form-control" name="amount" value="<?php echo e($available_payout_amount); ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?php echo e(__("Method")); ?> <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select required class="form-control" name="payout_method">
                                <option value=""><?php echo e(__('-- Please select --')); ?></option>
                                <?php $__currentLoopData = $currentUser->available_payout_methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($id); ?>"><?php echo e($method->name); ?> <?php if(!empty($method->min)): ?> (<?php echo e(__('Minimum: :amount',['amount'=>format_money($method->min)])); ?>) <?php endif; ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?php echo e(__("Note to admin")); ?></label>
                        <div class="col-sm-9">
                            <textarea name="note_to_admin" class="form-control" cols="30" rows="10" ></textarea>
                        </div>
                    </div>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('Close')); ?></button>
                <button type="button" class="btn btn-success " onclick="vendorPayout.sendRequest(this)"><?php echo e(__('Send request')); ?>

                    <i class="fa fa-spinner fa-spin fa-fw"></i>
                </button>
            </div>
        </div>
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/payouts/request.blade.php ENDPATH**/ ?>