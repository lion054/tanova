<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tsoka Vendor API · Swagger</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
<style>body{margin:0;background:#fff}.topbar-note{font:14px system-ui,sans-serif;padding:10px 20px;border-bottom:1px solid #e4e6ea}.topbar-note a{color:#111}</style>
</head>
<body>
<div class="topbar-note">Tsoka Vendor API · <a href="{{ $specUrl }}">openapi.json</a> · <a href="{{ $specUrl }}?download=1">download</a> · <a href="{{ preg_replace('#/openapi.json$#', '/docs', $specUrl) }}">guides and reference</a> · Use a <code>sk_test_</code> key under <b>Authorize</b>.</div>
<div id="swagger"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
window.ui = SwaggerUIBundle({ url: @json($specUrl), dom_id: '#swagger', deepLinking: true, docExpansion: 'none', tagsSorter: 'alpha', filter: true, persistAuthorization: true });
</script>
</body>
</html>
