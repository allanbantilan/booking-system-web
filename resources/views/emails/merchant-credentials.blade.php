@php($user = $backendUser)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        /* Mobile responsive */
        @media only screen and (max-width: 620px) {
            .email-wrapper {
                padding: 16px 8px !important;
            }
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .email-body {
                padding: 20px 16px !important;
                border-radius: 8px !important;
            }
            .credentials-table td {
                padding: 12px 14px !important;
            }
            h1.email-heading {
                font-size: 18px !important;
                line-height: 24px !important;
            }
            .credential-row {
                display: block !important;
                margin-bottom: 10px !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation"
       style="margin:0;padding:0;background-color:#0f172a;border-collapse:collapse;">
    <tr>
        <td align="center" class="email-wrapper" style="padding:32px 16px;">

            <!-- Outer container: fluid on mobile, fixed 600px on desktop -->
            <table class="email-container" cellpadding="0" cellspacing="0" role="presentation"
                   style="width:100%;max-width:600px;border-collapse:collapse;font-family:Georgia,'Times New Roman',Times,serif;">

                <!-- ── Header accent bar ── -->
                <tr>
                    <td style="background:linear-gradient(90deg,#3b82f6 0%,#6366f1 100%);height:4px;border-radius:12px 12px 0 0;font-size:0;line-height:0;">&nbsp;</td>
                </tr>

                <!-- ── Main card ── -->
                <tr>
                    <td class="email-body"
                        style="background-color:#ffffff;padding:32px 32px 28px 32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0;border-top:none;">

                        <!-- Brand label -->
                        <p style="margin:0 0 20px 0;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#6366f1;font-family:Arial,Helvetica,sans-serif;font-weight:700;">
                            Booking System
                        </p>

                        <!-- Heading -->
                        <h1 class="email-heading"
                            style="margin:0 0 12px 0;font-size:22px;line-height:28px;color:#0f172a;font-family:Georgia,'Times New Roman',Times,serif;font-weight:700;letter-spacing:-0.01em;">
                            Merchant Access Created
                        </h1>

                        <!-- Greeting -->
                        <p style="margin:0 0 8px 0;font-size:14px;line-height:21px;color:#334155;font-family:Arial,Helvetica,sans-serif;">
                            Hi {{ $user->name }},
                        </p>

                        <!-- Body copy -->
                        <p style="margin:0 0 24px 0;font-size:14px;line-height:21px;color:#475569;font-family:Arial,Helvetica,sans-serif;">
                            Your merchant access has been created. Use the credentials below to sign in to your account.
                        </p>

                        <!-- ── Credentials card ── -->
                        <table class="credentials-table" width="100%" cellpadding="0" cellspacing="0" role="presentation"
                               style="border-collapse:collapse;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                            <tr>
                                <td style="padding:18px 20px;">

                                    <!-- Login URL -->
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                           style="border-collapse:collapse;margin-bottom:12px;">
                                        <tr>
                                            <td style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;font-family:Arial,Helvetica,sans-serif;font-weight:700;padding-bottom:3px;">
                                                Login URL
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;line-height:19px;color:#1e40af;font-family:Arial,Helvetica,sans-serif;word-break:break-all;">
                                                <a href="{{ $loginUrl }}" style="color:#1e40af;text-decoration:underline;">{{ $loginUrl }}</a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Divider -->
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                           style="border-collapse:collapse;">
                                        <tr>
                                            <td style="border-top:1px solid #e2e8f0;padding-bottom:12px;font-size:0;line-height:0;">&nbsp;</td>
                                        </tr>
                                    </table>

                                    <!-- Email -->
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                           style="border-collapse:collapse;margin-bottom:12px;">
                                        <tr>
                                            <td style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;font-family:Arial,Helvetica,sans-serif;font-weight:700;padding-bottom:3px;">
                                                Email
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;line-height:19px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;word-break:break-all;">
                                                {{ $user->email }}
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Divider -->
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                           style="border-collapse:collapse;">
                                        <tr>
                                            <td style="border-top:1px solid #e2e8f0;padding-bottom:12px;font-size:0;line-height:0;">&nbsp;</td>
                                        </tr>
                                    </table>

                                    <!-- Password -->
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                                           style="border-collapse:collapse;">
                                        <tr>
                                            <td style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;font-family:Arial,Helvetica,sans-serif;font-weight:700;padding-bottom:3px;">
                                                Temporary Password
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;line-height:19px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-family:monospace;letter-spacing:0.04em;">
                                                {{ $plainPassword }}
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>
                        </table>

                        <!-- Warning note -->
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                               style="border-collapse:collapse;margin-top:16px;">
                            <tr>
                                <td style="background-color:#fefce8;border-left:3px solid #eab308;border-radius:0 6px 6px 0;padding:10px 14px;">
                                    <p style="margin:0;font-size:12px;line-height:18px;color:#713f12;font-family:Arial,Helvetica,sans-serif;">
                                        <strong>Important:</strong> Please change your password immediately after your first login.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:14px 0 0 0;font-size:12px;line-height:18px;color:#64748b;font-family:Arial,Helvetica,sans-serif;">
                            To update your password: open the top-right menu, go to <strong>Profile</strong>, then update your password.
                        </p>

                    </td>
                </tr>

                <!-- ── Footer ── -->
                <tr>
                    <td style="padding:20px 0 4px 0;text-align:center;">
                        <p style="margin:0;font-size:11px;line-height:17px;color:#64748b;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.04em;">
                            Booking System Admin &mdash; This is an automated message, please do not reply.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
