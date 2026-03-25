@php($requester = $merchantRequest->user)
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:0;padding:0;background-color:#0f172a;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:600px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
                <tr>
                    <td style="background-color:#ffffff;border-radius:12px;padding:24px;border:1px solid #e2e8f0;">
                        <h1 style="margin:0 0 10px 0;font-size:20px;line-height:26px;color:#0f172a;">
                            Merchant Request Update
                        </h1>
                        <p style="margin:0 0 14px 0;font-size:14px;line-height:20px;color:#334155;">
                            Hi {{ $requester->name }},
                        </p>
                        <p style="margin:0 0 18px 0;font-size:14px;line-height:20px;color:#334155;">
                            Thank you for your interest. After review, your merchant request was not approved.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <p style="margin:0;font-size:13px;line-height:18px;color:#475569;">
                                        <strong style="color:#0f172a;">Reason:</strong>
                                        {{ $decisionNote ?: 'No additional details were provided.' }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;font-size:13px;line-height:19px;color:#64748b;">
                            You may update your details and submit a new request at any time.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0 0;text-align:center;">
                        <p style="margin:0;font-size:12px;line-height:18px;color:#94a3b8;">
                            Booking System Admin
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

