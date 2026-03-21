<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; color: #334155; padding: 40px 20px;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; padding: 32px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 20px;">Verify your ticketcloser account</h2>
        <p style="font-size: 15px; color: #64748b; line-height: 1.5; margin-bottom: 24px;">
            Please enter the following verification code to confirm your email address and activate your account. This code is valid for 15 minutes.
        </p>
        <div style="background-color: #f1f5f9; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <span style="font-family: monospace; font-size: 32px; font-weight: bold; color: #0f172a; letter-spacing: 4px;">{{ $otpCode }}</span>
        </div>
        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 0;">
            If you didn't request this email, you can safely ignore it.
        </p>
    </div>
</body>
</html>
