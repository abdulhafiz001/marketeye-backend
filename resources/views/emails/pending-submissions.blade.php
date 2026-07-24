<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pending submissions</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #111;">
    <p>Hello,</p>
    <p>
        There are <strong>{{ $pendingCount }}</strong> new price submissions waiting for review
        and no admin has logged in recently.
    </p>
    <p>
        Please sign in to the Market Eye admin panel to approve or decline them so contributors
        can earn their wallet rewards and shoppers see fresh prices.
    </p>
    <p>
        <a href="{{ url('/admin/login') }}">Open admin login</a>
    </p>
    <p style="color:#6B7280;font-size:13px;">— Market Eye</p>
</body>
</html>
