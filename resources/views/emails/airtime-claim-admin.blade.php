<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Airtime claim</title>
</head>
<body style="margin:0;padding:24px;background:#F5F5F5;font-family:Arial,sans-serif;color:#1E3A5F;">
    <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E8E8E8;padding:24px;">
        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#2ECC71;font-weight:700;">Market Eye Admin</div>
        <h1 style="margin:8px 0 16px;font-size:22px;">New airtime claim</h1>
        <p style="color:#6C757D;line-height:1.5;">
            <strong>{{ $claim->user?->name }}</strong> ({{ $claim->user?->email }}) requested
            <strong>₦{{ number_format((int) $claim->amount) }}</strong> airtime to
            <strong>{{ $claim->phone }}</strong>.
        </p>
        <p style="margin-top:20px;">
            <a href="{{ url('/admin/claims') }}" style="display:inline-block;background:#1E3A5F;color:#fff;text-decoration:none;padding:12px 18px;font-weight:700;">
                Open claims panel
            </a>
        </p>
    </div>
</body>
</html>
