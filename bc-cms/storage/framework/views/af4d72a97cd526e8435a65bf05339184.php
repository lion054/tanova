<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Error'); ?> — <?php echo e(config('app.name', 'Tsoka Travel')); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .err-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 32px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }
        .err-nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #111827;
            font-weight: 700;
            font-size: 15px;
        }
        .err-nav-brand img { height: 32px; width: auto; }
        .err-nav-home {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
        }
        .err-nav-home:hover { color: #111827; }
        .err-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            position: relative;
            overflow: hidden;
        }
        .err-bg-code {
            position: absolute;
            font-size: clamp(120px, 25vw, 240px);
            font-weight: 800;
            letter-spacing: -12px;
            user-select: none;
            pointer-events: none;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            white-space: nowrap;
            opacity: .06;
        }
        .err-card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 52px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.05), 0 10px 40px -12px rgba(0,0,0,.12);
            position: relative;
            z-index: 1;
        }
        .err-icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .err-icon-wrap svg { width: 38px; height: 38px; }
        .err-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 100px;
            margin-bottom: 14px;
        }
        .err-code {
            font-size: 64px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -3px;
            line-height: 1;
            margin-bottom: 8px;
        }
        .err-title {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 10px;
        }
        .err-message {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.65;
            margin-bottom: 32px;
        }
        .err-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity .15s;
        }
        .err-btn:hover { opacity: .85; }
        .err-btn-ghost {
            background: transparent;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
        }
        .err-btn-ghost:hover { color: #111827; border-color: #9ca3af; }
        .err-footer {
            text-align: center;
            padding: 16px;
            font-size: 12px;
            color: #9ca3af;
        }
        @media (max-width: 540px) {
            .err-card { padding: 36px 28px; }
            .err-code { font-size: 48px; }
        }
        <?php echo $__env->yieldContent('extra-style'); ?>
    </style>
</head>
<body>

<nav class="err-nav">
    <a class="err-nav-brand" href="<?php echo e(url('/')); ?>">
        <?php $logo = setting_item('logo_id') ? get_file_url(setting_item('logo_id')) : null; ?>
        <?php if($logo): ?>
            <img src="<?php echo e($logo); ?>" alt="<?php echo e(setting_item('site_title', 'Tsoka Travel')); ?>">
        <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            <?php echo e(setting_item('site_title', 'Tsoka Travel')); ?>

        <?php endif; ?>
    </a>
    <a class="err-nav-home" href="<?php echo e(url('/')); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
        Back to Home
    </a>
</nav>

<div class="err-stage">
    <div class="err-bg-code" aria-hidden="true" style="color:<?php echo $__env->yieldContent('code-color','#1d4ed8'); ?>"><?php echo $__env->yieldContent('code','ERR'); ?></div>
    <div class="err-card">
        <div class="err-icon-wrap" style="background:<?php echo $__env->yieldContent('icon-bg','#eff6ff'); ?>">
            <?php echo $__env->yieldContent('icon'); ?>
        </div>
        <span class="err-badge" style="background:<?php echo $__env->yieldContent('badge-bg','#eff6ff'); ?>;color:<?php echo $__env->yieldContent('badge-color','#1d4ed8'); ?>"><?php echo $__env->yieldContent('badge-label','Error'); ?></span>
        <div class="err-code"><?php echo $__env->yieldContent('code','?'); ?></div>
        <h1 class="err-title"><?php echo $__env->yieldContent('title','Something went wrong'); ?></h1>
        <p class="err-message"><?php echo $__env->yieldContent('message','An unexpected error occurred.'); ?></p>
        <div class="err-actions"><?php echo $__env->yieldContent('actions'); ?></div>
    </div>
</div>

<footer class="err-footer">
    &copy; <?php echo e(date('Y')); ?> <?php echo e(setting_item('site_title', 'Tsoka Travel')); ?>. All rights reserved.
</footer>

<?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/errors/layout.blade.php ENDPATH**/ ?>