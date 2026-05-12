<x-emails.brand eyebrow="Welcome to tickIt" title="Your workspace is ready.">
    <p style="margin:0 0 18px;">
        Hi {{ $user->name }}, thanks for creating your account. Your first workspace,
        <strong style="color:#ffffff;">{{ $workspace->name }}</strong>, is ready for setup.
    </p>

    <div style="margin:0 0 22px;border:1px solid rgba(255,255,255,0.1);border-radius:22px;background:rgba(255,255,255,0.04);padding:18px 18px;">
        <div style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:0.22em;text-transform:uppercase;color:#94a3b8;">
            What happens next
        </div>
        <ul style="margin:0;padding-left:18px;color:#cbd5e1;">
            <li style="margin:0 0 8px;">Verify your email with the code from the other email.</li>
            <li style="margin:0 0 8px;">Choose your use case and finish workspace setup.</li>
            <li style="margin:0;">Review the first assistant and make a test call.</li>
        </ul>
    </div>

    <div style="margin:0 0 22px;">
        <a href="{{ route('login') }}" style="display:inline-block;border-radius:14px;background:#f97316;padding:12px 18px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
            Open tickIt
        </a>
    </div>

    <p style="margin:0;color:#94a3b8;">
        tickIt answers the phone, captures the details, creates a ticket, and keeps follow-up moving.
    </p>
</x-emails.brand>
