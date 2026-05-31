
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('module/booking/css/checkout.css?_ver='.config('app.asset_version'))); ?>" rel="stylesheet">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <?php
        $translate = $plan->translate();
        if(request()->query('annual')!=1){
            $price = $plan->price;
            $duration_text = $plan->duration_type_text;
        }else{
            $price = $plan->annual_price;
            $duration_text = __('Year');
        }
            $term_conditions = setting_item('booking_term_conditions');

    ?>
    <section class="pricing-section bc-booking-page">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div class="sec-title text-center mb-5">
                        <h2><?php echo e(setting_item_with_lang('user_plans_page_title', app()->getLocale()) ?? __("Pricing Packages")); ?></h2>
                    </div>
                    <div class="pricing-tabs tabs-box">
                        <form method="post" action="<?php echo e(route('user.plan.buyProcess',['id'=>$plan->id])); ?>" class="row">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="annual" value="<?php echo e(request()->query('annual')); ?>">
                            <div class="pricing-table col-12">
                                <div class="inner-box">
                                    <div class="title"><?php echo e($translate->title); ?></div>
                                    <div class="price"><?php echo e(format_money($price)); ?>

                                        <?php if($price): ?>
                                            <span class="duration">/ <?php echo e($duration_text); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-content">
                                        <?php echo clean($translate->content); ?>

                                    </div>
                                </div>
                            </div>
                            <div class="form-section col-12">
                                <?php echo $__env->make('Booking::frontend.booking.checkout-payment', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                            <div class="row">
                                <div class="col-auto">
                                    <?php if(setting_item("booking_enable_recaptcha")): ?>
                                        <div class="form-group">
                                            <?php echo e(recaptcha_field('booking')); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row y-gap-20 items-center justify-between mb-40">
                                <div class="col-auto">
                                    <label class="d-flex items-center term-conditions-checkbox" id="term_conditions">
                                        <div class="form-checkbox ">
                                            <input type="checkbox" name="term_conditions" class="has-value">
                                            <div class="form-checkbox__mark">
                                                <div class="form-checkbox__icon icon-check"></div>
                                            </div>
                                        </div>
                                        <div class="text-14 lh-10 text-light-1 ml-10">
                                            <?php echo e(__('I have read and accept the')); ?>  <a target="_blank" class="text-blue-1" href="<?php echo e(get_page_url($term_conditions)); ?>"><?php echo e(__('terms and conditions')); ?></a>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="button h-60 px-24 -dark-1 bg-blue-1 text-white">
                                        <?php echo e(__('Submit')); ?>

                                        <div class="icon-arrow-top-right ml-15"></div>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('footer'); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/User/Views/frontend/plan/checkout.blade.php ENDPATH**/ ?>