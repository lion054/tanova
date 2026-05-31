<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name', 'Tsoka Travel')); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e(url('uploads/0000/6/2026/05/23/favicon2.png')); ?>"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black:   #0a0a0a;
            --white:   #ffffff;
            --g100:    #f5f5f5;
            --g200:    #e8e8e8;
            --g300:    #d0d0d0;
            --g400:    #a0a0a0;
            --g600:    #5a5a5a;
            --g800:    #222222;
            --panel:   #111111;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 15px;
            -webkit-font-smoothing: antialiased;
            color: var(--black);
        }

        /* ── Split layout ─────────────────────────────────────── */
        .auth-split {
            display: flex;
            min-height: 100vh;
        }

        /* ── Left panel ───────────────────────────────────────── */
        .auth-left {
            flex: 0 0 46%;
            background: var(--panel);
            display: flex;
            flex-direction: column;
            padding: 36px 48px;
            position: relative;
            overflow: hidden;
        }

        /* Page-specific background image (set per page) */
        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 0;
        }

        /* Everything inside left panel stacks above the overlay */
        .auth-left > * { position: relative; z-index: 1; }

        /* Top bar: logo + nav */
        .auth-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .auth-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .auth-logo img { height: 36px; width: auto; }

        /* Nav */
        .auth-nav {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .auth-nav a {
            font-size: 12px;
            font-weight: 400;
            color: rgba(255,255,255,.5);
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            letter-spacing: .01em;
            transition: color .15s;
        }
        .auth-nav a:hover { color: rgba(255,255,255,.9); }
        .auth-nav-soon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 400;
            color: rgba(255,255,255,.3);
            padding: 5px 10px;
            cursor: default;
        }
        .auth-soon-pill {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            border: 1px solid rgba(255,255,255,.2);
            color: rgba(255,255,255,.4);
            border-radius: 3px;
            padding: 1px 5px;
        }

        /* Brand body */
        .auth-brand-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 0 32px;
        }

        .auth-eyebrow {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: rgba(255,255,255,.4);
            margin-bottom: 20px;
        }

        .auth-brand-body h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(40px, 4.5vw, 58px);
            font-weight: 400;
            line-height: 1.08;
            letter-spacing: -.02em;
            color: #ffffff;
            margin-bottom: 18px;
        }

        .auth-brand-body h1 em {
            font-style: italic;
            color: rgba(255,255,255,.55);
        }

        .auth-brand-body p {
            font-size: 14px;
            line-height: 1.65;
            color: rgba(255,255,255,.45);
            max-width: 320px;
            font-weight: 400;
        }

        /* Minimal tags */
        .auth-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 36px;
        }
        .auth-tag {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            padding: 4px 10px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 2px;
        }

        /* Footer */
        .auth-left-foot {
            border-top: 1px solid rgba(255,255,255,.06);
            padding-top: 20px;
            font-size: 11px;
            color: rgba(255,255,255,.2);
            letter-spacing: .01em;
        }

        /* ── Right panel ──────────────────────────────────────── */
        .auth-right {
            flex: 1;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .auth-form-box {
            width: 100%;
            max-width: 380px;
        }

        /* Form heading */
        .auth-form-box .form-heading { margin-bottom: 28px; }
        .auth-form-box .form-heading h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 28px;
            font-weight: 400;
            letter-spacing: -.02em;
            color: var(--black);
            margin-bottom: 6px;
        }
        .auth-form-box .form-heading p {
            font-size: 13px;
            color: var(--g400);
            font-weight: 400;
        }
        .auth-form-box .form-heading p a {
            color: var(--black);
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .auth-form-box .form-heading p a:hover { color: var(--g600); }

        /* Fields */
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--g400);
            margin-bottom: 7px;
        }
        .field input,
        .field select,
        .field textarea {
            display: block;
            width: 100%;
            background: var(--white);
            border: 1px solid var(--g200);
            border-radius: 4px;
            padding: 10px 13px;
            font-size: 14px;
            color: var(--black);
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            transition: border-color .15s;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: var(--black);
        }
        .field input::placeholder,
        .field textarea::placeholder { color: var(--g300); }

        /* Kill autofill styling */
        .field input:-webkit-autofill,
        .field input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px var(--white) inset !important;
            -webkit-text-fill-color: var(--black) !important;
        }

        /* Select arrow */
        .field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23a0a0a0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 13px center;
        }

        .field textarea { resize: vertical; min-height: 80px; }
        .field-hint { font-size: 11px; color: var(--g400); margin-top: 5px; }

        /* Two-col */
        .field-row { display: flex; gap: 12px; }
        .field-row .field { flex: 1; }

        /* Extras row */
        .form-extras {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-extras label {
            font-size: 12px;
            color: var(--g400);
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .form-extras a {
            font-size: 12px;
            color: var(--black);
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .form-extras a:hover { color: var(--g600); }

        /* Primary button */
        .btn-auth {
            display: block;
            width: 100%;
            background: var(--black);
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .03em;
            border: none;
            border-radius: 4px;
            padding: 12px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-auth:hover { background: var(--g800); color: var(--white); }
        .btn-auth:active { background: var(--black); }

        /* Alerts */
        .alert-danger  { background: var(--g100); border: 1px solid var(--g200); color: #b00; border-radius: 4px; padding: 10px 13px; margin-bottom: 18px; font-size: 13px; }
        .alert-success { background: var(--g100); border: 1px solid var(--g200); color: var(--g600); border-radius: 4px; padding: 10px 13px; margin-bottom: 18px; font-size: 13px; }

        /* Checkbox row */
        .check-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px; }
        .check-row input[type=checkbox] { flex-shrink: 0; margin-top: 2px; accent-color: var(--black); width: 14px; height: 14px; }
        .check-row label { font-size: 12px; color: var(--g600); line-height: 1.5; cursor: pointer; font-weight: 400; text-transform: none; letter-spacing: 0; margin: 0; }
        .check-row label a { color: var(--black); font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }

        /* Bottom note */
        .auth-note { text-align: center; margin-top: 20px; font-size: 12px; color: var(--g400); }
        .auth-note a { color: var(--black); font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }

        /* Divider */
        .auth-divider { height: 1px; background: var(--g200); margin: 20px 0; }

        /* Mobile */
        @media (max-width: 767px) {
            .auth-left { display: none; }
            .auth-right { padding: 32px 20px; }
        }
    </style>
</head>
<body>
<div class="auth-split">

    
    <div class="auth-left" id="auth-left-panel">
        <?php echo $__env->yieldContent('panel-style'); ?>

        <div class="auth-topbar">
            <a href="<?php echo e(url('/')); ?>" class="auth-logo">
                <img src="<?php echo e(url('/images/logo.png')); ?>" alt="Tsoka"
                     onerror="this.style.display='none'">
            </a>
            <nav class="auth-nav">
                <a href="<?php echo e(route('admin.integrations.hub')); ?>">Integrations</a>
                <a href="<?php echo e(route('admin.concierge.index')); ?>">Concierge</a>
                <a href="<?php echo e(route('admin.tanova.index')); ?>">Tanova</a>
                <span class="auth-nav-soon">TourPay <span class="auth-soon-pill">Soon</span></span>
            </nav>
        </div>

        <div class="auth-brand-body">
            <?php echo $__env->yieldContent('panel-deco'); ?>
            <p class="auth-eyebrow">Travel Management Portal</p>
            <?php echo $__env->yieldContent('brand-heading'); ?>
            <?php echo $__env->yieldContent('brand-sub'); ?>
            <div class="auth-tags">
                <span class="auth-tag">EMEA</span>
                <span class="auth-tag">Multi-currency</span>
                <span class="auth-tag">Est. 2017</span>
            </div>
        </div>

        <div class="auth-left-foot">
            &copy; 2016 &ndash; <?php echo e(date('Y')); ?> Tsoka Travel. Managed by Peachpy Technologies.
        </div>
    </div>

    
    <div class="auth-right">
        <div class="auth-form-box">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

</div>
<script src="<?php echo e(url('/orion/vendor/jquery.min.js')); ?>"></script>
</body>
</html>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/resources/views/layouts/auth.blade.php ENDPATH**/ ?>