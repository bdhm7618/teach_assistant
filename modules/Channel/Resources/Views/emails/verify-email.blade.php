@php
    $isRtl = in_array(app()->getLocale(), ['ar']);
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('channel::app.mail.verify_email_subject') }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial, Helvetica, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:20px;">
    <tr>
        <td align="center">

            <!-- Card -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:520px;background:#ffffff;border-radius:8px;padding:24px;
                          text-align:{{ $isRtl ? 'right' : 'left' }};">

                <!-- Title -->
                <tr>
                    <td style="text-align:center;padding-bottom:16px;">
                        <h2 style="margin:0;color:#111827;">
                            {{ __('channel::app.mail.verify_email_title') }}
                        </h2>
                    </td>
                </tr>

                <!-- Greeting -->
                <tr>
                    <td style="color:#374151;font-size:14px;line-height:1.7;">
                        {{ __('channel::app.mail.hello', ['name' => $user->name]) }}
                    </td>
                </tr>

                <!-- Message -->
                <tr>
                    <td style="color:#374151;font-size:14px;line-height:1.7;padding-top:12px;">
                        {{ __('channel::app.mail.verify_email_text') }}
                    </td>
                </tr>

                <!-- OTP -->
                <tr>
                    <td align="center" style="padding:28px 0;">
                        <div style="
                            direction:ltr;
                            display:inline-block;
                            font-size:28px;
                            letter-spacing:6px;
                            font-weight:bold;
                            color:#111827;
                            background:#f3f4f6;
                            padding:14px 26px;
                            border-radius:6px;
                        ">
                            {{ $otp }}
                        </div>
                    </td>
                </tr>

                <!-- Expiration -->
                <tr>
                    <td style="color:#6b7280;font-size:13px;text-align:center;">
                        {{ __('channel::app.mail.otp_expire') }}
                    </td>
                </tr>

                <!-- Divider -->
                <tr>
                    <td style="padding:24px 0;">
                        <hr style="border:none;border-top:1px solid #e5e7eb;">
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="color:#6b7280;font-size:12px;line-height:1.7;text-align:center;">
                        {{ __('channel::app.mail.ignore_if_not_you') }}
                    </td>
                </tr>

            </table>
            <!-- End Card -->

        </td>
    </tr>
</table>
</body>
</html>
