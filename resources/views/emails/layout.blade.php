{{--
    CPRMS email shell. Table-based with inline styles on purpose: Outlook and Gmail
    strip <style> blocks and ignore flexbox, so nothing here relies on either.
    Every CPRMS notification renders through this one view; the notification class
    supplies heading / greeting / lines / action / outro.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} — CPRMS</title>
</head>
<body style="margin:0; padding:0; background:#F4F7F9; font-family:'Segoe UI',Helvetica,Arial,sans-serif; color:#263238;">

{{-- Preview text: what the inbox shows next to the subject, hidden in the body. --}}
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $preview ?? '' }}</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F7F9; padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:560px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #dbe3e8;">

                {{-- Brand bar --}}
                <tr>
                    <td style="background:#123B5D; padding:22px 28px;">
                        <div style="font-size:19px; font-weight:700; color:#ffffff; letter-spacing:.5px;">CPRMS</div>
                        <div style="font-size:11px; color:#A9C6D6; margin-top:3px;">
                            Campus Property Recovery &amp; Management System · University Recovery System
                        </div>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:28px;">
                        <h1 style="margin:0 0 6px; font-size:19px; font-weight:700; color:#0C2A42;">{{ $heading }}</h1>

                        @isset($greeting)
                            <p style="margin:0 0 14px; font-size:14px; color:#374151;">{{ $greeting }}</p>
                        @endisset

                        @foreach ((array) $lines as $line)
                            <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">{{ $line }}</p>
                        @endforeach

                        @isset($actionUrl)
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0;">
                                <tr>
                                    <td style="background:#0F8B8D; border-radius:9px;">
                                        <a href="{{ $actionUrl }}"
                                           style="display:inline-block; padding:12px 26px; font-size:14px; font-weight:600;
                                                  color:#ffffff; text-decoration:none;">{{ $actionText }}</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 6px; font-size:11.5px; color:#6b7280;">
                                If the button doesn't work, copy this link into your browser:
                            </p>
                            <p style="margin:0 0 14px; font-size:11.5px; word-break:break-all;">
                                <a href="{{ $actionUrl }}" style="color:#123B5D;">{{ $actionUrl }}</a>
                            </p>
                        @endisset

                        @isset($outro)
                            <p style="margin:18px 0 0; padding-top:16px; border-top:1px solid #e5e7eb;
                                      font-size:12px; line-height:1.6; color:#6b7280;">{{ $outro }}</p>
                        @endisset
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f7f9fb; padding:16px 28px; border-top:1px solid #e5e7eb;">
                        <p style="margin:0; font-size:11px; line-height:1.6; color:#6b7280;">
                            This is an automated message from the University Recovery System Lost &amp; Found Office — please don't reply to it.
                            For help, visit the office in person.
                        </p>
                        <p style="margin:8px 0 0; font-size:11px; color:#9ca3af;">
                            &copy; {{ date('Y') }} University Recovery System · CPRMS
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
