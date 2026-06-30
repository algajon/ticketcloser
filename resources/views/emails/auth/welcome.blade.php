@php
    $workspaceUrl = route('dashboard');
    $docsUrl = route('docs');
    $salesEmail = config('services.tickit_sales.email') ?: 'jon@ticketcloser.online';
    $salesSubject = rawurlencode('Help me launch tickIt');
    $salesHref = 'mailto:' . $salesEmail . '?subject=' . $salesSubject;
    $calendlyUrl = trim((string) config('services.tickit_sales.calendly_url'));
@endphp

<x-emails.brand eyebrow="Welcome to tickIt" title="Your workspace is ready.">
    <p style="margin:0 0 18px;">
        Hi {{ $user->name }}, your email is verified and
        <strong style="color:#ffffff;">{{ $workspace->name }}</strong> is configured. You can start testing the full call-to-ticket flow now.
    </p>

    <div style="margin:0 0 22px;border:1px solid rgba(255,255,255,0.1);border-radius:22px;background:rgba(255,255,255,0.04);padding:18px 18px;">
        <div style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:0.22em;text-transform:uppercase;color:#94a3b8;">
            Start here
        </div>
        <ul style="margin:0;padding-left:18px;color:#cbd5e1;">
            <li style="margin:0 0 8px;">Open your workspace and review the assistant draft.</li>
            <li style="margin:0 0 8px;">Use the documentation if you want the clean setup path for numbers, assistants, tickets, and calendars.</li>
            <li style="margin:0;">If you want help, talk to sales directly or book time with Jon.</li>
        </ul>
    </div>

    <div style="margin:0 0 18px;">
        <a href="{{ $workspaceUrl }}" style="display:inline-block;border-radius:14px;background:#f97316;padding:12px 18px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
            Open workspace
        </a>
        <a href="{{ $docsUrl }}" style="display:inline-block;margin-left:8px;border-radius:14px;border:1px solid rgba(255,255,255,0.22);padding:12px 18px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
            Read docs
        </a>
    </div>

    <div style="margin:0 0 22px;border-top:1px solid rgba(255,255,255,0.1);padding-top:18px;">
        <p style="margin:0 0 12px;color:#cbd5e1;">
            Want a human to sanity-check your setup? Email sales directly, or book a short Calendly appointment with Jon if you want hands-on launch help.
        </p>
        <a href="{{ $salesHref }}" style="display:inline-block;border-radius:14px;border:1px solid rgba(255,255,255,0.22);padding:11px 16px;font-size:13px;font-weight:700;color:#ffffff;text-decoration:none;">
            Talk to sales
        </a>
        @if($calendlyUrl !== '')
            <a href="{{ $calendlyUrl }}" style="display:inline-block;margin-left:8px;border-radius:14px;background:#ffffff;padding:11px 16px;font-size:13px;font-weight:700;color:#020617;text-decoration:none;">
                Book with Jon
            </a>
        @endif
    </div>

    <p style="margin:0;color:#94a3b8;">
        tickIt answers the phone, captures the details, creates a ticket, and keeps follow-up moving.
    </p>
</x-emails.brand>
