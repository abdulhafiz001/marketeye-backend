<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email</title>
</head>
<body style="margin:0;padding:0;background:#F5F5F5;font-family:Georgia,'Times New Roman',serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F5F5F5;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:4px;overflow:hidden;border:1px solid #E8E8E8;">
                <tr>
                    <td style="background:#1E3A5F;padding:24px 32px;">
                        <div style="font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#52D3A8;font-family:Arial,sans-serif;font-weight:700;">Market Eye</div>
                        <div style="margin-top:8px;font-size:24px;color:#ffffff;font-weight:700;">Verify your email</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 32px;color:#1E3A5F;">
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#6C757D;font-family:Arial,sans-serif;">
                            @if($userName)Hi {{ $userName }}, use @else Use @endif this 6-digit code in the app to finish creating your account. It expires in 15 minutes.
                        </p>
                        <div style="letter-spacing:0.35em;font-size:36px;font-weight:700;text-align:center;padding:18px;background:#F5F5F5;border:1px dashed #2ECC71;color:#1E3A5F;font-family:Arial,sans-serif;">
                            {{ $code }}
                        </div>
                        <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#6C757D;font-family:Arial,sans-serif;">
                            If you did not create a Market Eye account, you can ignore this email.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
