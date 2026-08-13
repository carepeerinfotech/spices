{{--
    Branded shell wrapped around every EmailTemplate body by TemplateMailer.
    Palette mirrors public/css/home.css. Table layout and inline styles are
    deliberate: Outlook ignores <style> blocks and most float/flex CSS.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#fff7ec; -webkit-font-smoothing:antialiased;">

    {{-- Preview text: shown in the inbox list next to the subject, hidden in the body. --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">{{ $preview ?? $subject }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff7ec;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #eee9e2;">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="padding:28px 24px 20px 24px; background-color:#ffffff;">
                            <a href="{{ $appUrl }}" style="text-decoration:none;">
                                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" width="150" style="display:block; width:150px; max-width:150px; height:auto; border:0;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; background-color:#b82125; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 32px 36px 32px; font-family:'Montserrat','DM Sans','Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:15px; line-height:1.65; color:#29272a;">
                            {!! $content !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:26px 32px; background-color:#1a1714; font-family:'Montserrat','DM Sans','Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:13px; line-height:1.7; color:#bdbdbd;">
                            <p style="margin:0 0 8px 0; font-family:'Montserrat','DM Sans','Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:16px; font-weight:700; color:#ffffff;">{{ $storeName }}</p>
                            <p style="margin:0 0 14px 0; color:#bdbdbd;">Authentic Indian flavours, milled fresh since 1974.</p>
                            @if ($supportEmail)
                                <p style="margin:0 0 14px 0;">
                                    Questions? Write to
                                    <a href="mailto:{{ $supportEmail }}" style="color:#efa400; text-decoration:none;">{{ $supportEmail }}</a>
                                </p>
                            @endif
                            <p style="margin:0; padding-top:14px; border-top:1px solid #ffffff38; color:#8a8a8a; font-size:12px;">
                                &copy; {{ $year }} {{ $storeName }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
