<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Developer') — Market Eye</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0F172A; --panel:#111827; --border:#1F2937; --text:#E5E7EB; --muted:#94A3B8; --green:#22C55E; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Outfit,system-ui,sans-serif; background:var(--bg); color:var(--text); }
        .wrap { width:min(920px, calc(100% - 32px)); margin:0 auto; padding:28px 0 56px; }
        a { color:#86EFAC; text-decoration:none; }
        .nav { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:28px; }
        .brand { font-weight:800; font-size:1.2rem; color:#fff; }
        .card { background:var(--panel); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:16px; }
        label { display:block; color:var(--muted); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
        input { width:100%; border:1px solid var(--border); background:#0B1220; color:var(--text); border-radius:10px; padding:10px 12px; margin-bottom:12px; }
        .btn { display:inline-block; border:1px solid rgba(34,197,94,.35); background:rgba(34,197,94,.15); color:#86EFAC; padding:10px 14px; border-radius:10px; font-weight:800; cursor:pointer; }
        .btn-ghost { background:transparent; border-color:var(--border); color:var(--text); }
        .flash { margin-bottom:14px; padding:12px; border-radius:12px; border:1px solid rgba(34,197,94,.35); background:rgba(34,197,94,.12); color:#BBF7D0; font-weight:700; word-break:break-all; }
        .err { margin-bottom:14px; padding:12px; border-radius:12px; border:1px solid rgba(239,68,68,.35); background:rgba(239,68,68,.12); color:#FCA5A5; font-weight:700; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { padding:10px 8px; border-bottom:1px solid var(--border); text-align:left; }
        th { color:var(--muted); font-size:11px; text-transform:uppercase; }
        h1 { margin:0 0 8px; font-size:1.6rem; }
        p.muted { color:var(--muted); line-height:1.5; }
        code { font-family:"IBM Plex Mono", monospace; }
    </style>
</head>
<body>
<div class="wrap">
    <nav class="nav">
        <a class="brand" href="{{ route('home') }}">Market Eye Developers</a>
        <div>
            <a href="{{ route('developers') }}">Docs</a>
            @auth('developer')
                &nbsp;·&nbsp;
                <form method="post" action="{{ route('developer.logout') }}" style="display:inline;">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Logout</button>
                </form>
            @endauth
        </div>
    </nav>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    @if (session('new_api_key'))
        <div class="flash">New key (copy now): <strong>{{ session('new_api_key') }}</strong></div>
    @endif
    @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</div>
</body>
</html>
