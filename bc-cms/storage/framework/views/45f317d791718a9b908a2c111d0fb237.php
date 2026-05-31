<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="<?php echo e($html_class ?? ''); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(url('uploads/0000/6/2026/05/23/favicon2.png')); ?>" />
    <?php echo $__env->make('Layout::parts.seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Portal Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        /* ── Design tokens ─────────────────────────────────────── */
        :root {
            --p-black:  #0a0a0a;
            --p-white:  #ffffff;
            --p-g50:    #fafafa;
            --p-g100:   #f5f5f5;
            --p-g200:   #e8e8e8;
            --p-g300:   #d0d0d0;
            --p-g400:   #a0a0a0;
            --p-g600:   #5a5a5a;
            --p-g800:   #222222;
            --p-panel:  #111111;
            /* override GoTrip warm palette */
            --ink:      #0a0a0a;
            --bronze:   #0a0a0a;
            --bone:     #f5f5f5;
            --paper:    #ffffff;
        }

        /* ── Base typography ────────────────────────────────────── */
        body,
        body * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'DM Serif Display', Georgia, serif !important;
            font-weight: 400 !important;
            letter-spacing: -0.02em !important;
        }
        :focus-visible {
            outline: 2px solid var(--p-black);
            outline-offset: 3px;
            border-radius: 3px;
        }

        /* ── Kill GoTrip blue accent everywhere ─────────────────── */
        .bg-blue-1     { background:   var(--p-black) !important; }
        .text-blue-1   { color:        var(--p-black) !important; }
        .bg-blue-1-05  { background:   var(--p-g100)  !important; }
        .border-blue-1 { border-color: var(--p-black) !important; }

        /* ── Page background ────────────────────────────────────── */
        .bg-light-2,
        .dashboard__content.bg-light-2,
        .dashboard__content { background: var(--p-g100) !important; }

        /* ── Cards ──────────────────────────────────────────────── */
        .py-30.px-30.rounded-4.bg-white,
        .py-30.px-30.rounded-4.bg-white.shadow-3,
        .bc-list-item.py-30.px-30.rounded-4.bg-white.shadow-3 {
            box-shadow: none !important;
            border: 1px solid var(--p-g200) !important;
            border-radius: 4px !important;
        }
        .shadow-3 { box-shadow: none !important; border: 1px solid var(--p-g200) !important; }

        /* ── Buttons ────────────────────────────────────────────── */
        .button.-dark-1.bg-blue-1.text-white,
        .button.bg-blue-1.text-white,
        .btn-file,
        button.button.-dark-1.bg-blue-1.text-white {
            background: var(--p-black)   !important;
            color:      var(--p-white)   !important;
            border:     1px solid var(--p-black) !important;
            border-radius: 4px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            letter-spacing: .03em !important;
            transition: background .15s !important;
        }
        .button.-dark-1.bg-blue-1.text-white:hover,
        .button.bg-blue-1.text-white:hover {
            background: var(--p-g800) !important;
        }
        /* secondary / outline buttons */
        .button.-outline-dark-1,
        .button.-outline-blue-1 {
            border-color: var(--p-black) !important;
            color:        var(--p-black) !important;
        }
        /* become-vendor button */
        .become-vendor.bg-blue-1 {
            background: var(--p-black) !important;
            color: var(--p-white)      !important;
        }

        /* ── Forms ──────────────────────────────────────────────── */
        .form-input input,
        .form-input textarea,
        .form-input select,
        .form-control {
            border-color: var(--p-g200) !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            color: var(--p-black) !important;
        }
        .form-input input:focus,
        .form-input textarea:focus,
        .form-input select:focus,
        .form-control:focus {
            border-color: var(--p-black) !important;
            box-shadow: none !important;
            outline: none !important;
        }
        /* floating label */
        .form-input label {
            color: var(--p-g400) !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            letter-spacing: .1em !important;
            text-transform: uppercase !important;
        }

        /* ── Tabs ───────────────────────────────────────────────── */
        .tabs.-underline-2 .tabs__button.is-tab-el-active {
            color:        var(--p-black) !important;
            border-color: var(--p-black) !important;
        }
        .tabs.-underline-2 .tabs__button {
            color: var(--p-g400) !important;
        }
        .tabs.-underline-2 .tabs__button:hover {
            color: var(--p-black) !important;
        }

        /* ── Tables ─────────────────────────────────────────────── */
        .table-3 thead.bg-light-2 th,
        .table-2 thead th,
        thead.bg-light-2 {
            background: var(--p-g100) !important;
            color:      var(--p-g600) !important;
            font-size:  10px !important;
            font-weight: 600 !important;
            letter-spacing: .1em !important;
            text-transform: uppercase !important;
        }
        .table-3 tbody tr:hover,
        .table-2 tbody tr:hover { background: var(--p-g50) !important; }

        /* ── Status badges ─ keep colour signals but muted ───────── */
        .bg-yellow-4  { background: var(--p-g200) !important; }
        .text-yellow-3{ color:      var(--p-g600) !important; }
        .bg-green-1   { background: #e8f0e8        !important; }
        .text-green-2 { color:      #2d5f2d        !important; }
        .bg-red-3     { background: #f0e8e8        !important; }
        .text-red-2   { color:      #8b2020        !important; }

        /* ── Misc ───────────────────────────────────────────────── */
        .badge-info { background: var(--p-g200) !important; color: var(--p-g800) !important; }
        .text-light-1 { color: var(--p-g400) !important; }
        .border-top-light { border-color: var(--p-g200) !important; }
        .border-light     { border-color: var(--p-g200) !important; }
        .rounded-100 { border-radius: 4px !important; }
        .rounded-4   { border-radius: 4px !important; }
        a.underline  { color: var(--p-black) !important; text-decoration: underline; text-underline-offset: 2px; }

        /* icon-arrow-top-right used in buttons */
        .icon-arrow-top-right { display: none !important; }

        /* ── Portal page header (shared across all pages) ───────── */
        .portal-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding-bottom: 28px;
            border-bottom: 1px solid #e8e8e8;
            margin-bottom: 28px;
        }
        .portal-eyebrow {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #a0a0a0;
            margin-bottom: 6px;
            font-family: 'Inter', sans-serif !important;
        }
        .portal-h1 {
            font-size: 26px;
            font-weight: 400;
            letter-spacing: -.02em;
            color: #0a0a0a;
            line-height: 1.1;
            margin: 0;
        }
        .portal-h1 em { font-style: italic; color: #a0a0a0; }

        /* Tabs — tighten sizing */
        .tabs.-underline-2 .tabs__button {
            font-size: 13px !important;
            font-weight: 500 !important;
        }

        /* Stat cards */
        .portal-stat {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 4px;
            padding: 24px;
        }
        .portal-stat__label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #a0a0a0;
            margin-bottom: 8px;
        }
        .portal-stat__value {
            font-size: 28px;
            font-weight: 600;
            color: #0a0a0a;
            letter-spacing: -.02em;
            line-height: 1;
            font-family: 'Inter', sans-serif !important;
        }
        .portal-stat__desc {
            font-size: 11px;
            color: #a0a0a0;
            margin-top: 4px;
        }

        /* Section cards */
        .portal-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 4px;
            padding: 24px;
        }
        .portal-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .portal-card__title {
            font-size: 13px;
            font-weight: 600;
            color: #0a0a0a;
            letter-spacing: .02em;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif !important;
        }
        .portal-card__link {
            font-size: 12px;
            color: #0a0a0a;
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 500;
        }
        .portal-card__link:hover { color: #5a5a5a; }

        /* Date range picker button */
        .portal-daterange {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: #5a5a5a;
            border: 1px solid #e8e8e8;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            background: #fff;
            transition: border-color .12s;
        }
        .portal-daterange:hover { border-color: #0a0a0a; }
        .portal-daterange i { color: #a0a0a0; font-size: 11px; }

        /* Empty state */
        .portal-empty {
            text-align: center;
            padding: 40px 0;
            font-size: 13px;
            color: #a0a0a0;
        }

        /* Upload avatar button */
        .portal-upload-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0a0a0a;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: .03em;
            border: none;
            border-radius: 4px;
            padding: 9px 16px;
            cursor: pointer;
            overflow: hidden;
            transition: background .15s;
        }
        .portal-upload-btn:hover { background: #222; }
        .portal-upload-btn input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
        }
        .portal-avatar-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            border: 1px solid #e8e8e8;
            flex-shrink: 0;
        }
        .portal-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Danger zone */
        .portal-danger-zone {
            border: 1px solid #f0e8e8;
            border-radius: 4px;
            padding: 20px 24px;
            margin-top: 32px;
            background: #fffafa;
        }
        .portal-danger-zone h4 {
            font-size: 13px;
            font-weight: 600;
            color: #8b2020;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif !important;
            letter-spacing: .02em;
        }
        .portal-danger-zone p { font-size: 12px; color: #5a5a5a; margin-bottom: 14px; }
        .portal-btn-danger {
            font-size: 12px;
            font-weight: 500;
            color: #8b2020;
            background: transparent;
            border: 1px solid #f0e8e8;
            border-radius: 4px;
            padding: 7px 14px;
            text-decoration: none;
            display: inline-block;
            transition: background .15s, border-color .15s;
        }
        .portal-btn-danger:hover { background: #f0e8e8; border-color: #e8d0d0; color: #8b2020; }
    </style>

    <link href="<?php echo e(asset('libs/bootstrap/css/bootstrap.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/font-awesome/css/font-awesome.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/ionicons/css/ionicons.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/icofont/icofont.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('dist/frontend/css/notification.css')); ?>" rel="newest stylesheet">
    <link href="<?php echo e(asset('dist/frontend/css/app.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/daterange/daterangepicker.css')); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/select2/css/select2.min.css')); ?>">
    <link href="<?php echo e(asset('themes/gotrip/css/vendors.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('themes/gotrip/css/main.css')); ?>" rel="stylesheet">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="<?php echo e(asset('dist/frontend/module/user/css/user.css?_ver=' . config('app.asset_version'))); ?>"
        rel="stylesheet">
    <link rel="stylesheet"
        href="<?php echo e(asset('themes/gotrip/dist/frontend/css/app.css?_v=' . config('app.asset_version'))); ?>">
    <link rel="stylesheet"
        href="<?php echo e(asset('themes/gotrip/dist/frontend/css/user.css?_v=' . config('app.asset_version'))); ?>">
    <?php echo $__env->make('Layout::parts.global-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script>
        var image_editer = {
            language: '<?php echo e(app()->getLocale()); ?>',
            translations: {
                <?php echo e(app()->getLocale()); ?>: {
                    'header.image_editor_title': '<?php echo e(__('Image Editor')); ?>',
                    'header.toggle_fullscreen': '<?php echo e(__('Toggle fullscreen')); ?>',
                    'header.close': '<?php echo e(__('Close')); ?>',
                    'header.close_modal': '<?php echo e(__('Close window')); ?>',
                    'toolbar.download': '<?php echo e(__('Save Change')); ?>',
                    'toolbar.save': '<?php echo e(__('Save')); ?>',
                    'toolbar.apply': '<?php echo e(__('Apply')); ?>',
                    'toolbar.saveAsNewImage': '<?php echo e(__('Save As New Image')); ?>',
                    'toolbar.cancel': '<?php echo e(__('Cancel')); ?>',
                    'toolbar.go_back': '<?php echo e(__('Go Back')); ?>',
                    'toolbar.adjust': '<?php echo e(__('Adjust')); ?>',
                    'toolbar.effects': '<?php echo e(__('Effects')); ?>',
                    'toolbar.filters': '<?php echo e(__('Filters')); ?>',
                    'toolbar.orientation': '<?php echo e(__('Orientation')); ?>',
                    'toolbar.crop': '<?php echo e(__('Crop')); ?>',
                    'toolbar.resize': '<?php echo e(__('Resize')); ?>',
                    'toolbar.watermark': '<?php echo e(__('Watermark')); ?>',
                    'toolbar.focus_point': '<?php echo e(__('Focus point')); ?>',
                    'toolbar.shapes': '<?php echo e(__('Shapes')); ?>',
                    'toolbar.image': '<?php echo e(__('Image')); ?>',
                    'toolbar.text': '<?php echo e(__('Text')); ?>',
                    'adjust.brightness': '<?php echo e(__('Brightness')); ?>',
                    'adjust.contrast': '<?php echo e(__('Contrast')); ?>',
                    'adjust.exposure': '<?php echo e(__('Exposure')); ?>',
                    'adjust.saturation': '<?php echo e(__('Saturation')); ?>',
                    'orientation.rotate_l': '<?php echo e(__('Rotate Left')); ?>',
                    'orientation.rotate_r': '<?php echo e(__('Rotate Right')); ?>',
                    'orientation.flip_h': '<?php echo e(__('Flip Horizontally')); ?>',
                    'orientation.flip_v': '<?php echo e(__('Flip Vertically')); ?>',
                    'pre_resize.title': '<?php echo e(__('Would you like to reduce resolution before editing the image?')); ?>',
                    'pre_resize.keep_original_resolution': '<?php echo e(__('Keep original resolution')); ?>',
                    'pre_resize.resize_n_continue': '<?php echo e(__('Resize & Continue')); ?>',
                    'footer.reset': '<?php echo e(__('Reset')); ?>',
                    'footer.undo': '<?php echo e(__('Undo')); ?>',
                    'footer.redo': '<?php echo e(__('Redo')); ?>',
                    'spinner.label': '<?php echo e(__('Processing...')); ?>',
                    'warning.too_big_resolution': '<?php echo e(__('The resolution of the image is too big for the web. It can cause problems with Image Editor performance.')); ?>',
                    'common.x': '<?php echo e(__('x')); ?>',
                    'common.y': '<?php echo e(__('y')); ?>',
                    'common.width': '<?php echo e(__('width')); ?>',
                    'common.height': '<?php echo e(__('height')); ?>',
                    'common.custom': '<?php echo e(__('custom')); ?>',
                    'common.original': '<?php echo e(__('original')); ?>',
                    'common.square': '<?php echo e(__('square')); ?>',
                    'common.opacity': '<?php echo e(__('Opacity')); ?>',
                    'common.apply_watermark': '<?php echo e(__('Apply watermark')); ?>',
                    'common.url': '<?php echo e(__('URL')); ?>',
                    'common.upload': '<?php echo e(__('Upload')); ?>',
                    'common.gallery': '<?php echo e(__('Gallery')); ?>',
                    'common.text': '<?php echo e(__('Text')); ?>',
                }
            }
        };
    </script>
    <!-- Styles -->
    <?php echo $__env->yieldPushContent('css'); ?>
    <style type="text/css">
        .bc_topbar,
        .bc_header,
        .bc_footer {
            display: none;
        }

        html,
        body,
        .bc_wrap,
        .bc_user_profile,
        .bc_user_profile>.container-fluid>.row-eq-height>.col-md-3 {
            min-height: 100vh !important;
        }
    </style>
    
    <link href="<?php echo e(route('core.style.customCss')); ?>" rel="stylesheet">
    <?php if(setting_item_with_lang('enable_rtl')): ?>
        <link href="<?php echo e(asset('themes/gotrip/dist/frontend/css/rtl.css')); ?>" rel="stylesheet">
    <?php endif; ?>
    <link href="<?php echo e(asset('libs/carousel-2/owl.carousel.css')); ?>" rel="stylesheet">
    <?php if(setting_item_with_lang('enable_rtl')): ?>
        <link href="<?php echo e(asset('dist/frontend/css/rtl.css')); ?>" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        .tsoka-sidebar { background: #111111 !important; color: #ffffff !important; }

        /* Everything white by default */
        .tsoka-sidebar *,
        .tsoka-sidebar a,
        .tsoka-sidebar span,
        .tsoka-sidebar i,
        .tsoka-sidebar .tsoka-sb-link {
            color: #ffffff !important;
            text-decoration: none !important;
            background: transparent !important;
        }

        /* Grey on hover */
        .tsoka-sidebar a:hover,
        .tsoka-sidebar .tsoka-sb-link:hover {
            color: rgba(255,255,255,.5) !important;
            background: rgba(255,255,255,.05) !important;
        }
        .tsoka-sidebar a:hover i,
        .tsoka-sidebar .tsoka-sb-link:hover i { color: rgba(255,255,255,.5) !important; }

        /* Active — bright white + left border */
        .tsoka-sidebar .is-active,
        .tsoka-sidebar a.is-active {
            color: #ffffff !important;
            background: rgba(255,255,255,.08) !important;
            border-left-color: #ffffff !important;
            font-weight: 500 !important;
        }

        /* Child items */
        .tsoka-sidebar .tsoka-sb-children a {
            color: #ffffff !important;
            display: block !important;
            font-size: 12px !important;
            padding: 5px 0 !important;
        }
        .tsoka-sidebar .tsoka-sb-children a:hover { color: rgba(255,255,255,.5) !important; }

        /* Muted meta text */
        .tsoka-sb-role  { color: rgba(255,255,255,.45) !important; }
        .tsoka-sb-since { color: rgba(255,255,255,.3) !important; }

        /* ── Layout: full-width content after sidebar ─────────────── */
        :root { --dashboard-width: 0px !important; }
        .dashboard {
            display: flex !important;
            align-items: stretch !important;
            min-height: calc(100vh - 56px) !important;
        }
        .dashboard__main {
            flex: 1 1 0% !important;
            min-width: 0 !important;
            padding-left: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        .dashboard__content {
            flex: 1 !important;
            padding: 0 !important;
            max-width: none !important;
            width: 100% !important;
            background: #f5f5f5 !important;
        }
        /* Inner page wrapper — consistent page padding */
        .bc-user-dashboard,
        .bc-booking-history,
        .bc-user-profile,
        .bc-change-password,
        .bc-wishlist,
        .bc-vendor-dashboard,
        .bc-vendor-booking,
        .bc-vendor-service {
            padding: 40px 48px !important;
        }
        /* Generic fallback: direct children of dashboard__content */
        .dashboard__content > div:not([class*="tsoka"]):not(.modal) {
            padding: 40px 48px;
        }

        /* ── Formal design refinements ────────────────────────────── */

        /* Header: stronger hierarchy */
        .portal-header {
            padding-bottom: 24px !important;
            margin-bottom: 32px !important;
            border-bottom: 1px solid #e0e0e0 !important;
        }
        .portal-eyebrow {
            font-size: 9px !important;
            letter-spacing: .18em !important;
            color: #b0b0b0 !important;
            margin-bottom: 8px !important;
        }
        .portal-h1 {
            font-size: 24px !important;
            color: #0a0a0a !important;
        }

        /* Stat cards: formal flat style */
        .portal-stat {
            background: #ffffff !important;
            border: 1px solid #e8e8e8 !important;
            border-radius: 3px !important;
            padding: 28px 24px !important;
            transition: border-color .15s !important;
        }
        .portal-stat:hover { border-color: #c0c0c0 !important; }
        .portal-stat__label {
            font-size: 9px !important;
            letter-spacing: .16em !important;
            color: #b0b0b0 !important;
            margin-bottom: 10px !important;
        }
        .portal-stat__value {
            font-size: 32px !important;
            font-weight: 700 !important;
            letter-spacing: -.03em !important;
            color: #0a0a0a !important;
        }
        .portal-stat__desc { color: #b0b0b0 !important; font-size: 11px !important; }

        /* Section cards */
        .portal-card {
            border-radius: 3px !important;
            border: 1px solid #e8e8e8 !important;
            background: #ffffff !important;
        }
        .portal-card__title {
            font-size: 11px !important;
            letter-spacing: .14em !important;
            color: #5a5a5a !important;
        }

        /* Tables: clean enterprise rows */
        .table-2,
        .table-3 {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .table-2 th, .table-3 th {
            font-size: 9px !important;
            letter-spacing: .14em !important;
            text-transform: uppercase !important;
            color: #a0a0a0 !important;
            background: #fafafa !important;
            padding: 10px 14px !important;
            font-weight: 600 !important;
            border-bottom: 1px solid #e8e8e8 !important;
        }
        .table-2 td, .table-3 td {
            padding: 11px 14px !important;
            border-bottom: 1px solid #f0f0f0 !important;
            font-size: 13px !important;
            color: #222 !important;
            vertical-align: middle !important;
        }
        .table-2 tbody tr:last-child td,
        .table-3 tbody tr:last-child td { border-bottom: none !important; }

        /* Forms: formal field style */
        .form-input input,
        .form-input textarea,
        .form-input select,
        .form-control {
            background: #fafafa !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 3px !important;
            font-size: 14px !important;
            padding: 10px 14px !important;
            color: #0a0a0a !important;
            transition: border-color .12s, background .12s !important;
        }
        .form-input input:focus,
        .form-input textarea:focus,
        .form-input select:focus,
        .form-control:focus {
            background: #ffffff !important;
            border-color: #0a0a0a !important;
        }
        .form-input label {
            font-size: 9px !important;
            letter-spacing: .14em !important;
            color: #a0a0a0 !important;
        }

        /* Hide GoTrip user footer — redundant in portal */
        .dashboard__content .footer-under,
        .dashboard__content footer { display: none !important; }

        /* ── Fixed full-height sidebar adjustments ────────────────── */
        /* The sidebar is now position:fixed and covers the header logo area.
           Push the header's logo slot to be transparent / dark so there's no white flash. */
        .ph-logo {
            background: #111111 !important;
            border-right-color: rgba(255,255,255,.07) !important;
        }
        .ph-logo a, .ph-logo span, .ph-logo-text { color: #fff !important; }
        /* Shift dashboard content right to clear the fixed sidebar */
        .bc_user_profile.dashboard,
        .dashboard.bc_user_profile {
            padding-left: 220px !important;
            display: block !important;
        }
        .dashboard__main { width: 100% !important; }
        /* Ensure header toggle button doesn't overlap sidebar */
        .ph-toggle { margin-left: 8px !important; }
    </style>
</head>

<body class="user-page <?php echo e($body_class ?? ''); ?> <?php if(setting_item_with_lang('enable_rtl')): ?> is-rtl <?php endif; ?>">
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('body_scripts'); ?>

    <?php endif; ?>
    <div class="bc_wrap">
        <div class="header-margin"></div>
        <?php echo $__env->make('Layout::parts.user.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="dashboard bc_user_profile p-0" data-x="dashboard" data-x-toggle="-is-sidebar-open">
            <?php echo $__env->make('User::frontend.layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="dashboard__main">
                <div class="dashboard__content bg-light-2">
                    <?php echo $__env->yieldContent('content'); ?>
                    <?php echo $__env->make('Layout::parts.user.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
            <div class="modal" tabindex="-1" id="modal_booking_detail">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo e(__('Booking ID: #')); ?> <span class="user_id"></span></h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-center"><?php echo e(__('Loading...')); ?></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script
        src="<?php echo e(asset('libs/filerobot-image-editor/filerobot-image-editor.min.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('footer_scripts'); ?>

    <?php endif; ?>
</body>

</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/user.blade.php ENDPATH**/ ?>