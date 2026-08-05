<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market Eye Developer API</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=IBM+Plex+Mono:wght@400;500&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --blue:#1E3A5F; --green:#2ECC71; --orange:#FF6B35; --bg:#0F172A; --panel:#111827; --text:#E5E7EB; --muted:#94A3B8; --line:#1F2937; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Outfit,system-ui,sans-serif; background:var(--bg); color:var(--text); }
        .wrap { width:min(980px, calc(100% - 32px)); margin:0 auto; padding:28px 0 64px; }
        a { color:#86EFAC; text-decoration:none; }
        .brand { font-family:Fraunces,Georgia,serif; font-size:1.5rem; color:#fff; text-decoration:none; }
        .site-nav {
            display:flex; justify-content:space-between; gap:12px; align-items:center;
            margin-bottom:36px; position:relative; z-index:40;
        }
        .nav-toggle {
            display:none; width:44px; height:44px; border-radius:10px;
            border:1px solid #334155; background:#0B1220; color:#E5E7EB;
            align-items:center; justify-content:center; cursor:pointer; padding:0;
        }
        .nav-toggle span {
            display:block; width:18px; height:2px; background:#E5E7EB; position:relative;
        }
        .nav-toggle span::before, .nav-toggle span::after {
            content:''; position:absolute; left:0; width:18px; height:2px; background:#E5E7EB;
        }
        .nav-toggle span::before { top:-6px; }
        .nav-toggle span::after { top:6px; }
        .nav-toggle[aria-expanded="true"] span { background:transparent; }
        .nav-toggle[aria-expanded="true"] span::before { top:0; transform:rotate(45deg); }
        .nav-toggle[aria-expanded="true"] span::after { top:0; transform:rotate(-45deg); }
        .nav-links { display:flex; flex-wrap:wrap; gap:10px 18px; align-items:center; justify-content:flex-end; }
        .nav-links a { color:#CBD5E1; font-weight:600; font-size:.92rem; }
        .nav-links a:hover { color:#fff; }
        .nav-backdrop { display:none; position:fixed; inset:0; background:rgba(2,6,23,.55); z-index:30; }
        .nav-backdrop.is-open { display:block; }
        h1 { font-family:Fraunces,Georgia,serif; font-size:clamp(2rem,4vw,3rem); letter-spacing:-0.03em; margin:0 0 12px; }
        .lead { color:var(--muted); font-size:1.05rem; line-height:1.55; max-width:40rem; margin:0 0 28px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:22px; margin-bottom:18px; }
        h2 { margin:0 0 10px; font-size:1.2rem; }
        h3 { margin:18px 0 8px; font-size:1rem; color:#F8FAFC; }
        p, li { color:var(--muted); line-height:1.55; }
        code, pre { font-family:"IBM Plex Mono", ui-monospace, monospace; }
        pre {
            background:#0B1220; border:1px solid var(--line); border-radius:12px;
            padding:16px; overflow:auto; color:#D1FAE5; font-size:.86rem; line-height:1.55;
        }
        .pill { display:inline-block; padding:4px 10px; border-radius:999px; border:1px solid #334155; color:#CBD5E1; font-size:.75rem; font-weight:700; margin-right:6px; }
        table { width:100%; border-collapse:collapse; font-size:.92rem; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--line); vertical-align:top; }
        th { color:#94A3B8; font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; }
        .endpoint { color:#FDE68A; word-break:break-all; }
        .note { font-size:.9rem; }
        .url-label { display:block; font-size:.75rem; letter-spacing:.06em; text-transform:uppercase; color:#64748B; margin:12px 0 6px; font-weight:700; }
        @media (max-width: 768px) {
            .nav-toggle { display:inline-flex; }
            .nav-links {
                display:none; position:absolute; top:calc(100% + 8px); left:0; right:0;
                flex-direction:column; align-items:stretch; gap:4px;
                background:#0B1220; border:1px solid #334155; border-radius:14px;
                padding:10px; z-index:40;
            }
            .nav-links.is-open { display:flex; }
            .nav-links a {
                padding:12px 14px; border-radius:10px; min-height:44px;
                display:flex; align-items:center;
            }
            .card { padding:18px; }
            table, thead, tbody, th, td, tr { display:block; }
            thead { display:none; }
            tr { border-bottom:1px solid var(--line); padding:12px 0; }
            td { border:none; padding:4px 0; }
            td:first-child { margin-bottom:4px; }
        }
    </style>
</head>
<body>
<div class="nav-backdrop" id="navBackdrop" hidden></div>
<div class="wrap">
    <nav class="site-nav" aria-label="Developer docs">
        <a class="brand" href="{{ route('home') }}">Market Eye</a>
        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Open menu">
            <span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('developers.swagger') }}">Swagger UI</a>
            <a href="{{ route('developers.openapi') }}">OpenAPI</a>
            <a href="{{ route('developers.postman') }}">Postman</a>
            <a href="{{ route('developer.register') }}">Get API key</a>
        </div>
    </nav>

    <h1>Developer API</h1>
    <p class="lead">
        Market Eye is the standard for crowd-verified Nigerian market prices.
        Authenticate with an API key, respect daily limits, and use the same units shoppers use: kg, mudu, bag, paint, congo, derica.
    </p>

    <div class="card">
        <h2>Base URL</h2>
        <span class="url-label">This instance (from APP_URL)</span>
        <pre>{{ $baseUrl }}</pre>
        <span class="url-label">Production (canonical)</span>
        <pre>{{ $productionBaseUrl }}</pre>
        <p class="note">Prefer the production URL in external apps. Paths below are relative to the Base URL (they already include <code>/api/v1/public</code>).</p>
        <p>Send your key on every request:</p>
        <pre>X-API-Key: me_your_key_here
# or
Authorization: Bearer me_your_key_here</pre>
        <p>
            Default daily limit: <strong style="color:#fff;">{{ number_format($defaultDailyLimit) }}</strong> requests per key
            (admins can raise this). Responses include <code>X-RateLimit-Limit</code>,
            <code>X-RateLimit-Remaining</code>, and <code>X-RateLimit-Reset</code>. Over limit returns <code>429</code>.
        </p>
        <p class="note">Browser clients from other origins are allowed (CORS on <code>api/*</code>). Rate-limit headers are exposed to JS.</p>
    </div>

    <div class="card">
        <h2>Endpoints</h2>
        <table>
            <thead>
            <tr><th>Method</th><th>Path</th><th>Description</th></tr>
            </thead>
            <tbody>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/markets</td><td>List active markets. No query params. Full list (not paginated).</td></tr>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/categories</td><td>Goods categories. No query params. Full list.</td></tr>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/products</td><td>Catalogue with measurement units. Full list.</td></tr>
            <tr>
                <td><span class="pill">GET</span></td>
                <td class="endpoint">/markets/{id}/prices</td>
                <td>
                    Latest prices for a market.
                    Optional <code>?category=</code> (category <strong>slug</strong> only) and <code>?search=</code> (product name).
                    Full filtered list (not paginated).
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Pagination &amp; filtering</h2>
        <p>
            <strong style="color:#fff;">None of these public endpoints are paginated.</strong>
            There is no <code>page</code> / <code>limit</code> / <code>cursor</code> today — each response returns the full matching collection.
        </p>
        <table>
            <thead>
            <tr><th>Endpoint</th><th>Query parameters</th></tr>
            </thead>
            <tbody>
            <tr><td class="endpoint">/markets</td><td>None</td></tr>
            <tr><td class="endpoint">/categories</td><td>None</td></tr>
            <tr><td class="endpoint">/products</td><td>None</td></tr>
            <tr>
                <td class="endpoint">/markets/{id}/prices</td>
                <td>
                    <code>category</code> — category slug (e.g. <code>grains</code>), not numeric id<br>
                    <code>search</code> — case-insensitive product name contains match
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Response envelope</h2>
        <p>Successful responses use:</p>
        <pre>{
  "success": true,
  "message": "OK",
  "data": { }
}</pre>
    </div>

    <div class="card">
        <h2>GET /markets — response shape</h2>
        <pre>{
  "success": true,
  "message": "OK",
  "data": {
    "markets": [
      {
        "id": 1,
        "name": "Wuse Market",
        "area": "Wuse",
        "city": "Abuja",
        "state": "FCT",
        "lat": 9.0765,
        "lng": 7.3986,
        "description": "..."
      }
    ],
    "meta": {
      "units": ["kg", "mudu", "paint", "bag", "congo", "piece", "litre", "derica"],
      "currency": "NGN"
    }
  }
}</pre>
    </div>

    <div class="card">
        <h2>GET /categories — response shape</h2>
        <pre>{
  "success": true,
  "message": "OK",
  "data": {
    "categories": [
      {
        "id": 2,
        "name": "Grains",
        "slug": "grains",
        "icon": "grain",
        "product_count": 12
      }
    ]
  }
}</pre>
    </div>

    <div class="card">
        <h2>GET /products — response shape</h2>
        <pre>{
  "success": true,
  "message": "OK",
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Rice (local)",
        "slug": "rice-local",
        "unit": "50kg bag",
        "description": "...",
        "category": {
          "id": 2,
          "name": "Grains",
          "slug": "grains"
        }
      }
    ]
  }
}</pre>
    </div>

    <div class="card">
        <h2>GET /markets/{id}/prices — response shape</h2>
        <pre>curl -s "{{ $productionBaseUrl }}/markets/1/prices?category=grains&search=rice" \
  -H "X-API-Key: me_your_key_here"</pre>
        <pre>{
  "success": true,
  "message": "OK",
  "data": {
    "market": {
      "id": 1,
      "name": "Wuse Market",
      "area": "Wuse",
      "city": "Abuja",
      "state": "FCT"
    },
    "prices": [
      {
        "product": { "id": 1, "name": "Rice (local)", "slug": "rice-local", "unit": "50kg bag" },
        "category": { "id": 2, "name": "Grains", "slug": "grains" },
        "market": { "id": 1, "name": "Wuse Market", "area": "Wuse" },
        "price": { "avg": 78500, "min": 76000, "max": 81000, "currency": "NGN" },
        "measurement": {
          "unit": "50kg bag",
          "note": "Units reflect how Nigerians buy goods in open markets (kg, mudu, bag, paint, etc.)."
        },
        "quality": {
          "submission_count": 12,
          "low_confidence": false,
          "confidence_level": "high",
          "is_stale": false
        },
        "updated_at": "2026-07-24"
      }
    ]
  }
}</pre>
        <p class="note"><code>confidence_level</code> is one of <code>high</code>, <code>medium</code>, <code>low</code>, <code>stale</code>.</p>
    </div>

    <div class="card">
        <h2>Errors</h2>
        <p>Errors use the same top-level shape (<code>success</code> / <code>message</code>), not an <code>error.code</code> object.</p>

        <h3>401 — missing API key</h3>
        <pre>{
  "success": false,
  "message": "API key required. Pass X-API-Key or Authorization: Bearer me_…"
}</pre>

        <h3>401 — invalid or inactive key</h3>
        <pre>{
  "success": false,
  "message": "Invalid or inactive API key."
}</pre>

        <h3>404 — market not found</h3>
        <pre>{
  "success": false,
  "message": "Market not found.",
  "errors": {}
}</pre>

        <h3>429 — daily rate limit</h3>
        <pre>{
  "success": false,
  "message": "Daily API request limit reached.",
  "limit": {{ (int) $defaultDailyLimit }},
  "resets_at": "2026-08-05T23:59:59+00:00"
}</pre>
        <p class="note">Also returned on the response: <code>X-RateLimit-Limit</code>, <code>X-RateLimit-Remaining: 0</code>, <code>X-RateLimit-Reset</code> (unix timestamp, end of UTC day).</p>

        <h3>422 — validation (when applicable)</h3>
        <pre>{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field": ["The field is required."]
  }
}</pre>
    </div>

    <div class="card">
        <h2>OpenAPI &amp; tooling</h2>
        <p>
            Interactive docs: <a href="{{ route('developers.swagger') }}">Swagger UI</a> ·
            Machine-readable: <a href="{{ route('developers.openapi') }}">openapi.json</a> ·
            Import into Postman: <a href="{{ route('developers.postman') }}">collection JSON</a>
        </p>
    </div>

    <div class="card">
        <h2>Quickstart examples</h2>
        <h3>cURL</h3>
        <pre>curl -s "{{ $productionBaseUrl }}/markets" \
  -H "X-API-Key: me_your_key_here"</pre>
        <h3>Node.js</h3>
        <pre>const base = "{{ $productionBaseUrl }}";
const res = await fetch(`${base}/markets/1/prices`, {
  headers: { "X-API-Key": process.env.MARKETEYE_API_KEY }
});
const json = await res.json();
if (!json.success) throw new Error(json.message);
console.log(json.data.prices[0].price.avg);
console.log(json.data.prices[0].measurement.unit);</pre>
        <h3>Python</h3>
        <pre>import os, requests

base = "{{ $productionBaseUrl }}"
r = requests.get(
    f"{base}/markets/1/prices",
    headers={"X-API-Key": os.environ["MARKETEYE_API_KEY"]},
    timeout=20,
)
r.raise_for_status()
body = r.json()
assert body["success"]
prices = body["data"]["prices"]
print(prices[0]["price"]["avg"], prices[0]["measurement"]["unit"])</pre>
    </div>

    <div class="card">
        <h2>Nigerian measurement conventions</h2>
        <ul>
            <li><strong>kg</strong> — weighed goods (tomato, onion, meat)</li>
            <li><strong>mudu / congo / derica</strong> — bowl measures for grains &amp; beans</li>
            <li><strong>bag</strong> — e.g. 50kg rice, 100kg maize</li>
            <li><strong>paint</strong> — paint-bucket volume for garri, beans, etc.</li>
            <li><strong>piece / litre</strong> — countable or liquid items</li>
        </ul>
        <p>Create a free developer account and generate keys at <a href="{{ route('developer.register') }}">/developer/register</a>.</p>
    </div>
</div>
<script>
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
        document.body.style.overflow = open ? 'hidden' : '';
    };
    toggle.addEventListener('click', () => setOpen(!links.classList.contains('is-open')));
    backdrop?.addEventListener('click', () => setOpen(false));
    links.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
    window.addEventListener('resize', () => { if (window.innerWidth > 768) setOpen(false); });
})();
</script>
</body>
</html>
