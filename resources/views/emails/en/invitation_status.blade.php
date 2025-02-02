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

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }

        .status-declined {
            color: #dc3545;
            font-weight: bold;
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
        <h1>Invitation Status Update</h1>
    </div>
    <div class="content">
        @if ($ownerName)
            <p>Dear {{ $ownerName }},</p>
        @else
            <p>Hello!</p>
        @endif
        <p>
            We wanted to inform you that your invitation request for the household
            <strong>{{ $householdName }}</strong> has been 
            @if ($status === 'approved')
                <span class="status-approved">approved</span>.
            @else
                <span class="status-declined">declined</span>.
            @endif
        </p>
        @if ($status === 'approved')
            <p>
                Your request was processed successfully, and the household member can now access your household details.
            </p>
        @else
            <p>
                Unfortunately, the request could not be processed at this time. Feel free to contact us for more details or assistance.
            </p>
        @endif
        <p>If you have any questions, please contact us at <a
                href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
    </div>
    <div class="footer">
        <p>Thank you,<br>The {{ $billingSystemName }} Team</p>
        <p><a href="{{ $websiteURL }}">{{ $websiteURL }}</a></p>
    </div>
</div>
</body>
</html>