<style>
    .footer.-dashboard {
        border-top: 1px solid #e8e8e8 !important;
        padding: 20px 30px !important;
        margin-top: 48px !important;
        background: transparent !important;
    }
    .footer.-dashboard .footer__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .footer.-dashboard .text-14 {
        font-size: 11px !important;
        color: #a0a0a0 !important;
        font-weight: 400;
        letter-spacing: .01em;
    }
</style>
<footer class="footer -dashboard">
    <div class="footer__row">
        <div class="text-14">
            <?php echo setting_item_with_lang('footer_text_left') ?? ('&copy; ' . date('Y') . ' Tsoka Travel'); ?>

        </div>
    </div>
</footer>

<?php echo $__env->make('Popup::frontend.popup', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php if(Auth::check() and !empty($is_user_page)): ?>
    <?php echo $__env->make('Media::browser', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<!-- Custom script for all pages -->
<script src="<?php echo e(asset('libs/lodash.min.js')); ?>"></script>
<script src="<?php echo e(asset('libs/jquery-3.6.3.min.js')); ?>"></script>

<?php echo App\Helpers\MapEngine::scripts(); ?>


<script src="<?php echo e(asset('libs/vue/vue' . (!env('APP_DEBUG') ? '.min' : '') . '.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(asset('themes/gotrip/libs/bs/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo e(asset('libs/bootbox/bootbox.min.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(asset('themes/gotrip/js/vendors.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(asset('themes/gotrip/js/main.js')); ?>"></script>
<?php if(Auth::check() and !empty($is_user_page)): ?>
    <script src="<?php echo e(asset('module/media/js/browser.js?_ver=' . config('app.version'))); ?>"></script>
<?php endif; ?>
<script src="<?php echo e(asset('libs/carousel-2/owl.carousel.min.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(asset('libs/daterange/moment.min.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(asset('libs/daterange/daterangepicker.min.js')); ?>"></script>
<script src="<?php echo e(asset('libs/select2/js/select2.min.js')); ?>"></script>
<script src="<?php echo e(asset('module/user/js/user.js?_ver=' . config('app.asset_version'))); ?>"></script>
<?php if(setting_item('cookie_agreement_enable') == 1 and
        request()->cookie('booking_cookie_agreement_enable') != 1 and
        !is_api() and
        !isset($_COOKIE['booking_cookie_agreement_enable'])): ?>
    <div class="booking_cookie_agreement p-3 d-flex fixed-bottom">
        <div class="content-cookie"><?php echo clean(setting_item_with_lang('cookie_agreement_content')); ?></div>
        <button class="btn save-cookie"><?php echo clean(setting_item_with_lang('cookie_agreement_button_text')); ?></button>
    </div>
    <script>
        var save_cookie_url = '<?php echo e(route('core.cookie.check')); ?>';
    </script>
    <script src="<?php echo e(asset('js/cookie.js?_ver=' . config('app.version'))); ?>"></script>
<?php endif; ?>


<script src="<?php echo e(asset('themes/gotrip/dist/frontend/js/gotrip.js?_ver=' . config('app.version'))); ?>"></script>

<?php \App\Helpers\ReCaptchaEngine::scripts() ?>
<?php if(setting_item('user_enable_2fa')): ?>
    <?php echo $__env->make('auth.confirm-password-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script src="<?php echo e(asset('/module/user/js/2fa.js')); ?>"></script>
<?php endif; ?>
<?php echo $__env->yieldPushContent('js'); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/parts/user/footer.blade.php ENDPATH**/ ?>