<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Market Eye</title>
    @stack('head')
    <style>
        :root { --bg:#0B1220; --panel:#111827; --border:#1F2937; --text:#E5E7EB; --muted:#94A3B8; --accent:#22C55E; --sidebar:#0F172A; --sidebar-hover:rgba(34,197,94,.08); }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background: var(--bg); color: var(--text); }
        .admin-shell { display: flex; min-height: 100%; }
        .sidebar-state { display: none; }
        .mobile-topbar { display: none; }
        .sidebar-backdrop { display: none; }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            padding: 20px 0 0;
        }
        .sidebar-brand {
            padding: 0 20px 20px;
            font-weight: 900;
            letter-spacing: -0.02em;
            font-size: 17px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
        }
        .sidebar-nav a {
            display: block;
            color: var(--muted);
            text-decoration: none;
            padding: 11px 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
            border: 1px solid transparent;
        }
        .sidebar-nav a:hover { background: var(--sidebar-hover); color: var(--text); }
        .sidebar-nav a.active {
            background: rgba(34, 197, 94, 0.12);
            color: var(--text);
            border-color: rgba(34, 197, 94, 0.25);
        }
        .sidebar-footer {
            flex-shrink: 0;
            padding: 16px 16px 20px;
            border-top: 1px solid var(--border);
            background: var(--sidebar);
        }
        .sidebar-footer form { margin: 0; }
        .btn-logout {
            width: 100%;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-logout:hover { border-color: rgba(239, 68, 68, 0.45); color: #FCA5A5; }
        .main-wrap {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
            overflow-y: auto;
        }
        .main-inner { padding: 24px 28px 40px; max-width: 1400px; }
        .topbar-page { margin-bottom: 22px; }
        .topbar-page h1 { margin: 0 0 6px; font-size: 22px; font-weight: 900; letter-spacing: -0.02em; }
        .topbar-page .muted { color: var(--muted); font-size: 13px; font-weight: 600; }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; font-weight: 800; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-weight: 900; font-size: 11px; border: 1px solid var(--border); color: var(--muted); }
        .btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            font-size: 12px;
        }
        .btn:hover { border-color: rgba(245, 158, 11, 0.45); }
        .btn-primary { background: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.35); color: #86EFAC; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--border);
            background: #0B1220;
            color: var(--text);
            border-radius: 10px;
            padding: 9px 11px;
            font-size: 13px;
        }
        textarea { min-height: 70px; resize: vertical; }
        label { display: block; color: var(--muted); font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .form-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; margin-bottom: 16px; }
        @media (max-width: 1100px) { .form-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 900px) {
            .sidebar { width: 220px; }
            .main-wrap { margin-left: 220px; }
            .sidebar-brand, .sidebar-nav { padding-left: 14px; padding-right: 14px; }
        }
        @media (max-width: 760px) {
            body.admin-shell { display: block; }
            .mobile-topbar {
                position: sticky;
                top: 0;
                z-index: 80;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                min-height: 58px;
                padding: 10px 14px;
                background: rgba(15, 23, 42, 0.98);
                border-bottom: 1px solid var(--border);
            }
            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border-radius: 12px;
                border: 1px solid var(--border);
                color: var(--text);
                font-weight: 900;
                cursor: pointer;
                background: #0B1220;
            }
            .mobile-brand { font-weight: 900; letter-spacing: -0.02em; }
            .sidebar {
                width: 82vw;
                max-width: 320px;
                transform: translateX(-105%);
                transition: transform .2s ease;
                box-shadow: 20px 0 50px rgba(0,0,0,.35);
            }
            .sidebar-state:checked ~ .sidebar { transform: translateX(0); }
            .sidebar-state:checked ~ .sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 90;
                background: rgba(0,0,0,.52);
            }
            .main-wrap { margin-left: 0; min-height: calc(100vh - 58px); }
            .main-inner { padding: 18px 14px 32px; }
            .form-grid { grid-template-columns: 1fr; }
            table { min-width: 760px; }
        }
        .flash { margin-bottom: 14px; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(34,197,94,.35); background: rgba(34,197,94,.12); color: #BBF7D0; font-weight: 800; font-size: 13px; }
        .err { margin-bottom: 14px; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(239,68,68,.35); background: rgba(239,68,68,.12); color: #FCA5A5; font-weight: 800; font-size: 13px; }
        .page-hint { color: var(--muted); font-size: 13px; margin-bottom: 18px; line-height: 1.45; }
    </style>
    @stack('styles')
</head>
<body class="admin-shell">
<input class="sidebar-state" type="checkbox" id="sidebar-state">
<div class="mobile-topbar">
    <label class="mobile-menu-btn" for="sidebar-state" aria-label="Open menu">☰</label>
    <div class="mobile-brand">Market Eye Admin</div>
    <span style="width:42px;"></span>
</div>
<label class="sidebar-backdrop" for="sidebar-state" aria-label="Close menu"></label>
<aside class="sidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">Market Eye</div>
    <nav class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Manage categories</a>
        <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.index') ? 'active' : '' }}">Manage products</a>
        <a href="{{ route('admin.markets.index') }}" class="{{ request()->routeIs('admin.markets.index') ? 'active' : '' }}">Manage markets</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">Manage users</a>
        <a href="{{ route('admin.submissions.index') }}" class="{{ request()->routeIs('admin.submissions.index') ? 'active' : '' }}">Manage submissions</a>
        <a href="{{ route('admin.claims.index') }}" class="{{ request()->routeIs('admin.claims.*') ? 'active' : '' }}">Airtime claims</a>
        <a href="{{ route('admin.prices.index') }}" class="{{ request()->routeIs('admin.prices.*') ? 'active' : '' }}">Manage prices</a>
        <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
        <a href="{{ route('admin.external.index') }}" class="{{ request()->routeIs('admin.external.*') ? 'active' : '' }}">External price data</a>
        <a href="{{ route('admin.api-keys.index') }}" class="{{ request()->routeIs('admin.api-keys.*') ? 'active' : '' }}">API keys</a>
        <a href="{{ route('admin.activity') }}" class="{{ request()->routeIs('admin.activity') ? 'active' : '' }}">Recent admin activity</a>
    </nav>
    <div class="sidebar-footer">
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button class="btn-logout" type="submit">Logout</button>
        </form>
    </div>
</aside>
<div class="main-wrap">
    <div class="main-inner">
        <div class="topbar-page">
            <h1>@yield('page_title')</h1>
            <div class="muted">Signed in as {{ auth('admin')->user()?->email }}</div>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </div>
</div>
@stack('scripts')
</body>
</html>
