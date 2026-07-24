<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market Eye Developer API</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=IBM+Plex+Mono:wght@400;500&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --blue:#1E3A5F; --green:#2ECC71; --orange:#FF6B35; --bg:#0F172A; --panel:#111827; --text:#E5E7EB; --muted:#94A3B8; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Outfit,system-ui,sans-serif; background:var(--bg); color:var(--text); }
        .wrap { width:min(980px, calc(100% - 32px)); margin:0 auto; padding:28px 0 64px; }
        a { color:#86EFAC; text-decoration:none; }
        .brand { font-family:Fraunces,Georgia,serif; font-size:1.5rem; color:#fff; }
        nav { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:36px; }
        h1 { font-family:Fraunces,Georgia,serif; font-size:clamp(2rem,4vw,3rem); letter-spacing:-0.03em; margin:0 0 12px; }
        .lead { color:var(--muted); font-size:1.05rem; line-height:1.55; max-width:40rem; margin:0 0 28px; }
        .card { background:var(--panel); border:1px solid #1F2937; border-radius:18px; padding:22px; margin-bottom:18px; }
        h2 { margin:0 0 10px; font-size:1.2rem; }
        p, li { color:var(--muted); line-height:1.55; }
        code, pre { font-family:"IBM Plex Mono", ui-monospace, monospace; }
        pre {
            background:#0B1220; border:1px solid #1F2937; border-radius:12px;
            padding:16px; overflow:auto; color:#D1FAE5; font-size:.86rem; line-height:1.55;
        }
        .pill { display:inline-block; padding:4px 10px; border-radius:999px; border:1px solid #334155; color:#CBD5E1; font-size:.75rem; font-weight:700; margin-right:6px; }
        table { width:100%; border-collapse:collapse; font-size:.92rem; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid #1F2937; vertical-align:top; }
        th { color:#94A3B8; font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; }
        .endpoint { color:#FDE68A; }
    </style>
</head>
<body>
<div class="wrap">
    <nav>
        <a class="brand" href="{{ route('home') }}">Market Eye</a>
        <div>
            <a href="{{ route('home') }}">Home</a>
            &nbsp;·&nbsp;
            <a href="{{ route('developer.register') }}">Get API key</a>
            &nbsp;·&nbsp;
            <a href="{{ route('developer.login') }}">Developer login</a>
        </div>
    </nav>

    <h1>Developer API</h1>
    <p class="lead">
        Market Eye is the standard for crowd-verified Nigerian market prices.
        Authenticate with an API key, respect daily limits, and use the same units shoppers use: kg, mudu, bag, paint, congo, derica.
    </p>

    <div class="card">
        <h2>Base URL</h2>
        <pre>{{ $baseUrl }}</pre>
        <p>Send your key on every request:</p>
        <pre>X-API-Key: me_your_key_here
# or
Authorization: Bearer me_your_key_here</pre>
        <p>Default daily limit: <strong style="color:#fff;">1,000</strong> requests per key. Responses include <code>X-RateLimit-Limit</code>, <code>X-RateLimit-Remaining</code>, and <code>X-RateLimit-Reset</code>. Over limit returns <code>429</code>.</p>
    </div>

    <div class="card">
        <h2>Endpoints</h2>
        <table>
            <thead>
            <tr><th>Method</th><th>Path</th><th>Description</th></tr>
            </thead>
            <tbody>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/markets</td><td>List active markets</td></tr>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/categories</td><td>Goods categories</td></tr>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/products</td><td>Catalogue with measurement units</td></tr>
            <tr><td><span class="pill">GET</span></td><td class="endpoint">/markets/{id}/prices</td><td>Latest prices for a market. Optional <code>?category=</code> and <code>?search=</code></td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Example — market prices</h2>
        <pre>curl -s "{{ $baseUrl }}/markets/1/prices" \
  -H "X-API-Key: me_your_key_here"</pre>
        <pre>const res = await fetch("{{ $baseUrl }}/markets/1/prices", {
  headers: { "X-API-Key": "me_your_key_here" }
});
const data = await res.json();
// data.data.prices[0].price.avg
// data.data.prices[0].measurement.unit  // e.g. "mudu", "50kg bag"</pre>
    </div>

    <div class="card">
        <h2>Price object shape</h2>
        <pre>{
  "product": { "id": 1, "name": "Rice (local)", "unit": "50kg bag" },
  "category": { "id": 2, "name": "Grains", "slug": "grains" },
  "market": { "id": 1, "name": "Wuse Market", "area": "Wuse" },
  "price": { "avg": 78500, "min": 76000, "max": 81000, "currency": "NGN" },
  "measurement": { "unit": "50kg bag", "note": "..." },
  "quality": {
    "submission_count": 12,
    "low_confidence": false,
    "confidence_level": "high",
    "is_stale": false
  },
  "updated_at": "2026-07-24"
}</pre>
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
</body>
</html>
