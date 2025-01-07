<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
        }

        .content {
            font-size: 16px;
            color: #555;
            line-height: 1.5;
        }

        .household-list {
            margin: 15px 0;
            padding: 0;
            list-style: none;
        }

        .household-list li {
            margin: 5px 0;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
            color: #333;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .button.decline {
            background-color: #dc3545;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>You’re Invited to Join Households!</h1>
    </div>
    <div class="content">
        @if ($inviteeName)
            <p>Dear {{ $inviteeName }},</p>
        @else
            <p>Hello!</p>
        @endif
        <p>
            You have been invited by <strong>{{ $inviterName }}</strong> to join the following households on
            <strong>{{ $billingSystemName }}</strong>:
        </p>
        <ul class="household-list">
            @foreach ($households as $household)
                <li>{{ $household }}</li>
            @endforeach
        </ul>
        <p>As a household member, you'll be able to:</p>
        <ul>
            <li>View and manage utility bills for the households.</li>
            <li>Track payments and due dates.</li>
            <li>Collaborate with other members to keep the households organized.</li>
        </ul>
        <p>
            To respond to this invitation, please click one of the options below:
        </p>
        <p>
            <a href="{{ $acceptLink }}" class="button">Accept Invitation</a>
            <a href="{{ $declineLink }}" class="button decline">Decline Invitation</a>
        </p>
        <p>If you have any questions or need assistance, please feel free to contact us at <a
                href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
        <p>If you do not recognize this invitation or believe it was sent in error, you can safely disregard this
            email.</p>
    </div>
    <div class="footer">
        <p>Thank you,<br>The {{ $billingSystemName }} Team</p>
        <p><a href="{{ $websiteURL }}">{{ $websiteURL }}</a></p>
    </div>
</div>
</body>
</html>
