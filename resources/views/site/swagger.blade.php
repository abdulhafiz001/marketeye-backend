<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market Eye — OpenAPI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #0f172a; }
        .top {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding: 14px 20px; background: #111827; border-bottom: 1px solid #1f2937; color: #e5e7eb;
            font-family: Outfit, system-ui, sans-serif;
        }
        .top a { color: #86efac; text-decoration: none; font-weight: 700; }
        #swagger-ui { max-width: 1200px; margin: 0 auto; }
    </style>
</head>
<body>
<div class="top">
    <strong>Market Eye Public API — Swagger UI</strong>
    <div>
        <a href="{{ route('developers') }}">Docs</a>
        &nbsp;·&nbsp;
        <a href="{{ route('developers.openapi') }}">openapi.json</a>
        &nbsp;·&nbsp;
        <a href="{{ route('developers.postman') }}">Postman</a>
    </div>
</div>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script>
  window.ui = SwaggerUIBundle({
    url: @json($specUrl),
    dom_id: '#swagger-ui',
    deepLinking: true,
    presets: [SwaggerUIBundle.presets.apis],
  });
</script>
</body>
</html>
