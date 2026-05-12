@props([
    'eyebrow' => null,
    'title',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#020202;color:#e2e8f0;font-family:Inter,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#020202;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;">
                    <tr>
                        <td style="padding-bottom:16px;text-align:left;">
                            <div style="font-size:18px;font-weight:800;letter-spacing:-0.02em;color:#ffffff;">tickIt</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border:1px solid rgba(255,255,255,0.1);border-radius:28px;background:linear-gradient(180deg, rgba(2,6,23,0.94), rgba(2,6,23,0.88));padding:32px;box-shadow:0 24px 60px -32px rgba(2,6,23,0.9);">
                            @if($eyebrow)
                                <div style="margin-bottom:14px;font-size:12px;font-weight:700;letter-spacing:0.28em;text-transform:uppercase;color:#fdba74;">
                                    {{ $eyebrow }}
                                </div>
                            @endif

                            <h1 style="margin:0 0 14px;font-size:34px;line-height:1.1;font-weight:700;letter-spacing:-0.04em;color:#ffffff;">
                                {{ $title }}
                            </h1>

                            <div style="font-size:15px;line-height:1.75;color:#cbd5e1;">
                                {{ $slot }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:18px;text-align:left;font-size:12px;line-height:1.7;color:#94a3b8;">
                            tickIt helps businesses answer calls, create tickets, and keep follow-up moving.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
