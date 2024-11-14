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
        Reminder: Unpaid Bills Notification
    </div>

    <div class="content">
        <p>Dear {{ $user->name }},</p>
        <p>We hope this email finds you well. Our records show that you have unpaid bills with the following details:</p>

        @foreach ($bills as $bill)
            <div class="bill">
                <div class="bill-item"><strong>Utility Company:</strong> {{ $bill->utility_company }}</div>

                <div class="bill-item"><strong>Bill Issue Date:</strong>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $bill->issue_date)->locale($locale)->translatedFormat('F Y') }}
                </div>
                <div class="bill-item"><strong>Amount Due:</strong> {{ Number::currency($bill->amount) }}</div>
            </div>
        @endforeach

        <p>We kindly request that you settle the payments by their due dates to avoid any potential service interruptions or late fees.</p>

        <p>If you have already made the payments, please disregard this email. If you need assistance with your payments or have any questions, feel free to contact our support team.</p>

        <a href="{{ $login_link }}" class="button">Log in to Pay Your Bills</a>

        <p>Thank you for your prompt attention to this matter.</p>
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
