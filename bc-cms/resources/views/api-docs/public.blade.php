<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tsoka Vendor API</title>
<meta name="description" content="Everything a vendor can do in the Tsoka portal, over HTTP: listings, seats, bookings, guests, payments, loyalty, waitlist, invoices, messages and reports.">
<style>html,body{margin:0;background:#fff;color:#111;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}.wrap{max-width:1280px;margin:0 auto;padding:20px 16px 60px}.top{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:18px}.top h1{margin:0;font-size:22px}.top span{color:#5b6068;font-size:14px}</style>
</head>
<body>
<div class="wrap">
    <div class="top"><h1>Tsoka Vendor API</h1><span>Version {{ \App\Http\Middleware\ApiVersion::CURRENT }} · <a href="{{ $apiBase }}/openapi.json">OpenAPI</a> · <a href="{{ $apiBase }}/postman.json">Postman</a></span></div>
    @include('api-docs.reference', ['base' => url('/api/v/docs')])
</div>
</body>
</html>
