<x-emails.brand eyebrow="Account verification" title="Verify your tickIt account">
    <p style="margin:0 0 18px;">
        Enter this six-digit code in tickIt to finish setting up your account. It stays valid for 15 minutes.
    </p>

    <div style="margin:0 0 22px;border:1px solid rgba(255,255,255,0.1);border-radius:22px;background:rgba(255,255,255,0.04);padding:20px 18px;text-align:center;">
        <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:34px;font-weight:800;letter-spacing:0.28em;color:#ffffff;">
            {{ $otpCode }}
        </div>
    </div>

    <p style="margin:0;color:#94a3b8;">
        If you did not create an account, you can safely ignore this email.
    </p>
</x-emails.brand>
