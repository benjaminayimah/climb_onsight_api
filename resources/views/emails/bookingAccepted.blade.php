<!doctype html>
<html lang="eng">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<style>
.btn:hover {
    background-color: #212135 !important
}
</style>
<table style="width: 100%; background-color: #EFF3FF">
    <tbody>
        <tr>
            <td style="padding: 80px 0;">
                <table style="width: 60%; max-width: 550px; min-width: 420px; margin: 0 auto">
                    <tbody>
                        <tr>
                            <td style="font-family:'Neue Montreal',sans-serif; font-size: 17px; font-weight: 400; line-height: 1.5">
                                <p style="text-align:center; margin: 24px 0">
                                    <a href="{{ $frontend_url ?? '' }}" target="_blank" style="padding: 8px">
                                        <img aria-hidden="true" src="{{ $s3bucket }}/app/cos-logo.png" height="12" alt="ClimbOnSight">
                                    </a>
                                </p>
                                <div class="body-card" style="background-color: #fff; padding: 30px; border-radius: 16px">
                                    
                                    <div style="padding: 12px 0">
                                        <span style="color: #000; padding-bottom: 6px; font-weight: 600; font-size: 25px; display:inline-block">
                                            Your booking has been accepted
                                        </span>
                                    </div>
                                    <div style="color: rgb(34, 34, 34);">
                                        <p>
                                            <strong>Hello,</strong>
                                        </p>
                                        <p>
                                            Your booking has been accepted by the trip guide.
                                        </p>
                                        <p>
                                            Log in to your account to complete payment for this trip. We will hold your spot for <strong>72 hours, </strong> after which your booking would be canceled.
                                        </p>
                                        <p>
                                            Thank you for choosing us, and we look forward to welcoming you!
                                        </p>
                                        <p>
                                            <a href="{{ $frontend_url }}" target="_blank" class="btn" style="background-color: #000; border-radius: 18px; text-align: center; color:#fff; display:block; padding: 14px 24px; text-decoration: none; margin: 32px 24px">Log in now</a>
                                        </p>
                                        <p style="margin-top: 32px">
                                            Climb Onsight Team.
                                        </p>
                                    </div>
                                </div>
                                <div style="padding: 20px 40px 0 40px;text-align: center">
                                    <div style="font-size: 12px; color: #7A7D84">
                                        <span>You received this email because you have created an account with ClimbOnSight.</span>
                                        <span> For more enquiries, contact us at <a style="color: #212121;" href="mailTo:info@climbonsight.com">info@climbonsight.com</a></span>
                                        <span> or visit our website <a style="color: #212121;" href="{{ $frontend_url ?? '' }}" target="_blank">www.climbonsight.com</a> for more information.</span>
                                        <br>
                                        <p>
                                            <div>© 2023 ClimbOnSight. All Rights Reserved.</div>
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
</body>
</html>
