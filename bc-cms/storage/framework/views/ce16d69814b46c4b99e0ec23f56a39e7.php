<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="<?php echo e($html_class ?? ''); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(url('uploads/0000/6/2026/05/23/favicon2.png')); ?>" />
    <?php echo $__env->make('Layout::parts.seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link href="<?php echo e(asset('libs/bootstrap/css/bootstrap.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/font-awesome/css/font-awesome.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/ionicons/css/ionicons.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/icofont/icofont.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('dist/frontend/css/app.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/daterange/daterangepicker.css')); ?>">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel='stylesheet' id='google-font-css-css'
        href='https://fonts.googleapis.com/css?family=Poppins%3A400%2C500%2C600' type='text/css' media='all' />
    <?php echo $__env->make('Layout::parts.global-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <!-- Styles -->
    <?php echo $__env->yieldPushContent('css'); ?>
    
    <link href="<?php echo e(route('core.style.customCss')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/carousel-2/owl.carousel.css')); ?>" rel="stylesheet">
    <?php if(setting_item_with_lang('enable_rtl')): ?>
        <link href="<?php echo e(asset('css/rtl.css')); ?>" rel="stylesheet">
    <?php endif; ?>
</head>

<body class="<?php echo e($body_class ?? ''); ?>">
    <div class="bc_wrap">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
    
    <script src="<?php echo e(asset('libs/lazy-load/intersection-observer.js')); ?>"></script>
    <script async src="<?php echo e(asset('libs/lazy-load/lazyload.min.js')); ?>"></script>
    <script>
        // Set the options to make LazyLoad self-initialize
        window.lazyLoadOptions = {
            elements_selector: ".lazy",
            // ... more custom settings?
        };
        // Listen to the initialization event and get the instance of LazyLoad
        window.addEventListener('LazyLoad::Initialized', function(event) {
            window.lazyLoadInstance = event.detail.instance;
        }, false);
    </script>
    <script src="<?php echo e(asset('libs/jquery-3.6.3.min.js')); ?>"></script>
    <script src="<?php echo e(asset('libs/vue/vue' . (!env('APP_DEBUG') ? '.min' : '') . '.js')); ?>"></script>
    <script src="<?php echo e(asset('libs/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <?php if(Auth::check()): ?>
        <script src="<?php echo e(asset('module/media/js/browser.js')); ?>"></script>
    <?php endif; ?>
    <script src="<?php echo e(asset('libs/carousel-2/owl.carousel.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/daterange/moment.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('libs/daterange/daterangepicker.min.js')); ?>"></script>
    <script src="<?php echo e(asset('js/functions.js')); ?>"></script>
    <script src="<?php echo e(asset('js/home.js')); ?>"></script>
    <?php echo $__env->yieldPushContent('js'); ?>
    <?php \App\Helpers\ReCaptchaEngine::scripts() ?>
</body>

</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Layout/empty.blade.php ENDPATH**/ ?>