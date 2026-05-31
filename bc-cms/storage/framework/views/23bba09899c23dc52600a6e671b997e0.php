<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="<?php echo e($html_class ?? ''); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(url('uploads/0000/6/2026/05/23/favicon2.png')); ?>" />
    <?php echo $__env->make('Layout::parts.seo-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link href="<?php echo e(asset('libs/bootstrap/css/bootstrap.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/font-awesome/css/font-awesome.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/ionicons/css/ionicons.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('libs/icofont/icofont.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('dist/frontend/css/notification.css')); ?>" rel="newest stylesheet">
    <link href="<?php echo e(asset('dist/frontend/css/app.css?_ver=' . config('app.asset_version'))); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/daterange/daterangepicker.css')); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('libs/select2/css/select2.min.css')); ?>">
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel='stylesheet' id='google-font-css-css'
        href='https://fonts.googleapis.com/css?family=Poppins%3A400%2C500%2C600' type='text/css' media='all' />
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
    <link href="<?php echo e(asset('dist/frontend/module/user/css/user.css?_ver=' . config('app.asset_version'))); ?>"
        rel="stylesheet">
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
    <link href="<?php echo e(asset('libs/carousel-2/owl.carousel.css')); ?>" rel="stylesheet">
    <?php if(setting_item_with_lang('enable_rtl')): ?>
        <link href="<?php echo e(asset('dist/frontend/css/rtl.css')); ?>" rel="stylesheet">
    <?php endif; ?>
</head>

<body class="user-page <?php echo e($body_class ?? ''); ?> <?php if(setting_item_with_lang('enable_rtl')): ?> is-rtl <?php endif; ?>">
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('body_scripts'); ?>

    <?php endif; ?>
    <div class="bc_wrap">
        <?php echo $__env->make('Layout::parts.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('Layout::parts.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="bc_user_profile">
            <div class="container-fluid">
                <div class="row row-eq-height">
                    <div class="col-md-3">
                        <?php echo $__env->make('User::frontend.layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="col-md-9">
                        <div class="user-form-settings">
                            <?php echo $__env->make('Layout::parts.user-bc', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php echo $__env->yieldContent('content'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $__env->make('Layout::parts.footer', ['is_user_page' => 1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <script
        src="<?php echo e(asset('libs/filerobot-image-editor/filerobot-image-editor.min.js?_ver=' . config('app.asset_version'))); ?>">
    </script>
    <?php if(!is_demo_mode()): ?>
        <?php echo setting_item('footer_scripts'); ?>

    <?php endif; ?>
</body>

</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Layout/user.blade.php ENDPATH**/ ?>