<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="<?php echo e($html_class ?? ''); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- Professional Branding & Meta Tags -->
    <meta name="theme-color" content="#FF6B35">
    <meta name="msapplication-TileColor" content="#FF6B35">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Tsoka Travel">
    <link rel="manifest" href="<?php echo e(url('site.webmanifest')); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo e(url('favicon.svg')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(url('apple-touch-icon.png')); ?>">

    <?php $favicon = setting_item('site_favicon'); ?>
    <?php if($favicon): ?>
        <?php
            $file = (new \Modules\Media\Models\MediaFile())->findById($favicon);
        ?>
        <?php if(!empty($file)): ?>
            <link rel="icon" type="<?php echo e($file['file_type']); ?>" href="<?php echo e(asset('uploads/' . $file['file_path'])); ?>" />
        <?php endif; ?>
    <?php endif; ?>

    <?php echo $__env->make('Layout::parts.seo-enhanced', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link href="<?php echo e(asset('themes/gotrip/css/vendors.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('themes/gotrip/css/main.css')); ?>" rel="stylesheet">
    <!-- Premium Design System -->
    <link href="<?php echo e(asset('custom/premium.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/icofont/icofont.min.css')); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/daterange/daterangepicker.css')); ?>">
    <link href="<?php echo e(asset('libs/carousel-2/owl.carousel.css')); ?>" rel="stylesheet">
    <!-- Tsoka Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{--ink:#1a0900;--bronze:#eea61a;--bone:#f9f5ec;--paper:#fefdf9;}
        body{font-family:'Instrument Sans',system-ui,sans-serif!important;background-color:var(--bone)!important;color:var(--ink)!important;}
        h1,h2,h3,h4,h5,h6,.fw-500,.fw-600,.fw-700{font-family:'DM Sans',system-ui,sans-serif!important;}
        :focus-visible{outline:2px solid var(--bronze);outline-offset:3px;border-radius:4px;}
    </style>
    <link rel="stylesheet"
        href="<?php echo e(asset('themes/gotrip/dist/frontend/css/app.css?_v=' . config('app.asset_version'))); ?>">

    <?php if(setting_item('cookie_agreement_type') == 'cookie_consent'): ?>
        <link rel="stylesheet" href="<?php echo e(asset('libs/cookie-consent/cookieconsent.css')); ?>" media="print"
            onload="this.media='all'">
    <?php endif; ?>

    <?php echo \App\Helpers\Assets::css(); ?>

    <?php echo \App\Helpers\Assets::js(); ?>

    <?php echo $__env->make('Layout::parts.global-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <!-- Styles -->
    <?php echo $__env->yieldPushContent('css'); ?>
    
    <link href="<?php echo e(route('core.style.customCss')); ?>" rel="stylesheet">
    <?php if(setting_item_with_lang('enable_rtl')): ?>
        <link href="<?php echo e(asset('themes/gotrip/dist/frontend/css/rtl.css')); ?>" rel="stylesheet">
    <?php endif; ?>
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('head_scripts'); ?>

        <?php echo setting_item_with_lang_raw('head_scripts'); ?>

    <?php endif; ?>
</head>
<?php
// Show Tsoka user bar for ALL authenticated users on every page
$hasAdminbar = Auth::check() && empty(request('preview'));
?>
<body
    class="frontend-page <?php echo e($hasAdminbar ? 'has-adminbar' : ''); ?> <?php echo e(!empty($row->header_style) ? 'header-' . $row->header_style : 'header-normal'); ?> <?php echo e($body_class ?? ''); ?> <?php echo e(setting_item_with_lang('enable_rtl') ? 'is-rtl' : ''); ?> <?php echo e(is_api() ? 'is_api' : ''); ?>">
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('body_scripts'); ?>

        <?php echo setting_item_with_lang_raw('body_scripts'); ?>

    <?php endif; ?>
    <?php if($hasAdminbar): ?>
        <?php echo $__env->make('Layout::parts.adminbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
    <div class="bc_wrap overflow-hidden">
        <?php if(!empty($row)): ?>
            <?php $hideHeaderMargin = ['transparent','transparent_v2','transparent_v3','transparent_v4','transparent_v5','transparent_v6','transparent_v7','transparent_v8','transparent_v9'] ?>
            <?php if(!in_array($row->header_style, $hideHeaderMargin)): ?>
                <div class="header-margin"></div>
            <?php endif; ?>
        <?php else: ?>
            <?php if(empty($hide_header_margin)): ?>
                <div class="header-margin"></div>
            <?php endif; ?>
        <?php endif; ?>
        <?php echo $__env->make('Layout::parts.preload', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Layout::parts.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->yieldContent('content'); ?>
        <?php echo $__env->make('Layout::parts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('footer_scripts'); ?>

        <?php echo setting_item_with_lang_raw('footer_scripts'); ?>

    <?php endif; ?>

</body>

</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/app.blade.php ENDPATH**/ ?>