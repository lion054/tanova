
<?php $__env->startSection('head'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <section class="pricing-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="sec-title text-center">
                        <h2><?php echo e(setting_item_with_lang('user_plans_page_title', app()->getLocale()) ?? __("Pricing Packages")); ?></h2>
                        <div class="my-3">
                            <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="col-auto">
                                <a class="button -md -blue-1 bg-dark-3 text-white" href="<?php echo e(route('user.plan')); ?>"><?php echo e(__('My plan')); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/plan/thankyou.blade.php ENDPATH**/ ?>