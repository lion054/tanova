
<?php $__env->startSection('content'); ?>
    <div class="b-container">
        <div class="b-panel">
            <?php if($to == 'vendor'): ?>
                <h1><?php echo e(__("Hello :name",['name'=>$user->getDisplayName()])); ?></h1>

                <?php if($action == 'insert'): ?>
                    <p><?php echo e(__('Your payout request has been submitted:')); ?></p>
                <?php elseif($action ==  'update'): ?>
                    <p><?php echo e(__('Your payout request has been updated:')); ?></p>
                <?php elseif($action ==  'reject'): ?>
                    <p><?php echo e(__('Your payout request has been rejected:')); ?></p>
                <?php elseif($action ==  'delete'): ?>
                    <p><?php echo e(__('Your payout request has been deleted')); ?></p>
                <?php endif; ?>

                <?php if(!in_array($action,['insert','delete'])): ?>
                    <ul>
                        <li><strong><?php echo e(__('Status:')); ?></strong> <strong><?php echo e($payout_request->status_text); ?></strong></li>
                        <?php if($payout_request->pay_date): ?>
                            <li><strong><?php echo e(__('Pay date:')); ?></strong> <strong><?php echo e(display_date($payout_request->pay_date)); ?></strong></li>
                        <?php endif; ?>
                        <?php if($payout_request->note_to_vendor): ?>
                            <li><strong><?php echo e(__('Note to vendor:')); ?></strong> <strong><?php echo clean($payout_request->note_to_vendor); ?></strong></li>
                        <?php endif; ?>
                    </ul>
                    <p><?php echo e(__('Payout information:')); ?></p>
                <?php endif; ?>

                <ul>
                    <li><strong><?php echo e(__("Payout ID:")); ?></strong> <strong>#<?php echo e($payout_request->id); ?></strong></li>
                    <li><strong><?php echo e(__('Amount: ')); ?></strong> <strong><?php echo e(format_money($payout_request->amount)); ?></strong></li>
                    <li><strong><?php echo e(__('Payout method: ')); ?></strong>
                        <?php echo e(__(':name to :info',['name'=>$payout_request->payout_method_name,'info'=>$payout_request->account_info])); ?>

                    </li>
                    <?php if($payout_request->note_to_admin): ?>
                    <li><strong><?php echo e(__('Note to admin: ')); ?></strong>
                        <?php echo e($payout_request->note_to_admin); ?>

                    </li>
                    <?php endif; ?>
                    <li><strong><?php echo e(__('Created at: ')); ?></strong>
                        <?php echo e(display_datetime($payout_request->created_at)); ?>

                    </li>

                </ul>
                <p><?php echo e(__('You can check your payout request here:')); ?> <a class="btn btn-primary" target="_blank" href="<?php echo e(route('vendor.payout.index')); ?>"><?php echo e(__('View payouts')); ?></a></p>

                <br>
            <?php else: ?>
                <h1><?php echo e(__("Hello administrator")); ?></h1>

                <?php if($action == 'insert'): ?>
                    <p><?php echo e(__('A vendor has been submitted a payout request:')); ?></p>
                <?php elseif($action ==  'update'): ?>
                    <p><?php echo e(__('A payout request has been updated:')); ?></p>
                <?php elseif($action ==  'reject'): ?>
                    <p><?php echo e(__('A payout request has been rejected:')); ?></p>
                <?php elseif($action ==  'delete'): ?>
                    <p><?php echo e(__('A payout request has been deleted')); ?></p>
                <?php endif; ?>

                <?php if(!in_array($action,['insert','delete'])): ?>
                    <ul>
                        <li><strong><?php echo e(__('Status:')); ?></strong> <strong><?php echo e($payout_request->status_text); ?></strong></li>
                        <?php if($payout_request->pay_date): ?>
                            <li><strong><?php echo e(__('Pay date:')); ?></strong> <strong><?php echo e(display_date($payout_request->pay_date)); ?></strong></li>
                        <?php endif; ?>
                        <?php if($payout_request->note_to_vendor): ?>
                        <li><strong><?php echo e(__('Note to vendor:')); ?></strong> <strong><?php echo clean($payout_request->note_to_vendor); ?></strong></li>
                        <?php endif; ?>
                    </ul>
                    <p><?php echo e(__('Payout information:')); ?></p>
                <?php endif; ?>

                <ul>
                    <li><strong><?php echo e(__("Payout ID:")); ?></strong> <strong>#<?php echo e($payout_request->id); ?></strong></li>
                    <li><strong><?php echo e(__('Vendor: ')); ?></strong> <strong><a target="_blank" href="<?php echo e(route('user.profile',['id'=>$payout_request->vendor_id])); ?>"><?php echo e($payout_request->vendor->getDisplayName()); ?></a></strong></li>
                    <li><strong><?php echo e(__('Amount: ')); ?></strong> <strong><?php echo e(format_money($payout_request->amount)); ?></strong></li>
                    <li><strong><?php echo e(__('Payout method: ')); ?></strong>
                        <?php echo e(__(':name to :info',['name'=>$payout_request->payout_method_name,'info'=>$payout_request->account_info])); ?>

                    </li>
                    <?php if($payout_request->note_to_admin): ?>
                    <li><strong><?php echo e(__('Note to admin: ')); ?></strong>
                        <?php echo e($payout_request->note_to_admin); ?>

                    </li>
                    <?php endif; ?>
                    <li><strong><?php echo e(__('Created at: ')); ?></strong>
                        <?php echo e(display_datetime($payout_request->created_at)); ?>

                    </li>

                </ul>
                <p><?php echo e(__('You can check all payout request here:')); ?> <a class="btn btn-primary" target="_blank" href="<?php echo e(route('vendor.admin.payout.index')); ?>"><?php echo e(__('Manage payouts')); ?></a></p>
                <br>
            <?php endif; ?>
            <p><?php echo e(__('Regards')); ?>,<br><?php echo e(setting_item('site_title')); ?></p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Email::layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/emails/payout-request-email.blade.php ENDPATH**/ ?>