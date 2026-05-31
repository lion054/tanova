<?php if ($row->status != 'open') return; ?>
<hr>
<div class="card">
    <div class="card-body">
        <form id="reply_form2" action="<?php echo e(route('support.ticket.reply_store',['id'=>$row->id])); ?>" method="post" class="">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <h6>Reply Content</h6>
                <textarea class="form-control message" id="reply_content2" rows="7" name="content" placeholder="Enter Your Text ..."></textarea>
                <div class="invalid-feedback" id="reply_content_invalid">
                    <?php echo e(__("Please provide ticket content")); ?>

                </div>
            </div>
            <?php echo \App\Helpers\ReCaptchaEngine::captcha('reply_store'); ?>

        </form>
    </div>
    <div class="card-footer">
        <button type="submit" id="reply_submit_btn2" class="btn btn-lg btn-info"><?php echo e(__("Add Reply")); ?></button>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/ticket/form-reply.blade.php ENDPATH**/ ?>