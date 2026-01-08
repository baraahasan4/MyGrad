<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            border-bottom: 2px solid #007BFF;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #007BFF;
            letter-spacing: 5px;
            background-color: #e9f5ff;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 20px 0;
        }
        .email-footer {
            margin-top: 20px;
            color: #888888;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="email-container">
    <div class="email-header">
        <h1 style="color: #333333;">رمز التحقق</h1>
    </div>

    <p style="font-size: 16px; color: #555555;">مرحباً بك،</p>
    <p style="font-size: 16px; color: #555555;">الرمز التالي هو رمز التحقق الخاص بك. استخدمه لإتمام العملية المطلوبة.</p>

    <h2 class="otp-code">{{ $code }}</h2>

    <p style="font-size: 14px; color: #888888;">هذا الرمز صالح لمدة 10 دقائق.</p>

</div>

</body>
</html>
