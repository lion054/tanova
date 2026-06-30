<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Tsoka Travel'))</title>
    <link rel="icon" type="image/png" href="{{ url('/images/tanova/tanova-mark-black.png') }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black:    #0a0a0a;
            --white:    #ffffff;
            --g100:     #f6f6f5;
            --g200:     #e8e8e6;
            --g300:     #d0d0cd;
            --g400:     #9a9a96;
            --g600:     #5a5a56;
            --g800:     #222222;
            --panel:    #0d0c0b;
            /* Savanna-gold accent */
            --accent:   #d8a24a;
            --accent-2: #e6b765;
            --accent-ink: #7a5a1e;
            --ring:     rgba(216,162,74,.35);
            --radius:   8px;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 15px;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            color: var(--black);
            background: var(--white);
        }

        /* ── Split layout ─────────────────────────────────────── */
        .auth-split { display: flex; min-height: 100vh; width: 100%; }

        /* ── Left panel ───────────────────────────────────────── */
        .auth-left {
            width: 46%;
            flex-shrink: 0;
            background: var(--panel);
            display: flex;
            flex-direction: column;
            padding: 38px 52px;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 0;
        }
        /* Warm radial glow + fine grain for depth */
        .auth-left::after {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(120% 80% at 80% 0%, rgba(216,162,74,.22) 0%, rgba(216,162,74,0) 55%),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
            z-index: 0;
            pointer-events: none;
        }
        .auth-left > * { position: relative; z-index: 1; }

        /* Top bar */
        .auth-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .auth-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .auth-logo img { height: 30px; width: auto; display: block; }
        .auth-logo .brand-divider { width: 1px; height: 22px; background: rgba(255,255,255,.16); }
        .auth-logo .powered {
            display: flex; flex-direction: column; align-items: flex-start; line-height: 1.1;
        }
        .auth-logo .powered small {
            font-size: 8px; font-weight: 600; letter-spacing: .16em; text-transform: uppercase;
            color: rgba(255,255,255,.35);
        }
        .auth-logo .powered img { height: 18px; width: auto; margin-top: 3px; opacity: .95; flex-shrink: 0; object-fit: contain; }

        /* Nav */
        .auth-nav { display: flex; align-items: center; gap: 2px; }
        .auth-nav a {
            font-size: 12px; font-weight: 400; color: rgba(255,255,255,.55);
            text-decoration: none; padding: 6px 11px; border-radius: 6px;
            letter-spacing: .01em; transition: color .18s, background .18s;
        }
        .auth-nav a:hover { color: #fff; background: rgba(255,255,255,.06); }
        .auth-nav-soon {
            display: inline-flex; align-items: center; gap: 6px; font-size: 12px;
            color: rgba(255,255,255,.32); padding: 6px 11px; cursor: default;
        }
        .auth-soon-pill {
            font-size: 9px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
            border: 1px solid rgba(255,255,255,.2); color: rgba(255,255,255,.45);
            border-radius: 4px; padding: 1px 5px;
        }

        /* Brand body */
        .auth-brand-body { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 48px 0 32px; }

        /* AI-powered badge */
        .auth-ai-badge {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 6px 12px 6px 10px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(216,162,74,.32);
            border-radius: 100px;
            width: fit-content;
            margin-bottom: 26px;
            backdrop-filter: blur(6px);
        }
        .auth-ai-badge img { height: 14px; width: auto; }
        .auth-ai-badge span {
            font-size: 10px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
            color: var(--accent-2);
        }
        .auth-ai-badge .dot {
            width: 6px; height: 6px; border-radius: 50%; background: var(--accent-2);
            box-shadow: 0 0 0 0 rgba(230,183,101,.6);
            animation: pulse 2.6s infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(230,183,101,.55); }
            70%  { box-shadow: 0 0 0 7px rgba(230,183,101,0); }
            100% { box-shadow: 0 0 0 0 rgba(230,183,101,0); }
        }

        .auth-eyebrow {
            font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase;
            color: rgba(255,255,255,.42); margin-bottom: 18px;
        }
        .auth-brand-body h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(42px, 4.6vw, 60px);
            font-weight: 400; line-height: 1.05; letter-spacing: -.02em;
            color: #fff; margin-bottom: 20px;
        }
        .auth-brand-body h1 em { font-style: italic; color: var(--accent-2); }
        .auth-brand-body p {
            font-size: 14px; line-height: 1.7; color: rgba(255,255,255,.5);
            max-width: 330px; font-weight: 400;
        }

        /* Tags */
        .auth-tags { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 38px; }
        .auth-tag {
            font-size: 10px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase;
            color: rgba(255,255,255,.5); padding: 5px 11px;
            border: 1px solid rgba(255,255,255,.13); border-radius: 100px;
            transition: border-color .18s, color .18s;
        }
        .auth-tag:hover { border-color: rgba(216,162,74,.5); color: var(--accent-2); }

        /* Footer */
        .auth-left-foot {
            border-top: 1px solid rgba(255,255,255,.07); padding-top: 20px;
            font-size: 11px; color: rgba(255,255,255,.28); letter-spacing: .01em;
        }

        /* ── Right panel ──────────────────────────────────────── */
        .auth-right {
            width: 54%; flex-shrink: 0; background: var(--white);
            display: flex; align-items: center; justify-content: center;
            padding: 48px 40px; position: relative;
        }
        .auth-form-box {
            width: 100%; max-width: 384px;
            animation: rise .55s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

        /* Big Tanova logo above the form (all viewports) */
        .auth-form-brand { margin-bottom: 28px; }
        .auth-form-brand img { height: 52px; width: auto; display: block; }

        /* Form heading */
        .auth-form-box .form-heading { margin-bottom: 28px; }
        .auth-form-box .form-heading h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 30px; font-weight: 400; letter-spacing: -.02em;
            color: var(--black); margin-bottom: 6px;
        }
        .auth-form-box .form-heading p { font-size: 13px; color: var(--g400); font-weight: 400; }
        .auth-form-box .form-heading p a {
            color: var(--accent-ink); font-weight: 600; text-decoration: none;
            border-bottom: 1px solid var(--ring); transition: color .15s, border-color .15s;
        }
        .auth-form-box .form-heading p a:hover { color: var(--accent); border-color: var(--accent); }

        /* Fields */
        .field { margin-bottom: 18px; }
        .field label {
            display: block; font-size: 10px; font-weight: 600; letter-spacing: .1em;
            text-transform: uppercase; color: var(--g400); margin-bottom: 8px;
        }
        .field input, .field select, .field textarea {
            display: block; width: 100%; background: var(--white);
            border: 1px solid var(--g200); border-radius: var(--radius);
            padding: 12px 14px; font-size: 14px; color: var(--black);
            font-family: 'Inter', sans-serif; font-weight: 400;
            transition: border-color .18s, box-shadow .18s; outline: none;
            -webkit-appearance: none; appearance: none;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            border-color: var(--accent); box-shadow: 0 0 0 4px var(--ring);
        }
        .field input::placeholder, .field textarea::placeholder { color: var(--g300); }
        .field input:-webkit-autofill, .field input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px var(--white) inset !important;
            -webkit-text-fill-color: var(--black) !important;
        }
        .field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23a0a0a0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 13px center;
        }
        .field textarea { resize: vertical; min-height: 80px; }
        .field-hint { font-size: 11px; color: var(--g400); margin-top: 5px; }
        .field-row { display: flex; gap: 12px; }
        .field-row .field { flex: 1; }

        /* Input with leading icon + trailing action */
        .input-wrap { position: relative; }
        .input-wrap .lead-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            width: 17px; height: 17px; color: var(--g400); pointer-events: none;
        }
        .input-wrap.has-icon input { padding-left: 40px; }
        .input-wrap .toggle-pw {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            background: none; border: none; padding: 7px; cursor: pointer;
            color: var(--g400); display: inline-flex; border-radius: 6px;
            transition: color .15s, background .15s;
        }
        .input-wrap .toggle-pw:hover { color: var(--black); background: var(--g100); }
        .input-wrap.has-toggle input { padding-right: 42px; }

        /* Extras row */
        .form-extras { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
        .form-extras label {
            font-size: 12px; color: var(--g600); font-weight: 400; text-transform: none;
            letter-spacing: 0; margin: 0; cursor: pointer; display: flex; align-items: center; gap: 8px;
        }
        .form-extras input[type=checkbox] { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; }
        .form-extras a {
            font-size: 12px; color: var(--accent-ink); font-weight: 600; text-decoration: none;
            border-bottom: 1px solid var(--ring); transition: color .15s, border-color .15s;
        }
        .form-extras a:hover { color: var(--accent); border-color: var(--accent); }

        /* Primary button */
        .btn-auth {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            width: 100%; background: var(--black); color: var(--white);
            font-family: 'Inter', sans-serif; font-size: 13.5px; font-weight: 600;
            letter-spacing: .02em; border: none; border-radius: var(--radius);
            padding: 14px; cursor: pointer; text-align: center; text-decoration: none;
            transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s, background .18s;
        }
        .btn-auth svg { width: 16px; height: 16px; transition: transform .18s; }
        .btn-auth:hover { background: #1c1c1c; transform: translateY(-1px); box-shadow: 0 10px 24px -10px rgba(216,162,74,.6); }
        .btn-auth:hover svg { transform: translateX(3px); }
        .btn-auth:active { transform: translateY(0); }

        /* Alerts */
        .alert-danger  { background: #fdf3f2; border: 1px solid #f4d9d6; color: #b3261e; border-radius: var(--radius); padding: 11px 14px; margin-bottom: 18px; font-size: 13px; }
        .alert-success { background: #f1f8f2; border: 1px solid #d4e9d7; color: #2f6b3a; border-radius: var(--radius); padding: 11px 14px; margin-bottom: 18px; font-size: 13px; }

        /* Checkbox row */
        .check-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px; }
        .check-row input[type=checkbox] { flex-shrink: 0; margin-top: 2px; accent-color: var(--accent); width: 14px; height: 14px; }
        .check-row label { font-size: 12px; color: var(--g600); line-height: 1.5; cursor: pointer; font-weight: 400; text-transform: none; letter-spacing: 0; margin: 0; }
        .check-row label a { color: var(--accent-ink); font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }

        .auth-note { text-align: center; margin-top: 20px; font-size: 12px; color: var(--g400); }
        .auth-note a { color: var(--accent-ink); font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }
        .auth-divider { height: 1px; background: var(--g200); margin: 20px 0; }

        /* Trust footer in form panel */
        .auth-trust {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 30px; padding-top: 22px; border-top: 1px solid var(--g200);
        }
        .auth-trust img { height: 20px; width: auto; opacity: .9; flex-shrink: 0; object-fit: contain; }
        .auth-trust span { font-size: 11px; color: var(--g400); letter-spacing: .02em; }
        .auth-trust strong { color: var(--g600); font-weight: 600; }

        /* Mobile */
        @media (max-width: 860px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; padding: 40px 22px; }
            .auth-form-brand img { height: 46px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>
<div class="auth-split">

    {{-- Left panel --}}
    <div class="auth-left" id="auth-left-panel">
        @yield('panel-style')

        <div class="auth-topbar">
            <a href="{{ url('/') }}" class="auth-logo">
                <img src="{{ url('/images/tanova/tanova-white.png') }}" alt="Tanova"
                     onerror="this.style.display='none'">
                <span class="brand-divider"></span>
                <span class="powered">
                    <small>Powered by</small>
                    <img src="{{ url('/images/logo.png') }}" alt="Tsoka Travel"
                         onerror="this.parentElement.style.display='none'">
                </span>
            </a>
            <nav class="auth-nav">
                @if(Route::has('admin.integrations.hub'))
                    <a href="{{ route('admin.integrations.hub') }}">Integrations</a>
                @endif
                @if(Route::has('admin.concierge.index'))
                    <a href="{{ route('admin.concierge.index') }}">Concierge</a>
                @endif
                @if(Route::has('admin.tanova.index'))
                    <a href="{{ route('admin.tanova.index') }}">Tanova</a>
                @endif
                <span class="auth-nav-soon">TourPay <span class="auth-soon-pill">Soon</span></span>
            </nav>
        </div>

        <div class="auth-brand-body">
            @yield('panel-deco')
            <div class="auth-ai-badge">
                <span class="dot"></span>
                <img src="{{ url('/images/tanova/tanova-mark.png') }}" alt=""
                     onerror="this.style.display='none'">
                <span>Tanova AI Inside</span>
            </div>
            <p class="auth-eyebrow">Travel Management Portal</p>
            @yield('brand-heading')
            @yield('brand-sub')
            <div class="auth-tags">
                <span class="auth-tag">EMEA</span>
                <span class="auth-tag">Multi-currency</span>
                <span class="auth-tag">Est. 2017</span>
            </div>
        </div>

        <div class="auth-left-foot">
            &copy; 2016 &ndash; {{ date('Y') }} Tsoka Travel &middot; A Tanova platform &middot; Managed by Peachpy Technologies.
        </div>
    </div>

    {{-- Right panel --}}
    <div class="auth-right">
        <div class="auth-form-box">
            {{-- Big Tanova logo above the form --}}
            <div class="auth-form-brand">
                <a href="{{ url('/') }}">
                    <img src="{{ url('/images/tanova/tanova-black.png') }}" alt="Tanova"
                         onerror="this.src='{{ url('/images/logo.png') }}'">
                </a>
            </div>

            @yield('content')

            <div class="auth-trust">
                <span>Secured &amp; orchestrated by</span>
                <img src="{{ url('/images/logo.png') }}" alt="Tsoka Travel"
                     onerror="this.outerHTML='<strong>Tsoka</strong>'">
            </div>
        </div>
    </div>

</div>
<script src="{{ url('/orion/vendor/jquery.min.js') }}"></script>
</body>
</html>
