<div class="pt-60 pb-60">
    <div class="row y-gap-40 justify-between xl:justify-start">
        <div class="col-xl-4 col-lg-6">
            <?php $footerContent = setting_item_with_lang('footer_content_left'); ?>

            <img src="<?php echo e(get_file_url(setting_item('logo_id'))); ?>" alt="image">
            <?php if(!empty($footerContent)): ?>
                <?php $__currentLoopData = json_decode($footerContent); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $content): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo clean($content->content); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <div class="row y-gap-30">
                <div class="col-12">
                    <h5 class="text-16 fw-500 mb-15"><?php echo e(__('Get Updates & More')); ?></h5>

                    <div class="pb-30 mailchimp">
                        <form action="<?php echo e(route('newsletter.subscribe')); ?>" class="subcribe-form bc-subscribe-form bc-form single-field ">
                            <?php echo csrf_field(); ?>
                           <div class='relative d-flex justify-end items-center'>
                                <input class="bg-white rounded-8 email-input" type="text" name="email" placeholder="<?php echo e(__('Your Email')); ?>">
                                <button class="button absolute px-20 h-full text-15 fw-500 text-dark-1 underline">
                                    <?php echo e(__('Subscribe')); ?> <i class="fa fa-spinner fa-pulse fa-fw"></i>
                                </button>
                            </div>
                                <?php if(setting_item("user_enable_subscribe_recaptcha")): ?>
                                <div class='mt-2'>
                                    <?php echo e(recaptcha_field( 'newsletter_subscribe')); ?>

                                </div>
                                <?php endif; ?>
                            <div class="d-flex justify-end ">
                                <div class="form-mess text-end"></div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if($list_widget_footers = setting_item_with_lang("footer_content_right")): ?>
                    <?php $list_widget_footers = json_decode($list_widget_footers); ?>
                    <?php $__currentLoopData = $list_widget_footers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-lg-4 col-sm-6">
                            <h5 class="text-16 fw-500 mb-30"><?php echo e($item->title); ?></h5>
                            <?php echo $item->content; ?>

                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/parts/footer-style/style_4.blade.php ENDPATH**/ ?>