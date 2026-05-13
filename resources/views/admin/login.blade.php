<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market Eye Admin</title>
    <style>
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background:#0B1220; color:#E5E7EB; }
        .wrap { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 24px; }
        .card { width: 100%; max-width: 420px; background:#111827; border:1px solid #1F2937; border-radius: 16px; padding: 22px; }
        h1 { margin:0 0 6px; font-size: 22px; }
        p { margin:0 0 18px; color:#94A3B8; font-size: 14px; line-height: 1.5; }
        label { display:block; font-size: 12px; color:#94A3B8; margin: 12px 0 6px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        input { width: 100%; padding: 12px 12px; border-radius: 12px; border:1px solid #1F2937; background:#0B1220; color:#F8FAFC; outline: none; }
        button { margin-top: 16px; width: 100%; padding: 12px 12px; border-radius: 12px; border: 0; background:#F59E0B; color:#0B1220; font-weight: 800; cursor: pointer; }
        .err { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color:#FCA5A5; padding: 10px 12px; border-radius: 12px; font-size: 13px; margin-bottom: 10px; }
        .badge { display:inline-flex; gap:8px; align-items:center; padding: 8px 10px; border-radius: 999px; border:1px solid rgba(245, 158, 11, 0.35); background: rgba(245, 158, 11, 0.12); color:#F59E0B; font-weight: 900; font-size: 12px; letter-spacing: .08em; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="badge">OFFICIAL</div>
        <h1 style="margin-top:12px;">Market Eye Admin</h1>
        <p>Secure access for moderators and administrators.</p>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('admin.login.post') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit">Continue</button>
        </form>
    </div>
</div>
</body>
</html>
