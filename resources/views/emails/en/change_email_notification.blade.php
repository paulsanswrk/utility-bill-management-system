<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Mobile-first responsive email styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content {
            font-size: 16px;
            line-height: 1.5;
            color: #333333;
        }

        .header {
            text-align: center;
            background-color: #007bff;
            padding: 20px;
            color: white;
            font-size: 20px;
            border-radius: 8px 8px 0 0;
        }

        .bill {
            background-color: #f9f9f9;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .bill-item {
            margin-bottom: 5px;
        }

        .button {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            margin: 20px 0;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 20px;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 10px;
            }

            .header, .button {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        Email Address Change Notification
    </div>

    <div class="content">

        <p>Dear {{ $user->name }},</p>
        <p>We have received a request to change the email address associated with your account. If you made this
            request, please confirm your new email address by clicking the link below:</p>
        <p><a href="{{ $confirmation_link }}" class="button">Confirm Email Address</a></p>
        <p>If you did not request this change, please ignore this email. No further action is required on your part, and
            your email address will remain unchanged.</p>
        <p>Thank you for your attention to this matter.</p>
    </div>

    <div class="footer">
        Best regards,<br>
        {{ $company_name }}<br>
        {{ $customer_service_contact }}<br>
        <a href="{{ $website_url }}">{{ $website_url }}</a>
    </div>
</div>
</body>
</html>
