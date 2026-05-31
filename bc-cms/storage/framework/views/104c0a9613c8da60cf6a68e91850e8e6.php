
<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(asset('module/booking/css/checkout.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <div class="bc-booking-page padding-content pt-40 pb-40">
        <div class="container">
            <div id="bc-checkout-page">
                <div class="row">
                    <div class="col-xl-7 col-lg-8">
                        <?php if(!auth()->user()): ?>
                            <div class="py-15 px-20 mb-40 md:md-24 rounded-4 text-15 bg-blue-1-05">
                                <a data-bs-toggle="modal" href="#login" class="text-blue-1 fw-500"><?php echo e(__('Sign in')); ?></a>
                                <?php echo e(__('to book with your saved details or')); ?>

                                <a data-bs-toggle="modal" href="#register"
                                    class="text-blue-1 fw-500"><?php echo e(__('register')); ?></a>
                                <?php echo e(__('to manage your bookings on the go!')); ?>

                            </div>
                        <?php endif; ?>
                        <h2 class="text-22 fw-500"><?php echo e(__('Let us know who you are')); ?></h2>
                        <div class="booking-form ">
                            <?php echo $__env->make(
                                $service->checkout_form_file ?? 'Booking::frontend/booking/checkout-form',
                                ['disable_lazyload' => 1]
                            , array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php if(!empty(($token = request()->input('token')))): ?>
                                <input type="hidden" name="token" value="<?php echo e($token); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-xl-5 col-lg-4">
                        <div class="ml-80 lg:ml-40 md:ml-0">
                            <?php echo $__env->make($service->checkout_booking_detail_file ?? '', [
                                'disable_lazyload' => 1,
                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script src="<?php echo e(asset('module/booking/js/checkout.js')); ?>"></script>
    <script type="text/javascript">
        jQuery(function() {
            $.ajax({
                'url': bookingCore.url +
                    '<?php echo e($is_api ? '/api' : ''); ?>/booking/<?php echo e($booking->code); ?>/check-status',
                'cache': false,
                'type': 'GET',
                success: function(data) {
                    if (data.redirect !== undefined && data.redirect) {
                        window.location.href = data.redirect
                    }
                }
            });
        })

        $('.deposit_amount').on('change', function() {
            checkPaynow();
        });

        $('input[type=radio][name=how_to_pay]').on('change', function() {
            checkPaynow();
        });

        function checkPaynow() {
            var credit_input = $('.deposit_amount').val();
            var how_to_pay = $("input[name=how_to_pay]:checked").val();
            var convert_to_money = credit_input * <?php echo e(setting_item('wallet_credit_exchange_rate', 1)); ?>;

            if (how_to_pay == 'full') {
                var pay_now_need_pay = parseFloat(<?php echo e(floatval($booking->total)); ?>) - convert_to_money;
            } else {
                var pay_now_need_pay = parseFloat(
                    <?php echo e(floatval($booking->deposit == null ? $booking->total : $booking->deposit)); ?>) - convert_to_money;
            }

            if (pay_now_need_pay < 0) {
                pay_now_need_pay = 0;
            }
            $('.convert_pay_now').html(bc_format_money(pay_now_need_pay));
            $('.convert_deposit_amount').html(bc_format_money(convert_to_money));
        }


        jQuery(function() {
            $(".bc_apply_coupon").click(function() {
                var parent = $(this).closest('.section-coupon-form');
                parent.find(".group-form .fa-spin").removeClass("d-none");
                parent.find(".message").html('');
                $.ajax({
                    'url': bookingCore.url + '/booking/<?php echo e($booking->code); ?>/apply-coupon',
                    'data': parent.find('input,textarea,select').serialize(),
                    'cache': false,
                    'method': "post",
                    success: function(res) {
                        parent.find(".group-form .fa-spin").addClass("d-none");
                        if (res.reload !== undefined) {
                            window.location.reload();
                        }
                        if (res.message && res.status === 1) {
                            parent.find('.message').html('<div class="alert alert-success">' +
                                res.message + '</div>');
                        }
                        if (res.message && res.status === 0) {
                            parent.find('.message').html('<div class="alert alert-danger">' +
                                res.message + '</div>');
                        }
                    }
                });
            });
            $(".bc_remove_coupon").click(function(e) {
                e.preventDefault();
                var parent = $(this).closest('.section-coupon-form');
                var parentItem = $(this).closest('.item');
                parentItem.find(".fa-spin").removeClass("d-none");
                $.ajax({
                    'url': bookingCore.url + '/booking/<?php echo e($booking->code); ?>/remove-coupon',
                    'data': {
                        coupon_code: $(this).attr('data-code')
                    },
                    'cache': false,
                    'method': "post",
                    success: function(res) {
                        parentItem.find(".fa-spin").addClass("d-none");
                        if (res.reload !== undefined) {
                            window.location.reload();
                        }
                        if (res.message && res.status === 0) {
                            parent.find('.message').html('<div class="alert alert-danger">' +
                                res.message + '</div>');
                        }
                    }
                });
            });
        })
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Booking/Views/frontend/checkout.blade.php ENDPATH**/ ?>