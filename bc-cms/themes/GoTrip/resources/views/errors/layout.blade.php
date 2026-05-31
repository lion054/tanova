<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') — Tsoka Travel Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a1628 0%, #0d2b1f 40%, #1a3a28 70%, #0f2318 100%);
            color: #1a1a1a;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative background blobs */
        body::before {
            content: '';
            position: fixed;
            top: -20%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(212,160,23,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(27,67,50,0.4) 0%, transparent 70%);
            pointer-events: none;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 56px 48px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 80px rgba(0,0,0,0.5), 0 4px 20px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 36px;
        }
        .brand-mark {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #1b4332, #2d6a4f);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #0d2b1f;
            letter-spacing: -0.3px;
        }
        .brand-name span {
            color: #d4a017;
        }

        .error-icon {
            font-size: 52px;
            margin-bottom: 16px;
            display: block;
        }

        .error-code {
            font-size: 80px;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #d4a017, #f0c040);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 22px;
            font-weight: 600;
            color: #0d2b1f;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .error-message {
            font-size: 15px;
            color: #5a6772;
            line-height: 1.65;
            margin-bottom: 36px;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 28px;
            background: linear-gradient(135deg, #1b4332, #2d6a4f);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(27,67,50,0.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27,67,50,0.4);
            color: #fff;
            text-decoration: none;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 24px;
            background: transparent;
            color: #1b4332;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 10px;
            border: 1.5px solid #d0d8d0;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }
        .btn-secondary:hover {
            border-color: #1b4332;
            background: #f0f7f0;
            color: #1b4332;
            text-decoration: none;
        }

        .divider {
            height: 1px;
            background: #edf0ed;
            margin: 32px 0;
        }

        .footer-note {
            font-size: 12px;
            color: #9ba8a0;
        }
        .footer-note a {
            color: #1b4332;
            text-decoration: none;
            font-weight: 500;
        }
        .footer-note a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .card { padding: 40px 28px; }
            .error-code { font-size: 64px; }
        }
    </style>
    @yield('extra_styles')
</head>
<body>
    <div class="card">
        <div class="brand">
            <img src="/uploads/gotrip/general/logo-dark.png"
                 onerror="this.style.display='none';document.getElementById('brand-fallback').style.display='flex';"
                 alt="Tsoka Travel" style="height:40px;max-width:180px;object-fit:contain;">
            <div id="brand-fallback" style="display:none;align-items:center;gap:10px;">
                <div class="brand-mark">🌍</div>
                <div class="brand-name">Tsoka<span>Travel</span></div>
            </div>
        </div>

        <span class="error-icon">@yield('icon', '⚠️')</span>
        <div class="error-code">@yield('code', '')</div>
        <h1 class="error-title">@yield('title', 'Something went wrong')</h1>
        <p class="error-message">@yield('message', 'An unexpected error occurred. Please try again.')</p>

        <div class="actions">
            @yield('actions')
        </div>

        <div class="divider"></div>
        <p class="footer-note">
            Need help? <a href="mailto:support@tsokatravel.com">Contact support</a> &nbsp;·&nbsp;
            <a href="/">Tsoka Travel Portal</a>
        </p>
    </div>
</body>
</html>
