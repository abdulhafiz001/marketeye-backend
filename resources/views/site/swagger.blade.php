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
            padding: 12px 16px; background: #111827; border-bottom: 1px solid #1f2937; color: #e5e7eb;
            font-family: Outfit, system-ui, sans-serif; position: relative; z-index: 40;
        }
        .top strong { font-size: clamp(.9rem, 2.5vw, 1rem); }
        .top a { color: #86efac; text-decoration: none; font-weight: 700; }
        .nav-toggle {
            display: none; width: 44px; height: 44px; border-radius: 10px;
            border: 1px solid #334155; background: #0b1220; color: #e5e7eb;
            align-items: center; justify-content: center; cursor: pointer; padding: 0; flex: none;
        }
        .nav-toggle span {
            display: block; width: 18px; height: 2px; background: #e5e7eb; position: relative;
        }
        .nav-toggle span::before, .nav-toggle span::after {
            content: ''; position: absolute; left: 0; width: 18px; height: 2px; background: #e5e7eb;
        }
        .nav-toggle span::before { top: -6px; }
        .nav-toggle span::after { top: 6px; }
        .nav-toggle[aria-expanded="true"] span { background: transparent; }
        .nav-toggle[aria-expanded="true"] span::before { top: 0; transform: rotate(45deg); }
        .nav-toggle[aria-expanded="true"] span::after { top: 0; transform: rotate(-45deg); }
        .top-links { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; justify-content: flex-end; }
        .nav-backdrop { display: none; position: fixed; inset: 0; background: rgba(2,6,23,.55); z-index: 30; }
        .nav-backdrop.is-open { display: block; }
        #swagger-ui { max-width: 1200px; margin: 0 auto; padding: 0 8px 24px; }
        @media (max-width: 768px) {
            .nav-toggle { display: inline-flex; }
            .top-links {
                display: none; position: absolute; top: calc(100% + 8px); left: 12px; right: 12px;
                flex-direction: column; align-items: stretch; gap: 4px;
                background: #0b1220; border: 1px solid #334155; border-radius: 14px;
                padding: 10px; z-index: 40;
            }
            .top-links.is-open { display: flex; }
            .top-links a {
                padding: 12px 14px; border-radius: 10px; min-height: 44px;
                display: flex; align-items: center;
            }
        }
    </style>
</head>
<body>
<div class="nav-backdrop" id="navBackdrop" hidden></div>
<div class="top">
    <strong>Market Eye Public API — Swagger UI</strong>
    <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Open menu">
        <span></span>
    </button>
    <div class="top-links" id="navLinks">
        <a href="{{ route('developers') }}">Docs</a>
        <a href="{{ route('developers.openapi') }}">openapi.json</a>
        <a href="{{ route('developers.postman') }}">Postman</a>
        <a href="{{ route('home') }}">Home</a>
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

  (function () {
    const toggle = document.getElementById('navToggle');
    const links = document.getElementById('navLinks');
    const backdrop = document.getElementById('navBackdrop');
    if (!toggle || !links) return;
    const setOpen = (open) => {
      links.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      if (backdrop) {
        backdrop.classList.toggle('is-open', open);
        backdrop.hidden = !open;
      }
    };
    toggle.addEventListener('click', () => setOpen(!links.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setOpen(false));
    links.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
    window.addEventListener('resize', () => { if (window.innerWidth > 768) setOpen(false); });
  })();
</script>
</body>
</html>
