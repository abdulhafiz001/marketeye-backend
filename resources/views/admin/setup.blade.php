<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up Market Eye Admin</title>
    <style>
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background:#0B1220; color:#E5E7EB; }
        .wrap { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 24px; }
        .card { width: 100%; max-width: 460px; background:#111827; border:1px solid #1F2937; border-radius: 16px; padding: 22px; }
        h1 { margin:12px 0 6px; font-size: 22px; }
        p { margin:0 0 18px; color:#94A3B8; font-size: 14px; line-height: 1.5; }
        label { display:block; font-size: 12px; color:#94A3B8; margin: 12px 0 6px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        input { width: 100%; padding: 12px; border-radius: 12px; border:1px solid #1F2937; background:#0B1220; color:#F8FAFC; outline: none; box-sizing: border-box; }
        button { margin-top: 16px; width: 100%; padding: 12px; border-radius: 12px; border: 0; background:#F59E0B; color:#0B1220; font-weight: 900; cursor: pointer; }
        .err { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color:#FCA5A5; padding: 10px 12px; border-radius: 12px; font-size: 13px; margin-bottom: 10px; }
        .badge { display:inline-flex; padding: 8px 10px; border-radius: 999px; border:1px solid rgba(34, 197, 94, 0.35); background: rgba(34, 197, 94, 0.12); color:#22C55E; font-weight: 900; font-size: 12px; letter-spacing: .08em; }
        .hint { margin-top: 14px; font-size: 12px; color:#64748B; }
        code { color:#FDE68A; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="badge">FIRST ADMIN</div>
        <h1>Create the admin account</h1>
        <p>
            One-time setup. This page only works while no admin exists yet.
            After you create the account you’ll be signed in and sent to the dashboard.
        </p>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('setup.post') }}">
            @csrf
            <label for="name">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>

            <button type="submit">Create admin account</button>
        </form>

        <p class="hint">Bookmark later: <code>/admin/login</code>. Setup URL: <code>/setup</code>.</p>
    </div>
</div>
</body>
</html>
